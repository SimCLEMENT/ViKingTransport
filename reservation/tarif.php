<?php
session_start();

// Configuration base de données
$host = "harpagon.unicaen.fr";
$port = "1521";
$sid = "info";
$user = "agile_5";
$password = "lemeilleurgroupe";
$dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$sid)))";

try {
    $conn = new PDO($dsn, $user, $password);
    
    // Récupération des tarifs
    $sql = "SELECT TAR_NUM_TRANCHE, TAR_MIN_DIST, TAR_MAX_DIST, TAR_PRIX 
            FROM VIK_TARIF 
            ORDER BY TAR_NUM_TRANCHE ASC";
    $stmt = $conn->query($sql);
    $tarifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Erreur de connexion : " . $e->getMessage();
}

// Fonction pour gérer la classe active du menu
function isActive($page) {
    return (basename($_SERVER['PHP_SELF']) == $page) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nos tarifs - Viking Transport</title>

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

        html {
            overflow-y: scroll;
        }

        body {
            background: linear-gradient(rgba(33, 33, 33, 0.6), rgba(33, 33, 33, 0.6)), url('../images/tarifs.jpg') no-repeat center center;
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
            font-family: system-ui, -apple-system, sans-serif;
        }

        /* En-tête */
        header {
            background-color: var(--viking-white);
            border-bottom: 5px solid var(--viking-red);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        header h1 {
            color: var(--viking-dark-grey);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        header p {
            color: #7F8C8D !important;
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
            background-color: var(--viking-white);
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
        }

        .table-custom tbody tr td:last-child {
            font-weight: 700;
            color: var(--viking-red);
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
            --bs-btn-active-bg: var(--viking-red);
            --bs-btn-active-color: var(--viking-white);
            --bs-btn-active-border-color: var(--viking-red);
            --bs-btn-focus-shadow-rgb: 198, 40, 40;
            --bs-btn-active-shadow: none;
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

    </style>
</head>

<body>

<?php include_once("../PHP/header.php") ?>

<main class="container">
        <section class="mb-4">
            <div class="p-4 custom-card shadow-lg">
                <h2 class="h4 mb-3">Comment fonctionnent nos tarifs ?</h2>
                <p class="text-muted mb-0">
                    Nos prix sont calculés par tranches de distance.
                    Chaque tranche correspond à un intervalle de kilomètres, avec un tarif fixe.
                    Cela permet une facturation simple, claire et sans surprise.
                </p>
            </div>
        </section>

        <section class="mb-5">
            <div class="p-4 custom-card shadow-lg">
                <h2 class="h4 mb-4">Tarifs par distance</h2>

                <div class="table-responsive">
                    <table class="table table-custom table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Tranche</th>
                                <th scope="col">Distance minimum (km)</th>
                                <th scope="col">Distance maximum (km)</th>
                                <th scope="col">Tarif</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($error)): ?>
                                <tr><td colspan="4" class="text-center text-danger"><?php echo htmlspecialchars($error); ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($tarifs as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['TAR_NUM_TRANCHE']); ?></td>
                                        <td><?php echo htmlspecialchars($row['TAR_MIN_DIST']); ?></td>
                                        <td><?php echo htmlspecialchars($row['TAR_MAX_DIST']); ?></td>
                                        <td><?php echo htmlspecialchars($row['TAR_PRIX']); ?> €</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
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