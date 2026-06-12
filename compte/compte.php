<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: /auth/connexion.php");
    exit;
}

$cli_num  = $_SESSION['id'];
$host     = "harpagon.unicaen.fr";
$port     = "1521";
$sid      = "info";
$user     = "agile_5";
$password = "lemeilleurgroupe";
$dsn      = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$sid)))";

try {
    $conn = new PDO($dsn, $user, $password);

    $sql = "SELECT c.CLI_NOM, c.CLI_PRENOM, c.CLI_VILLE, c.CLI_TELEPHONE, c.CLI_COURRIEL,
                   c.CLI_NB_POINTS_EC, c.CLI_NB_POINTS_TOT,
                   TO_CHAR(c.CLI_DATE_CONNEC, 'DD/MM/YYYY') AS DATE_CONNEC,
                   t.TYP_NOM, t.TYP_PT_LIMITE, t.TYP_REDUC
            FROM VIK_CLIENT c
            JOIN VIK_TYPE_CLIENT t ON c.TYP_NUM = t.TYP_NUM
            WHERE c.CLI_NUM = :cli_num";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['cli_num' => $cli_num]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        header("Location: /auth/connexion.php");
        exit;
    }

    $sql2 = "SELECT r.RES_NUM, TO_CHAR(r.RES_DATE, 'DD/MM/YYYY') AS RES_DATE,
r.RES_PRIX_TOT, r.RES_NB_POINTS, c2.COM_NOM depart, c.COM_NOM arrivee
FROM VIK_RESERVATION r
JOIN VIK_COMMUNE c ON r.COM_CODE_INSEE_ARRIVEE = c.COM_CODE_INSEE
JOIN VIK_COMMUNE c2 ON r.COM_CODE_INSEE_DEPART = c2.COM_CODE_INSEE
WHERE r.CLI_NUM = :cli_num
ORDER BY r.RES_DATE DESC";

    $stmt2 = $conn->prepare($sql2);
    $stmt2->execute(['cli_num' => $cli_num]);
    $reservations = $stmt2->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Mon compte - Viking Transport</title>

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

        header.site-header {
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

        .badge-type {
            background-color: var(--viking-red);
            color: white;
            font-size: 0.85rem;
            padding: 5px 12px;
            border-radius: 20px;
        }

        .info-label {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--viking-dark-grey);
            opacity: 0.7;
        }

        .info-value {
            font-size: 1rem;
            color: #333;
        }

        .points-box {
            border-left: 4px solid var(--viking-red);
            padding-left: 1rem;
        }

        .points-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--viking-red);
            line-height: 1;
        }

        .points-label {
            font-size: 0.8rem;
            color: var(--viking-dark-grey);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .btn-deconnexion {
            background-color: transparent;
            border: 1.5px solid var(--viking-red);
            color: var(--viking-red);
            font-weight: 600;
        }

        .btn-deconnexion:hover {
            background-color: var(--viking-red);
            color: white;
        }

        .btn-outline-info {
            color: var(--viking-dark);
            border-color: var(--viking-dark);
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <?php include_once("../PHP/header.php") ?>

    <main class="container mb-5 mt-4">

        <section class="mb-4">
            <div class="p-4 custom-card shadow-lg">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 mb-0">Mon compte</h2>
                    <span class="badge-type"><i class="bi bi-star-fill me-1"></i><?= htmlspecialchars($client['TYP_NOM']) ?></span>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="info-label">Nom complet</div>
                        <div class="info-value"><?= htmlspecialchars(ucfirst($_SESSION["prenom"])) ?> <?= htmlspecialchars(strtoupper($_SESSION["nom"]));?> <?php $_GET['edit'] = 'nom' ?>
                        <a class="btn btn-outline-info" href="/compte/edit.php<?php echo "?edit=" . $_GET['edit'] ?>"><i class="bi bi-pencil"></i> Modifier</a></div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?= htmlspecialchars($client['CLI_COURRIEL'] ?: '—') ?><?php $_GET['edit'] = 'mail' ?>
                            <a class="btn btn-outline-info" href="/compte/edit.php<?php echo "?edit=" . $_GET['edit'] ?>"><i class="bi bi-pencil"></i> Modifier</a></div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Téléphone</div>
                        <div class="info-value"><?= htmlspecialchars($client['CLI_TELEPHONE'] ?: '—') ?><?php $_GET['edit'] = 'tel' ?>
                            <a class="btn btn-outline-info" href="/compte/edit.php<?php echo "?edit=" . $_GET['edit'] ?>"><i class="bi bi-pencil"></i> Modifier</a></div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Ville</div>
                        <div class="info-value"><?= htmlspecialchars($client['CLI_VILLE'] ?: '—') ?><?php $_GET['edit'] = 'ville' ?>
                            <a class="btn btn-outline-info" href="/compte/edit.php<?php echo "?edit=" . $_GET['edit'] ?>"><i class="bi bi-pencil"></i> Modifier</a></div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Dernière connexion</div>
                        <div class="info-value"><?= htmlspecialchars($client['DATE_CONNEC'] ?: '—') ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Réduction actuelle</div>
                        <div class="info-value"><?= htmlspecialchars($client['TYP_REDUC']) ?>%</div>
                    </div>
                </div>

                <a href="/auth/deconnexion.php" class="btn btn-deconnexion btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i> Se déconnecter
                </a>
            </div>
        </section>

        <section class="mb-4">
            <div class="p-4 custom-card shadow-lg">
                <h2 class="h4 mb-4">Points fidélité</h2>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="points-box">
                            <div class="points-value"><?= htmlspecialchars($client['CLI_NB_POINTS_EC']) ?></div>
                            <div class="points-label">Points en cours</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="points-box">
                            <div class="points-value"><?= htmlspecialchars($client['CLI_NB_POINTS_TOT']) ?></div>
                            <div class="points-label">Points cumulés total</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="points-box">
                            <div class="points-value"><?= htmlspecialchars($client['TYP_PT_LIMITE']) ?></div>
                            <div class="points-label">Seuil prochain niveau</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <div class="p-4 custom-card shadow-lg">
                <h2 class="h4 mb-4">Mes réservations</h2>

                <?php if (empty($reservations)): ?>
                    <p class="text-muted">Vous n'avez pas encore de réservation.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom table-striped table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>N° Réservation</th>
                                    <th>Date</th>
                                    <th>Points gagnés</th>
                                    <th>Départ</th>
                                    <th>Arrivée</th>
                                    <th>Prix total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reservations as $res): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($res['RES_NUM']) ?></strong></td>
                                        <td><?= htmlspecialchars($res['RES_DATE']) ?></td>
                                        <td><?= htmlspecialchars($res['RES_NB_POINTS']) ?> pts</td>
                                        <td><?= $res['DEPART'] ?></td>
                                        <td><?= $res['ARRIVEE'] ?></td>
                                        <td><strong><?= htmlspecialchars($res['RES_PRIX_TOT']) ?> €</strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <?php include_once "../PHP/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>