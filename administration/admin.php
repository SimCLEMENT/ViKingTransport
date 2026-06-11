<?php
session_start();

if (!isset($_SESSION['id']) || ($_SESSION['id'] != 200 && $_SESSION['type'] != 200)) {
    header("Location: /auth/connexion.php");
    exit;
}

$host     = "harpagon.unicaen.fr";
$port     = "1521";
$sid      = "info";
$user     = "agile_5";
$password = "lemeilleurgroupe";
$dsn      = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$sid)))";

$recherche = null;

if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
    $recherche = htmlspecialchars($_GET['query']);
}

$message = null;
$message_type = 'info';

$allowed_sorts = [
    'num'        => 'c.CLI_NUM',
    'nom'        => 'c.CLI_NOM',
    'prenom'     => 'c.CLI_PRENOM',
    'ville'      => 'c.CLI_VILLE',
    'telephone'  => 'c.CLI_TELEPHONE',
    'courriel'   => 'c.CLI_COURRIEL',
    'points_ec'  => 'c.CLI_NB_POINTS_EC',
    'points_tot' => 'c.CLI_NB_POINTS_TOT',
    'date'       => 'c.CLI_DATE_CONNEC'
];

$sort_by = 'c.CLI_NUM';
if (isset($_GET['sort']) && array_key_exists($_GET['sort'], $allowed_sorts)) {
    $sort_by = $allowed_sorts[$_GET['sort']];
}

$order = 'ASC';
if (isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC') {
    $order = 'DESC';
}

$next_order = ($order === 'ASC') ? 'DESC' : 'ASC';

function getSortUrl($column, $next_order, $recherche) {
    $url = "?sort=" . $column . "&order=" . $next_order;
    if ($recherche !== null) {
        $url .= "&query=" . urlencode($recherche);
    }
    return $url . "#clients";
}

