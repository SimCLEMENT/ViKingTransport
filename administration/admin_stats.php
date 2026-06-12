<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['id'] != 200) {
    header("Location: /auth/connexion.php");
    exit;
}

$host     = "harpagon.unicaen.fr";
$port     = "1521";
$sid      = "info";
$user     = "agile_5";
$password = "lemeilleurgroupe";
$dsn      = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$sid)))";

try {
    $conn = new PDO($dsn, $user, $password);

    // Répartition des clients par type
    $sql1 = "SELECT t.TYP_NOM, COUNT(c.CLI_NUM) AS NB_CLIENTS
             FROM VIK_TYPE_CLIENT t
             LEFT JOIN VIK_CLIENT c ON t.TYP_NUM = c.TYP_NUM AND c.CLI_NUM != 0 AND c.CLI_NUM != 200
             WHERE t.TYP_NUM != 6
             GROUP BY t.TYP_NOM
             ORDER BY NB_CLIENTS DESC";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->execute();
    $repartition = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // Top 5 points en cours
    $sql2 = "SELECT c.CLI_NOM, c.CLI_PRENOM, c.CLI_NB_POINTS_EC, t.TYP_NOM
             FROM VIK_CLIENT c
             JOIN VIK_TYPE_CLIENT t ON c.TYP_NUM = t.TYP_NUM
             WHERE c.CLI_NUM != 0 AND c.CLI_NUM != 200
             ORDER BY c.CLI_NB_POINTS_EC DESC
             FETCH FIRST 5 ROWS ONLY";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->execute();
    $top_ec = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Top 5 points cumulés
    $sql3 = "SELECT c.CLI_NOM, c.CLI_PRENOM, c.CLI_NB_POINTS_TOT, t.TYP_NOM
             FROM VIK_CLIENT c
             JOIN VIK_TYPE_CLIENT t ON c.TYP_NUM = t.TYP_NUM
             WHERE c.CLI_NUM != 0 AND c.CLI_NUM != 200
             ORDER BY c.CLI_NB_POINTS_TOT DESC
             FETCH FIRST 5 ROWS ONLY";
    $stmt3 = $conn->prepare($sql3);
    $stmt3->execute();
    $top_tot = $stmt3->fetchAll(PDO::FETCH_ASSOC);

    // Moyenne des points par type
    $sql4 = "SELECT t.TYP_NOM,
                    ROUND(AVG(c.CLI_NB_POINTS_EC), 1) AS MOY_EC,
                    ROUND(AVG(c.CLI_NB_POINTS_TOT), 1) AS MOY_TOT
             FROM VIK_TYPE_CLIENT t
             LEFT JOIN VIK_CLIENT c ON t.TYP_NUM = c.TYP_NUM AND c.CLI_NUM != 0 AND c.CLI_NUM != 200
             WHERE t.TYP_NUM != 6
             GROUP BY t.TYP_NOM
             ORDER BY MOY_TOT DESC";
    $stmt4 = $conn->prepare($sql4);
    $stmt4->execute();
    $moyennes = $stmt4->fetchAll(PDO::FETCH_ASSOC);

    // Chiffre d'affaires et réservations
    $sql5 = "SELECT COUNT(RES_NUM) AS NB_RES,
                    ROUND(SUM(RES_PRIX_TOT), 2) AS CA_TOTAL,
                    ROUND(AVG(RES_PRIX_TOT), 2) AS CA_MOYEN,
                    SUM(RES_NB_POINTS) AS POINTS_DISTRIBUES
             FROM VIK_RESERVATION";
    $stmt5 = $conn->prepare($sql5);
    $stmt5->execute();
    $global = $stmt5->fetch(PDO::FETCH_ASSOC);

    $conn = null;

} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Statistiques - Viking Transport</title>

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
            background-color: var(--viking-dark);
            color: var(--viking-dark-grey);
            font-family: system-ui, -apple-system, sans-serif;
        }

        header {
            background-color: var(--viking-white);
            border-bottom: 5px solid var(--viking-red);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .nav-link { color: var(--viking-red); }
        .nav-link:hover { color: var(--viking-dark-red); }
        .nav-link.active { color: var(--viking-dark-red) !important; font-weight: bold; }

        .btn-outline-primary {
            color: var(--viking-red);
            border-color: var(--viking-red);
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
            color: var(--viking-dark-grey);
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

        .table-custom thead {
            background-color: var(--viking-dark-grey);
            color: var(--viking-white);
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

        .stat-box {
            border-left: 4px solid var(--viking-red);
            padding-left: 1rem;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--viking-red);
            line-height: 1;
        }

        .stat-label {
            font-size: 0.78rem;
            color: var(--viking-dark-grey);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 3px;
        }

        .badge-type {
            background-color: var(--viking-red);
            color: white;
            font-size: 0.75rem;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .rank {
            font-size: 1rem;
            font-weight: 700;
            color: var(--viking-red);
        }

        /* Navbar */
        .site-header {
            background-color: var(--viking-white);
            border-bottom: 5px solid var(--viking-red);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
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
        .badge-type {
            background-color: var(--viking-red);
            color: white;
            font-size: 0.75rem;
            padding: 3px 10px;
            border-radius: 20px;
        }

    </style>
</head>
<body>

    <?php include_once("../PHP/header.php") ?>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> mb-4"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <main class="container mb-5 mt-4">

        <!-- Titre -->
        <section class="mb-4">
            <div class="p-4 custom-card shadow-lg d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1">Statistiques</h2>
                    <p class="text-muted mb-0">Vue d'ensemble de l'activité VikingTransport.</p>
                </div>
                <a href="admin.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Retour
                </a>
            </div>
        </section>

        <!-- Chiffres globaux -->
        <section class="mb-4">
            <div class="p-4 custom-card shadow-lg">
                <h2 class="h4 mb-4">Chiffres globaux</h2>
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="stat-box">
                            <div class="stat-value"><?= htmlspecialchars($global['NB_RES'] ?? '0') ?></div>
                            <div class="stat-label">Réservations totales</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box">
                            <div class="stat-value"><?= htmlspecialchars($global['CA_TOTAL'] ?? '0') ?> €</div>
                            <div class="stat-label">Chiffre d'affaires</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box">
                            <div class="stat-value"><?= htmlspecialchars($global['CA_MOYEN'] ?? '0') ?> €</div>
                            <div class="stat-label">Prix moyen / réservation</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box">
                            <div class="stat-value"><?= htmlspecialchars($global['POINTS_DISTRIBUES'] ?? '0') ?></div>
                            <div class="stat-label">Points distribués</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Répartition par type -->
        <section class="mb-4">
            <div class="p-4 custom-card shadow-lg">
                <h2 class="h4 mb-4">Répartition des clients par type</h2>
                <div class="table-responsive">
                    <table class="table table-custom table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Nombre de clients</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($repartition as $r): ?>
                                <tr>
                                    <td><span class="badge-type"><?= htmlspecialchars($r['TYP_NOM']) ?></span></td>
                                    <td><strong><?= htmlspecialchars($r['NB_CLIENTS']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Top 5 points en cours + cumulés -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="p-4 custom-card shadow-lg h-100">
                    <h2 class="h4 mb-4">Top 5 — Points en cours</h2>
                    <div class="table-responsive">
                        <table class="table table-custom table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Client</th>
                                    <th>Type</th>
                                    <th>Points EC</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_ec as $i => $c): ?>
                                    <tr>
                                        <td><span class="rank"><?= $i + 1 ?></span></td>
                                        <td><?= htmlspecialchars($c['CLI_PRENOM']) ?> <?= htmlspecialchars($c['CLI_NOM']) ?></td>
                                        <td><span class="badge-type"><?= htmlspecialchars($c['TYP_NOM']) ?></span></td>
                                        <td><strong><?= htmlspecialchars($c['CLI_NB_POINTS_EC']) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-4 custom-card shadow-lg h-100">
                    <h2 class="h4 mb-4">Top 5 — Points cumulés</h2>
                    <div class="table-responsive">
                        <table class="table table-custom table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Client</th>
                                    <th>Type</th>
                                    <th>Points Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_tot as $i => $c): ?>
                                    <tr>
                                        <td><span class="rank"><?= $i + 1 ?></span></td>
                                        <td><?= htmlspecialchars($c['CLI_PRENOM']) ?> <?= htmlspecialchars($c['CLI_NOM']) ?></td>
                                        <td><span class="badge-type"><?= htmlspecialchars($c['TYP_NOM']) ?></span></td>
                                        <td><strong><?= htmlspecialchars($c['CLI_NB_POINTS_TOT']) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Moyenne par type -->
        <section>
            <div class="p-4 custom-card shadow-lg">
                <h2 class="h4 mb-4">Moyenne des points par type de client</h2>
                <div class="table-responsive">
                    <table class="table table-custom table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Moyenne points en cours</th>
                                <th>Moyenne points cumulés</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($moyennes as $m): ?>
                                <tr>
                                    <td><span class="badge-type"><?= htmlspecialchars($m['TYP_NOM']) ?></span></td>
                                    <td><?= htmlspecialchars($m['MOY_EC'] ?? '0') ?></td>
                                    <td><strong><?= htmlspecialchars($m['MOY_TOT'] ?? '0') ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </main>


    <?php include_once("../PHP/footer.php"); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>