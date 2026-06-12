<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$logFile = __DIR__ . '/debug_reservation.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Début script\n", FILE_APPEND);

if (isset($_SESSION['paiement_data']) && !isset($_POST['trajet_json'])) {
    $_POST = $_SESSION['paiement_data'];
    unset($_SESSION['paiement_data']);
    file_put_contents($logFile, "Données récupérées depuis session\n", FILE_APPEND);
}

if (!isset($_SESSION['client_info']) || !isset($_POST['trajet_json']) || !isset($_POST['date_voyage'])) {
    file_put_contents($logFile, "Erreur : données manquantes\n", FILE_APPEND);
    header('Location: reservation_trajet.php');
    exit;
}

$host = "harpagon.unicaen.fr";
$port = "1521";
$sid = "info";
$user = "agile_5";
$password = "lemeilleurgroupe";
$dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$sid)))";

try {
    $conn = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    file_put_contents($logFile, "Connexion DB OK\n", FILE_APPEND);

    $client = $_SESSION['client_info'];
    $villesCodes = $client['villes'];
    $choix = json_decode($_POST['trajet_json'], true);
    $dateVoyage = $_POST['date_voyage'];
    file_put_contents($logFile, "Trajet décodé : " . json_encode($choix) . "\n", FILE_APPEND);

    $arcs = [];
    $stmt = $conn->query("SELECT TRIM(LIG_NUM) AS LIGNE, TRIM(COM_CODE_INSEE_ARRET) AS DEPART, TRIM(COM_CODE_INSEE_SUIVANT) AS ARRIVEE, NOE_DISTANCE_PROCHAIN AS DISTANCE, NOE_DUREE_PROCHAIN AS DUREE, TO_CHAR(NOE_HEURE_PASSAGE, 'HH24:MI') AS HEURE FROM VIK_NOEUD");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dep = $row['DEPART'];
        $arr = $row['ARRIVEE'];
        if (!isset($arcs[$dep][$arr])) {
            $arcs[$dep][$arr] = [
                'ligne' => $row['LIGNE'],
                'dist'  => (float)$row['DISTANCE'],
                'duree' => (float)$row['DUREE'],
                'heure' => $row['HEURE']
            ];
        }
    }
    file_put_contents($logFile, "Arcs récupérés\n", FILE_APPEND);

    $client_id = 0;
    $pourcentage_a_payer = 100;
    $points_actuels = 0;
    $points_tot = 0;
    if (isset($_SESSION['id']) && $_SESSION['id'] > 0) {
        $client_id = (int)$_SESSION['id'];
        $stmt = $conn->prepare("SELECT t.TYP_REDUC, c.CLI_NB_POINTS_EC, c.CLI_NB_POINTS_TOT
                                FROM VIK_CLIENT c
                                JOIN VIK_TYPE_CLIENT t ON c.TYP_NUM = t.TYP_NUM
                                WHERE c.CLI_NUM = :id");
        $stmt->execute(['id' => $client_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $pourcentage_a_payer = (float)$data['TYP_REDUC'];
            $points_actuels = (int)$data['CLI_NB_POINTS_EC'];
            $points_tot     = (int)$data['CLI_NB_POINTS_TOT'];
        }
        file_put_contents($logFile, "Client ID: $client_id, pourcentage: $pourcentage_a_payer, points actuels: $points_actuels\n", FILE_APPEND);
    } else {
        file_put_contents($logFile, "Client non connecté\n", FILE_APPEND);
    }

    $prix_initial = $choix['prix'];
    $prix_apres_type = isset($_POST['prix_apres_type']) ? (float)$_POST['prix_apres_type'] : round($prix_initial * ($pourcentage_a_payer / 100), 2);
    $reduction_points = isset($_POST['reduction_points']) ? (float)$_POST['reduction_points'] : 0;
    $points_utilises  = isset($_POST['points_utilises']) ? (int)$_POST['points_utilises'] : 0;

    $prix_final_euros = $prix_apres_type - $reduction_points;
    if ($prix_final_euros < 0) $prix_final_euros = 0;
    // Stockage en euros avec deux décimales (ex: 67.50)
    $prix_euros = round($prix_final_euros, 2);
    $prix_euros_str = number_format($prix_euros, 2, '.', '');
    $points_gagnes = max(1, (int)round($choix['distance']));

    file_put_contents($logFile, "prix_initial=$prix_initial, prix_apres_type=$prix_apres_type, reduction_points=$reduction_points, points_utilises=$points_utilises, prix_euros_str=$prix_euros_str, points_gagnes=$points_gagnes\n", FILE_APPEND);

    $tarif_tranche = 1;
    $stmt_tarif = $conn->prepare("SELECT TAR_NUM_TRANCHE FROM VIK_TARIF WHERE :dist BETWEEN TAR_MIN_DIST AND TAR_MAX_DIST");
    $stmt_tarif->execute(['dist' => $choix['distance']]);
    $tranche = $stmt_tarif->fetchColumn();
    if ($tranche !== false) $tarif_tranche = (int)$tranche;
    file_put_contents($logFile, "Tranche tarifaire: $tarif_tranche\n", FILE_APPEND);

    $res_num = $conn->query("SELECT NVL(MAX(RES_NUM),0)+1 FROM VIK_RESERVATION")->fetchColumn();
    $depart_str = trim($villesCodes[0]);
    $arrivee_str = trim(end($villesCodes));
    file_put_contents($logFile, "Nouveau RES_NUM: $res_num, depart: $depart_str, arrivee: $arrivee_str\n", FILE_APPEND);

    $sql = "INSERT INTO VIK_RESERVATION (CLI_NUM, RES_NUM, TAR_NUM_TRANCHE, RES_DATE, RES_NB_POINTS, RES_PRIX_TOT, CLI_NOM, CLI_PRENOM, RES_HEURE, COM_CODE_INSEE_DEPART, COM_CODE_INSEE_ARRIVEE)
            VALUES (:cli, :res, :tranche, SYSDATE, :pts, :prix, :nom, :prenom, :heure, :dep, :arr)";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':cli',   $client_id, PDO::PARAM_INT);
    $stmt->bindValue(':res',   $res_num, PDO::PARAM_INT);
    $stmt->bindValue(':tranche', $tarif_tranche, PDO::PARAM_INT);
    $stmt->bindValue(':pts',   $points_gagnes, PDO::PARAM_INT);
    $stmt->bindValue(':prix',  $prix_euros_str, PDO::PARAM_STR);
    $stmt->bindValue(':nom',   $client['nom'], PDO::PARAM_STR);
    $stmt->bindValue(':prenom',$client['prenom'], PDO::PARAM_STR);
    $stmt->bindValue(':heure', $choix['heure_depart'], PDO::PARAM_STR);
    $stmt->bindValue(':dep',   $depart_str, PDO::PARAM_STR);
    $stmt->bindValue(':arr',   $arrivee_str, PDO::PARAM_STR);
    $stmt->execute();
    file_put_contents($logFile, "Insertion réservation OK\n", FILE_APPEND);

    if ($client_id > 0) {
        $new_points_ec = $points_actuels - $points_utilises + $points_gagnes;
        $new_points_tot = $points_tot + $points_gagnes;
        $upd = "UPDATE VIK_CLIENT SET CLI_NB_POINTS_EC = :new_ec, CLI_NB_POINTS_TOT = :new_tot WHERE CLI_NUM = :cli";
        $st = $conn->prepare($upd);
        $st->bindValue(':new_ec', $new_points_ec, PDO::PARAM_INT);
        $st->bindValue(':new_tot', $new_points_tot, PDO::PARAM_INT);
        $st->bindValue(':cli', $client_id, PDO::PARAM_INT);
        $st->execute();
        file_put_contents($logFile, "Points mis à jour\n", FILE_APPEND);
    }

    $sqlEtape = "INSERT INTO VIK_ETAPE (CLI_NUM, RES_NUM, LIG_NUM, COM_CODE_INSEE_DEPART, COM_CODE_INSEE_ARRIVEE, ETA_DISTANCE, ETA_HEURE)
                 VALUES (:cli, :res, :lig, :dep, :arr, :dist, TO_DATE(:h, 'HH24:MI'))";
    $stmtEtape = $conn->prepare($sqlEtape);
    $path = $choix['path'];
    $dureeCumulee = 0;
    for ($i = 0; $i < count($path)-1; $i++) {
        $dep = $path[$i];
        $arr = $path[$i+1];
        if (!isset($arcs[$dep][$arr])) continue;
        $arc = $arcs[$dep][$arr];
        $heureTronçon = ($i == 0) ? $choix['heure_depart'] : date('H:i', strtotime($choix['heure_depart']) + $dureeCumulee * 60);
        $dureeCumulee += $arc['duree'];
        $stmtEtape->bindValue(':cli', $client_id, PDO::PARAM_INT);
        $stmtEtape->bindValue(':res', $res_num, PDO::PARAM_INT);
        $stmtEtape->bindValue(':lig', $arc['ligne'], PDO::PARAM_STR);
        $stmtEtape->bindValue(':dep', $dep, PDO::PARAM_STR);
        $stmtEtape->bindValue(':arr', $arr, PDO::PARAM_STR);
        $stmtEtape->bindValue(':dist', $arc['dist']);
        $stmtEtape->bindValue(':h', $heureTronçon, PDO::PARAM_STR);
        $stmtEtape->execute();
        file_put_contents($logFile, "Étape $i insérée\n", FILE_APPEND);
    }

    file_put_contents($logFile, "Redirection vers reservation_success.php?ticket=$res_num\n", FILE_APPEND);
    header("Location: reservation_success.php?ticket=" . $res_num);
    exit;
} catch (Exception $e) {
    file_put_contents($logFile, "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
    header("Location: reservation_choix.php?error=1&msg=" . urlencode($e->getMessage()));
    exit;
}
?>