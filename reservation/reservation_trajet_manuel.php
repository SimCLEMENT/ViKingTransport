<?php
session_start();
$host = "harpagon.unicaen.fr";
$port = "1521";
$sid = "info";
$user = "agile_5";
$password = "lemeilleurgroupe";
$dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$sid)))";

try {
    $conn = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Récupération des lignes pour la première étape
    $lignes = $conn->query("SELECT TRIM(LIG_NUM) AS LIGNE FROM VIK_LIGNE ORDER BY LIGNE")->fetchAll(PDO::FETCH_COLUMN);

    $etape = 'choix_ligne';
    $ligne_id = $_GET['ligne'] ?? $_POST['ligne'] ?? '';
    if (!empty($ligne_id)) {
        $stmt = $conn->prepare("SELECT 1 FROM VIK_LIGNE WHERE TRIM(LIG_NUM)=:ligne");
        $stmt->execute(['ligne' => $ligne_id]);
        if ($stmt->fetch()) {
            $etape = 'formulaire';
        } else {
            $error_message = "Ligne invalide.";
            $etape = 'choix_ligne';
        }
    }

    if ($etape == 'choix_ligne') {
        // Affichage du formulaire de choix de ligne
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Choix manuel - Ligne</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
            <style>
                body { background-color: #8A8181; font-family: system-ui; }
                .btn-primary-custom { background-color: #C62828; color: white; border: none; }
                .btn-primary-custom:hover { background-color: #9b1e1e; }
            </style>
        </head>
        <body class="d-flex align-items-center min-vh-100">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="card shadow-lg border-0 p-5 rounded-4 bg-white">
                            <h3 class="fw-bold mb-4">Choix manuel du trajet</h3>
                            <?php if (!empty($error_message)): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                            <?php endif; ?>
                            <form method="GET" action="reservation_trajet_manuel.php">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Sélectionnez une ligne</label>
                                    <select name="ligne" class="form-select" required>
                                        <option value="">Choisir une ligne...</option>
                                        <?php foreach ($lignes as $lig): ?>
                                            <option value="<?= htmlspecialchars($lig) ?>">Ligne <?= htmlspecialchars($lig) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary-custom w-100 py-2">Continuer</button>
                                <a href="reservation_trajet.php" class="btn btn-outline-secondary w-100 mt-2">Retour</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    // ---------- Formulaire de réservation pour la ligne choisie ----------
    // Récupération des nœuds de la ligne
    $stmt = $conn->prepare("
        SELECT
            COM_CODE_INSEE_ARRET,
            COM_CODE_INSEE_SUIVANT,
            NOE_DISTANCE_PROCHAIN,
            TO_CHAR(NOE_HEURE_PASSAGE, 'HH24:MI') AS HEURE
        FROM VIK_NOEUD
        WHERE TRIM(LIG_NUM) = :ligne
        ORDER BY NOE_HEURE_PASSAGE
    ");
    $stmt->execute(['ligne' => $ligne_id]);
    $noeuds = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $graph = [];
    $incoming = [];
    $heures = [];
    foreach ($noeuds as $n) {
        $from = trim($n['COM_CODE_INSEE_ARRET']);
        $to   = trim($n['COM_CODE_INSEE_SUIVANT']);
        $h    = $n['HEURE'];
        if (!isset($graph[$from])) {
            $graph[$from] = ['next' => $to, 'dist' => (float)$n['NOE_DISTANCE_PROCHAIN']];
            $incoming[$to] = true;
        }
        if (!isset($heures[$from])) $heures[$from] = [];
        $heures[$from][$h] = true;
    }
    foreach ($heures as $k => $v) {
        $heures[$k] = array_keys($v);
        sort($heures[$k]);
    }

    // Déterminer l'ordre des gares sur la ligne
    $start = null;
    foreach ($graph as $node => $v) {
        if (!isset($incoming[$node])) { $start = $node; break; }
    }
    $ordre = [];
    $current = $start;
    while (isset($graph[$current])) {
        $ordre[] = $current;
        $current = $graph[$current]['next'];
    }
    if ($current) $ordre[] = $current;
    $ordre_depart = array_slice($ordre, 0, -1);

    // Récupérer les noms des communes
    $communes = [];
    if (!empty($ordre)) {
        $in = str_repeat('?,', count($ordre)-1) . '?';
        $stmt = $conn->prepare("SELECT COM_CODE_INSEE, COM_NOM FROM VIK_COMMUNE WHERE COM_CODE_INSEE IN ($in)");
        $stmt->execute($ordre);
        $communes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // Traitement du formulaire de réservation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserver'])) {
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $date_form = $_POST['res_date'];
        $depart = $_POST['depart'];
        $arrivee = $_POST['arrivee'];
        $horaire = $_POST['horaire'];

        if ($depart == $arrivee) throw new Exception("Départ = arrivée interdit.");
        if (!isset($heures[$depart]) || !in_array($horaire, $heures[$depart])) throw new Exception("Horaire invalide.");

        // Calcul de la distance
        $distance = 0;
        $current = $depart;
        $ok = false;
        while (isset($graph[$current])) {
            $distance += $graph[$current]['dist'];
            if ($graph[$current]['next'] == $arrivee) { $ok = true; break; }
            $current = $graph[$current]['next'];
            if ($current == $arrivee) { $ok = true; break; }
        }
        if (!$ok) throw new Exception("Trajet invalide.");

        // Récupération du tarif
        $stmt = $conn->prepare("SELECT TAR_NUM_TRANCHE, TAR_PRIX FROM VIK_TARIF WHERE :d BETWEEN TAR_MIN_DIST AND TAR_MAX_DIST");
        $stmt->execute(['d' => $distance]);
        $tarif = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tarif) throw new Exception("Tarif introuvable.");

        // Génération du numéro de réservation
        $res_num = $conn->query("SELECT NVL(MAX(RES_NUM),0)+1 FROM VIK_RESERVATION")->fetchColumn();

        // Insertion dans VIK_RESERVATION
        $sql = "INSERT INTO VIK_RESERVATION (CLI_NUM, RES_NUM, TAR_NUM_TRANCHE, RES_DATE, RES_NB_POINTS, RES_PRIX_TOT, CLI_NOM, CLI_PRENOM, RES_HEURE, COM_CODE_INSEE_DEPART, COM_CODE_INSEE_ARRIVEE) VALUES (0, :res_num, :tranche, TO_DATE(:date_form,'YYYY-MM-DD'), :nb_points, :prix, :nom, :prenom, :heure, :dep, :arr)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':res_num' => $res_num,
            ':tranche' => $tarif['TAR_NUM_TRANCHE'],
            ':date_form' => $date_form,
            ':nb_points' => max(1, floor($distance / 10)),
            ':prix' => $tarif['TAR_PRIX'],
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':heure' => $horaire,
            ':dep' => $depart,
            ':arr' => $arrivee
        ]);

        // Insertion dans VIK_ETAPE (un seul tronçon)
        $sqlEtape = "INSERT INTO VIK_ETAPE (CLI_NUM, RES_NUM, LIG_NUM, COM_CODE_INSEE_DEPART, COM_CODE_INSEE_ARRIVEE, ETA_DISTANCE, ETA_HEURE) VALUES (0, :res, :lig, :dep, :arr, :dist, TO_DATE(:h, 'HH24:MI'))";
        $stmtEtape = $conn->prepare($sqlEtape);
        $stmtEtape->execute([
            ':res' => $res_num,
            ':lig' => $ligne_id,
            ':dep' => $depart,
            ':arr' => $arrivee,
            ':dist' => $distance,
            ':h' => $horaire
        ]);

        // Redirection vers la page de confirmation
        header("Location: reservation_success.php?ticket=$res_num");
        exit;
    }

} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réservation manuelle - Ligne <?= htmlspecialchars($ligne_id) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #8A8181; font-family: system-ui; }
        .custom-card { background-color: white; border-radius: 10px; }
        .btn-primary-custom { background-color: #C62828; color: white; }
        .btn-primary-custom:hover { background-color: #9b1e1e; }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="custom-card shadow-lg p-5">
                    <h2 class="h4 mb-4">Réservation manuelle - Ligne <?= htmlspecialchars($ligne_id) ?></h2>
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nom</label>
                                <input type="text" class="form-control" name="nom" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Prénom</label>
                                <input type="text" class="form-control" name="prenom" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Date de réservation</label>
                            <input type="date" class="form-control" name="res_date" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Station de départ</label>
                                <select class="form-select" name="depart" id="depart" required>
                                    <?php foreach ($ordre_depart as $o): ?>
                                        <option value="<?= htmlspecialchars(trim($o)) ?>"><?= htmlspecialchars($communes[$o] ?? $o) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Station d'arrivée</label>
                                <select class="form-select" name="arrivee" id="arrivee" required disabled>
                                    <option value="">Sélectionnez d'abord un départ</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Horaire de passage</label>
                            <select class="form-select" name="horaire" id="horaire" required></select>
                        </div>
                        <button type="submit" name="reserver" class="btn btn-primary-custom w-100 py-2">Confirmer la réservation</button>
                        <a href="reservation_trajet_manuel.php" class="btn btn-outline-secondary w-100 mt-2">Changer de ligne</a>
                        <a href="reservation_trajet.php" class="btn btn-link w-100 mt-2">Retour à la recherche automatique</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        const heures = <?= json_encode($heures) ?>;
        const ordreGlobal = <?= json_encode(array_map('trim', $ordre)) ?>;
        const communes = <?= json_encode($communes) ?>;

        const depart = document.getElementById('depart');
        const arrivee = document.getElementById('arrivee');
        const horaire = document.getElementById('horaire');

        function updateHeures() {
            const val = depart.value;
            horaire.innerHTML = "";
            const list = heures[val] || [];
            if (list.length === 0) {
                horaire.innerHTML = '<option value="">Aucun horaire disponible</option>';
                return;
            }
            for (const h of list) {
                const opt = document.createElement("option");
                opt.value = h;
                opt.textContent = h;
                horaire.appendChild(opt);
            }
        }

        function updateArrivee() {
            const depVal = depart.value;
            arrivee.innerHTML = "";
            const depIndex = ordreGlobal.indexOf(depVal);
            if (depIndex !== -1 && depIndex < ordreGlobal.length - 1) {
                arrivee.disabled = false;
                for (let i = depIndex + 1; i < ordreGlobal.length; i++) {
                    const code = ordreGlobal[i];
                    const nom = communes[code] || code;
                    const opt = document.createElement("option");
                    opt.value = code;
                    opt.textContent = nom;
                    arrivee.appendChild(opt);
                }
            } else {
                arrivee.disabled = true;
                arrivee.innerHTML = '<option value="">Sélectionnez d\'abord un départ valide</option>';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateHeures();
            updateArrivee();
        });
        depart.addEventListener('change', () => {
            updateHeures();
            updateArrivee();
        });
    </script>
</body>
</html>