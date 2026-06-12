<?php
session_start();

if (!isset($_GET['ligne']) || trim($_GET['ligne']) === '') {
    header("Location: lignes.php");
    exit;
}

$ligne_id = trim($_GET['ligne']);

$host = "harpagon.unicaen.fr";
$port = "1521";
$sid = "info";
$user = "agile_5";
$password = "lemeilleurgroupe";

$dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$sid)))";

try {
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 🔥 toutes les étapes de la ligne
    $sql = "
        SELECT
            COM_CODE_INSEE_ARRET,
            COM_CODE_INSEE_SUIVANT,
            TO_CHAR(NOE_HEURE_PASSAGE,'HH24:MI') AS HEURE
        FROM VIK_NOEUD
        WHERE TRIM(LIG_NUM) = :ligne
        ORDER BY NOE_HEURE_PASSAGE
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute(['ligne' => $ligne_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        header("Location: lignes.php");
        exit;
    }

    // 🔥 départ = premier arrêt logique
    $start = trim($rows[0]['COM_CODE_INSEE_ARRET']);

    // 🔥 calcul terminus (VILLE ARRIVÉE RÉELLE)
    $departCandidates = [];
    $arrivees = [];

    foreach ($rows as $r) {
        $from = trim($r['COM_CODE_INSEE_ARRET']);
        $to   = trim($r['COM_CODE_INSEE_SUIVANT']);

        $departCandidates[$from] = true;
        $arrivees[$to] = true;
    }

    $end = null;
    foreach ($arrivees as $node => $_) {
        if (!isset($departCandidates[$node])) {
            $end = $node;
            break;
        }
    }

    // 🔥 horaires uniquement du premier arrêt
    $heures = [];

    foreach ($rows as $r) {
        if (trim($r['COM_CODE_INSEE_ARRET']) === $start) {
            $heures[] = $r['HEURE'];
        }
    }

    $heures = array_values(array_unique($heures));
    sort($heures);

    // 🔥 ville départ
    $stmt = $conn->prepare("
        SELECT COM_NOM
        FROM VIK_COMMUNE
        WHERE COM_CODE_INSEE = :code
    ");

    $stmt->execute(['code' => $start]);
    $ville_depart = $stmt->fetchColumn();

    if (!$ville_depart) {
        $ville_depart = $start;
    }

    // 🔥 ville arrivée (TERMINUS)
    $stmt->execute(['code' => $end]);
    $ville_arrivee = $stmt->fetchColumn();

    if (!$ville_arrivee) {
        $ville_arrivee = $end;
    }
} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Horaires ligne <?= htmlspecialchars($ligne_id) ?> - Viking Transport</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --viking-red: #C62828;
            --viking-dark-red: #9b1e1e;
            --viking-dark-grey: #706767;
            --viking-bg-grey: #8A8181;
            --viking-light-grey: #E5E8E8;
            --viking-white: #FFFFFF;
            --viking-dark: #212121;
        }

        body {
            background-color: #f8f9fa;
            color: var(--viking-dark-grey);
            font-family: system-ui, -apple-system, sans-serif;
        }

        header {
            background-color: var(--viking-white);
            border-bottom: 5px solid var(--viking-red);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .nav-link {
            color: var(--viking-red);
        }

        .nav-link:hover {
            color: var(--viking-dark-red);
        }

        .nav-link.active {
            color: var(--viking-dark-red) !important;
            font-weight: bold;
        }

        .btn-outline-primary {
            color: var(--viking-red);
            border-color: var(--viking-red);
            --bs-btn-active-bg: #9b1e1e;
            --bs-btn-active-color: var(--viking-white);
            --bs-btn-active-border-color: #9b1e1e;
            --bs-btn-focus-shadow-rgb: 198, 40, 40;
            --bs-btn-active-shadow: none;
        }

        .btn-outline-primary:hover {
            color: var(--viking-white);
            border-color: var(--viking-red);
            background-color: var(--viking-red);
        }

        .custom-card {
            background-color: var(--viking-white);
            border: none;
            border-radius: 10px;
        }

        h2.h4 {
            color: var(--viking-dark);
            font-weight: 700;
            position: relative;
            padding-left: 14px;
        }

        h2.h4::before {
            content: "";
            position: absolute;
            left: 0;
            top: 15%;
            height: 70%;
            width: 4px;
            background-color: var(--viking-red);
            border-radius: 2px;
        }

        .table-custom {
            --bs-table-bg: transparent;
            --bs-table-striped-bg: #F9FAFA;
            --bs-table-hover-bg: #F2F4F4;
            color: var(--viking-dark-grey);
        }

        .table-custom thead th {
            border-bottom: 3px solid var(--viking-red);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 14px 16px;
        }

        .table-custom tbody td {
            padding: 12px 16px;
            border-color: var(--viking-light-grey);
            vertical-align: middle;
        }

        .btn-reserver {
            background-color: var(--viking-red);
            border: none;
            color: var(--viking-white);
            font-weight: 600;
        }

        .btn-reserver:hover {
            background-color: var(--viking-dark-red);
            color: var(--viking-white);
        }

        

        .nav-col {
            flex: 1;
            display: flex;
            align-items: center;
        }

        .nav-col.nav-center {
            justify-content: center;
            gap: 2rem;
        }

        .nav-col.nav-right {
            justify-content: flex-end;
        }
    </style>
</head>

<body>

    <?php include_once("../PHP/header.php") ?>


    <main class="container mb-5">

        <section class="mb-4 mt-4">
            <div class="p-4 custom-card shadow-lg">
                <h2 class="h4 mb-1">Ligne <?= htmlspecialchars($ligne_id) ?></h2>
                <p class="text-muted mb-0">Horaires des trajets disponibles sur cette ligne.</p>
            </div>
        </section>

        <section class="mb-4">
            <div class="p-4 custom-card shadow-lg">
                <h2 class="h4 mb-4">Horaires</h2>

                <div class="table-responsive">
                    <table class="table table-custom table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th><i class="bi bi-clock me-1"></i> Heure</th>
                                <th><i class="bi bi-geo-alt me-1"></i> Départ</th>
                                <th><i class="bi bi-geo-alt-fill me-1"></i> Arrivée</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($heures as $h): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($h) ?></strong></td>
                                    <td><?= htmlspecialchars($ville_depart) ?></td>
                                    <td><?= htmlspecialchars($ville_arrivee) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <div class="d-flex gap-3">
            <a href="lignes.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-1"></i> Retour aux lignes
            </a>
            <!-- Modification : redirection vers la réservation manuelle -->
            <a href="reservation_trajet_manuel.php?ligne=<?= urlencode($ligne_id) ?>" class="btn btn-reserver">
                <i class="bi bi-ticket-perforated me-1"></i> Réserver sur cette ligne
            </a>
        </div>

    </main>

    <?php include_once "../PHP/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>