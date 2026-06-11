<?php
session_start();
if (!isset($_SESSION['client_info']) || !isset($_POST['trajet_json']) || !isset($_POST['date_voyage'])) {
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
    
    $client = $_SESSION['client_info'];
    $villesCodes = $client['villes'];
    $choix = json_decode($_POST['trajet_json'], true);
    $dateVoyage = $_POST['date_voyage'];
    
    // Récupération des arcs pour les étapes
    $arcs = [];
    $stmt = $conn->query("SELECT TRIM(LIG_NUM) AS LIGNE, TRIM(COM_CODE_INSEE_ARRET) AS DEPART, TRIM(COM_CODE_INSEE_SUIVANT) AS ARRIVEE, NOE_DISTANCE_PROCHAIN AS DISTANCE, NOE_DUREE_PROCHAIN AS DUREE, TO_CHAR(NOE_HEURE_PASSAGE, 'HH24:MI') AS HEURE FROM VIK_NOEUD");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dep = $row['DEPART'];
        $arr = $row['ARRIVEE'];
        if (!isset($arcs[$dep][$arr])) {
            $arcs[$dep][$arr] = [
                'ligne' => $row['LIGNE'],
                'dist' => (float)$row['DISTANCE'],
                'duree' => (float)$row['DUREE'],
                'heure' => $row['HEURE']
            ];
        }
    }
    
    // Génération du numéro de réservation
    $res_num = $conn->query("SELECT NVL(MAX(RES_NUM),0)+1 FROM VIK_RESERVATION")->fetchColumn();
    $depart_num = (int)$villesCodes[0];
    $arrivee_num = (int)end($villesCodes);
    
    // Insertion réservation principale
    // Note: RES_HEURE est de type VARCHAR2(5) → on stocke l'heure en chaîne de 5 caractères
    $sql = "INSERT INTO VIK_RESERVATION (CLI_NUM, RES_NUM, TAR_NUM_TRANCHE, RES_DATE, RES_NB_POINTS, RES_PRIX_TOT, CLI_NOM, CLI_PRENOM, RES_HEURE, COM_CODE_INSEE_DEPART, COM_CODE_INSEE_ARRIVEE) VALUES (0, :res, 1, TO_DATE(:dt,'YYYY-MM-DD'), :nb, :prix, :nom, :prenom, :heure, :dep, :arr)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':res' => $res_num,
        ':dt' => $dateVoyage,
        ':nb' => max(1, floor($choix['distance'])),
        ':prix' => $choix['prix'],
        ':nom' => $client['nom'],
        ':prenom' => $client['prenom'],
        ':heure' => substr($choix['heure_depart'], 0, 5), // On prend les 5 premiers caractères
        ':dep' => $depart_num,
        ':arr' => $arrivee_num
    ]);
    
    // Insertion des étapes
    $sqlEtape = "INSERT INTO VIK_ETAPE (CLI_NUM, RES_NUM, LIG_NUM, COM_CODE_INSEE_DEPART, COM_CODE_INSEE_ARRIVEE, ETA_DISTANCE, ETA_HEURE) VALUES (0, :res, :lig, :dep, :arr, :dist, TO_DATE(:h, 'HH24:MI'))";
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
        $stmtEtape->execute([
            ':res' => $res_num,
            ':lig' => $arc['ligne'],
            ':dep' => (int)$dep,
            ':arr' => (int)$arr,
            ':dist' => $arc['dist'],
            ':h' => $heureTronçon
        ]);
    }
    
    // Redirection vers la page de confirmation
    header("Location: reservation_success.php?ticket=" . $res_num);
    exit;
} catch (Exception $e) {
    header("Location: reservation_choix.php?error=1&msg=" . urlencode($e->getMessage()));
    exit;
}
?>