try {
    $conn = new PDO($dsn, $user, $password);

    $sql = "SELECT c.CLI_NUM, c.CLI_NOM, c.CLI_PRENOM, c.CLI_VILLE,
                   c.CLI_TELEPHONE, c.CLI_COURRIEL, vt.TYP_NOM,
                   c.CLI_NB_POINTS_EC, c.CLI_NB_POINTS_TOT,
                   TO_CHAR(c.CLI_DATE_CONNEC, 'DD/MM/YYYY') AS DATE_CONNEC
            FROM VIK_CLIENT c
            JOIN VIK_TYPE_CLIENT vt ON vt.TYP_NUM = c.TYP_NUM
            WHERE c.CLI_NUM != 0
            AND c.TYP_NUM != 6";

    if ($recherche !== null) {
        $sql .= " AND (UPPER(c.CLI_NOM) LIKE UPPER(:search_partial)
                 OR UPPER(c.CLI_PRENOM) LIKE UPPER(:search_partial)
                 OR UPPER(c.CLI_VILLE) LIKE UPPER(:search_partial)
                 OR UPPER(vt.TYP_NOM) = UPPER(:search_exact)
                 OR UPPER(c.CLI_COURRIEL) = UPPER(:search_exact)
                 OR UPPER(TO_CHAR(c.CLI_DATE_CONNEC, 'DD/MM/YYYY')) = UPPER(:search_exact)";

        if (is_numeric($recherche)) {
            $sql .= " OR c.CLI_NUM = :search_exact
                      OR c.CLI_NB_POINTS_TOT = :search_exact
                      OR c.CLI_TELEPHONE = :search_exact";
        }

        $sql .= ")";
    }

    $sql .= " ORDER BY " . $sort_by . " " . $order;

    $stmt = $conn->prepare($sql);
    if ($recherche !== null) {
        $stmt->execute([
            'search_partial' => '%' . $recherche . '%',
            'search_exact'   => $recherche
        ]);
    } else {
        $stmt->execute();
    }

    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Administration - Viking Transport</title>

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
        }

        body {
            background-color: var(--viking-bg-grey);
            color: var(--viking-dark-grey);
            font-family: system-ui, -apple-system, sans-serif;
        }

        header.site-header {
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

        .table-custom thead {
            background-color: var(--viking-dark-grey);
            color: var(--viking-white);
        }

        .table-custom thead th {
            border-bottom: 3px solid var(--viking-red);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.82rem;
            letter-spacing: 0.5px;
            padding: 12px 14px;
        }

        .table-custom thead th a {
            color: var(--viking-white) !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .table-custom thead th a:hover {
            color: var(--viking-light-grey) !important;
        }

        .table-custom tbody tr {
            cursor: pointer;
        }

        .table-custom tbody tr:hover td {
            background-color: #fdeaea;
        }

        .table-custom tbody td {
            padding: 10px 14px;
            border-color: var(--viking-light-grey);
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .badge-type {
            background-color: var(--viking-red);
            color: white;
            font-size: 0.75rem;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .btn-supprimer:hover {
            background-color: var(--viking-red);
            color: white;
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">
    <?php include_once("../PHP/header.php") ?>

    <main class="container my-5 mt-4">

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> mb-4"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="mb-4">
            <div class="p-4 custom-card shadow-lg">
                <h2 class="h4 mb-1">Espace Administration</h2>
                <p class="text-muted mb-0">Cliquez sur un client pour voir ses détails.</p>
            </div>
        </section>

        <section id="clients">
            <div class="p-4 custom-card shadow-lg">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 mb-0">Clients (<?= count($clients) ?>)</h2>
                    <a href="/administration/admin_stats.php" class="btn btn-sm text-white" style="background-color:#C62828"><i class="bi bi-bar-chart me-1"></i> Statistiques</a>
                </div>

                <form action="admin.php" class="d-flex justify-content-between align-items-center" method="GET">
                    <input type="text" class="form-control me-2" name="query" placeholder="Rechercher un client..." value="<?= isset($_GET['query']) ? htmlspecialchars($_GET['query']) : '' ?>" required>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-danger" type="submit">Rechercher</button>
                        <?php if (isset($_GET['query']) || isset($_GET['sort'])) : ?>
                            <a href="admin.php#clients" class="btn btn-secondary">Réinitialiser</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive mt-3">
                    <table class="table table-custom table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th><a href="<?= getSortUrl('num', $next_order, $recherche) ?>">N° <i class="bi bi-arrow-down-up small"></i></a></th>
                                <th><a href="<?= getSortUrl('nom', $next_order, $recherche) ?>">Nom <i class="bi bi-arrow-down-up small"></i></a></th>
                                <th><a href="<?= getSortUrl('prenom', $next_order, $recherche) ?>">Prénom <i class="bi bi-arrow-down-up small"></i></a></th>
                                <th><a href="<?= getSortUrl('ville', $next_order, $recherche) ?>">Ville <i class="bi bi-arrow-down-up small"></i></a></th>
                                <th><a href="<?= getSortUrl('telephone', $next_order, $recherche) ?>">Téléphone <i class="bi bi-arrow-down-up small"></i></a></th>
                                <th><a href="<?= getSortUrl('courriel', $next_order, $recherche) ?>">Email <i class="bi bi-arrow-down-up small"></i></a></th>
                                <th class="text-white text-transform-uppercase" style="padding: 12px 14px; font-size: 0.82rem; letter-spacing: 0.5px;">Type</th>
                                <th><a href="<?= getSortUrl('points_ec', $next_order, $recherche) ?>">Points EC <i class="bi bi-arrow-down-up small"></i></a></th>
                                <th><a href="<?= getSortUrl('points_tot', $next_order, $recherche) ?>">Points Total <i class="bi bi-arrow-down-up small"></i></a></th>
                                <th><a href="<?= getSortUrl('date', $next_order, $recherche) ?>">Dernière connexion <i class="bi bi-arrow-down-up small"></i></a></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $c): ?>
                                <tr onclick="window.location='admin_client.php?id=<?= htmlspecialchars($c['CLI_NUM']) ?>'">
                                    <td><strong><?= htmlspecialchars($c['CLI_NUM']) ?></strong></td>
                                    <td><?= htmlspecialchars($c['CLI_NOM']) ?></td>
                                    <td><?= htmlspecialchars($c['CLI_PRENOM']) ?></td>
                                    <td><?= htmlspecialchars($c['CLI_VILLE'] ?: '—') ?></td>
                                    <td><?= htmlspecialchars($c['CLI_TELEPHONE'] ?: '—') ?></td>
                                    <td><?= htmlspecialchars($c['CLI_COURRIEL'] ?: '—') ?></td>
                                    <td><span class="badge-type"><?= htmlspecialchars($c['TYP_NOM']) ?></span></td>
                                    <td><?= htmlspecialchars($c['CLI_NB_POINTS_EC']) ?></td>
                                    <td><?= htmlspecialchars($c['CLI_NB_POINTS_TOT']) ?></td>
                                    <td><?= htmlspecialchars($c['DATE_CONNEC'] ?: '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <?php
        // Tri lignes
        $allowed_sorts_lignes = [
            'num'    => 'l.LIG_NUM',
            'depart' => 'c1.COM_NOM',
            'arrivee' => 'c2.COM_NOM'
        ];

        $sort_by_l = 'l.LIG_NUM';
        if (isset($_GET['sort_l']) && array_key_exists($_GET['sort_l'], $allowed_sorts_lignes)) {
            $sort_by_l = $allowed_sorts_lignes[$_GET['sort_l']];
        }

        $order_l = 'ASC';
        if (isset($_GET['order_l']) && strtoupper($_GET['order_l']) === 'DESC') {
            $order_l = 'DESC';
        }

        $next_order_l = ($order_l === 'ASC') ? 'DESC' : 'ASC';

        $recherche_l = null;
        if (isset($_GET['query_l']) && !empty(trim($_GET['query_l']))) {
            $recherche_l = htmlspecialchars($_GET['query_l']);
        }

        function getSortUrlL($column, $next_order_l, $recherche_l)
        {
            $url = "?sort_l=" . $column . "&order_l=" . $next_order_l;
            if ($recherche_l !== null) {
                $url .= "&query_l=" . urlencode($recherche_l);
            }
            return $url . "#lignes";
        }

        $conn2 = new PDO($dsn, $user, $password);
        $sql_lignes = "SELECT l.LIG_NUM, c1.COM_NOM AS VILLE_DEP, c2.COM_NOM AS VILLE_ARR
               FROM VIK_LIGNE l
               JOIN VIK_COMMUNE c1 ON l.COM_CODE_INSEE_DEBU = c1.COM_CODE_INSEE
               JOIN VIK_COMMUNE c2 ON l.COM_CODE_INSEE_TERM = c2.COM_CODE_INSEE";

        if ($recherche_l !== null) {
            $sql_lignes .= " WHERE (UPPER(l.LIG_NUM) LIKE UPPER(:search_l)
                     OR UPPER(c1.COM_NOM) LIKE UPPER(:search_l)
                     OR UPPER(c2.COM_NOM) LIKE UPPER(:search_l))";
        }

        $sql_lignes .= " ORDER BY " . $sort_by_l . " " . $order_l;

        $stmt_lignes = $conn2->prepare($sql_lignes);
        if ($recherche_l !== null) {
            $stmt_lignes->execute(['search_l' => '%' . $recherche_l . '%']);
        } else {
            $stmt_lignes->execute();
        }
        $lignes = $stmt_lignes->fetchAll(PDO::FETCH_ASSOC);
        $conn2 = null;
        ?>

        <section class="mt-4" id="lignes">
            <div class="p-4 custom-card shadow-lg">
                <h2 class="h4 mb-4">Lignes (<?= count($lignes) ?>)</h2>

                <form action="admin.php" class="d-flex justify-content-between align-items-center mb-3" method="GET">
                    <input type="text" class="form-control me-2" name="query_l" placeholder="Rechercher une ligne..." value="<?= isset($_GET['query_l']) ? htmlspecialchars($_GET['query_l']) : '' ?>" required>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-danger" type="submit">Rechercher</button>
                        <?php if (isset($_GET['query_l']) || isset($_GET['sort_l'])) : ?>
                            <a href="admin.php#lignes" class="btn btn-secondary">Réinitialiser</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-custom table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th><a href="<?= getSortUrlL('num', $next_order_l, $recherche_l) ?>">Ligne <i class="bi bi-arrow-down-up small"></i></a></th>
                                <th><a href="<?= getSortUrlL('depart', $next_order_l, $recherche_l) ?>">Ville de départ <i class="bi bi-arrow-down-up small"></i></a></th>
                                <th><a href="<?= getSortUrlL('arrivee', $next_order_l, $recherche_l) ?>">Ville d'arrivée <i class="bi bi-arrow-down-up small"></i></a></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lignes as $l): ?>
                                <tr onclick="window.location='admin_ligne.php?id=<?= urlencode(trim($l['LIG_NUM'])) ?>'">
                                    <td><strong><?= htmlspecialchars(trim($l['LIG_NUM'])) ?></strong></td>
                                    <td><?= htmlspecialchars($l['VILLE_DEP']) ?></td>
                                    <td><?= htmlspecialchars($l['VILLE_ARR']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </main>

    <footer class="bg-light text-center py-3 border-top text-muted small mt-auto">
        <div class="container">
            <p class="mb-0">© 2026 Viking Transport — Développé par l'agence <strong>Asgard Tech</strong></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>