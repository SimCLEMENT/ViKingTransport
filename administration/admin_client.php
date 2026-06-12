<?php
session_start();

// Vérification admin
if (!isset($_SESSION['id']) || $_SESSION['id'] != 200) {
    header("Location: /auth/connexion.php");
    exit;
}

// Vérification paramètre
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /administration/admin.php");
    exit;
}

$cli_id   = (int)$_GET['id'];
$host     = "harpagon.unicaen.fr";
$port     = "1521";
$sid      = "info";
$user     = "agile_5";
$password = "lemeilleurgroupe";
$dsn      = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$sid)))";

$message = '';
$message_type = 'success';

// Traitement modification
if (isset($_POST['action']) && $_POST['action'] === 'modifier') {
    try {
        $conn_upd = new PDO($dsn, $user, $password);
        $sql_upd = "UPDATE VIK_CLIENT SET
                        CLI_NOM       = :nom,
                        CLI_PRENOM    = :prenom,
                        CLI_VILLE     = :ville,
                        CLI_TELEPHONE = :tel,
                        CLI_COURRIEL  = :email
                    WHERE CLI_NUM = :id";
        $stmt_upd = $conn_upd->prepare($sql_upd);
        $stmt_upd->execute([
            'nom'    => $_POST['nom'],
            'prenom' => $_POST['prenom'],
            'ville'  => $_POST['ville'],
            'tel'    => $_POST['tel'],
            'email'  => $_POST['email'],
            'id'     => $cli_id
        ]);
        $conn_upd = null;
        $message = "Modifications enregistrées avec succès.";
    } catch (PDOException $e) {
        $message = "Erreur : " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Traitement suppression
if (isset($_POST['action']) && $_POST['action'] === 'supprimer') {
    try {
        $conn_del = new PDO($dsn, $user, $password);
        $conn_del->prepare("DELETE FROM VIK_ETAPE WHERE CLI_NUM = :id")->execute(['id' => $cli_id]);
        $conn_del->prepare("DELETE FROM VIK_RESERVATION WHERE CLI_NUM = :id")->execute(['id' => $cli_id]);
        $conn_del->prepare("DELETE FROM VIK_CLIENT WHERE CLI_NUM = :id")->execute(['id' => $cli_id]);
        $conn_del = null;
        header("Location: /administration/admin.php");
        exit;
    } catch (PDOException $e) {
        $message = "Erreur : " . $e->getMessage();
        $message_type = 'danger';
    }
}

try {
    $conn = new PDO($dsn, $user, $password);

    // Infos client
    $sql = "SELECT c.CLI_NUM, c.CLI_NOM, c.CLI_PRENOM, c.CLI_VILLE, c.CLI_TELEPHONE,
                   c.CLI_COURRIEL, c.CLI_NB_POINTS_EC, c.CLI_NB_POINTS_TOT,
                   TO_CHAR(c.CLI_DATE_CONNEC, 'DD/MM/YYYY') AS DATE_CONNEC,
                   t.TYP_NOM
            FROM VIK_CLIENT c
            JOIN VIK_TYPE_CLIENT t ON c.TYP_NUM = t.TYP_NUM
            WHERE c.CLI_NUM = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['id' => $cli_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        header("Location: /administration/admin.php");
        exit;
    }

    // Réservations
    $sql2 = "SELECT r.RES_NUM, TO_CHAR(r.RES_DATE, 'DD/MM/YYYY') AS RES_DATE,
                r.RES_PRIX_TOT, r.RES_NB_POINTS, 
                c2.COM_NOM AS DEPART, c.COM_NOM AS ARRIVEE
         FROM VIK_RESERVATION r
         LEFT JOIN VIK_COMMUNE c ON TRIM(r.COM_CODE_INSEE_ARRIVEE) = TRIM(c.COM_CODE_INSEE)
         LEFT JOIN VIK_COMMUNE c2 ON TRIM(r.COM_CODE_INSEE_DEPART) = TRIM(c2.COM_CODE_INSEE)
         WHERE r.CLI_NUM = :id
         ORDER BY r.RES_DATE DESC";

    $stmt2 = $conn->prepare($sql2);
    $stmt2->execute(['id' => $cli_id]);
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
    <title>Client #<?= $cli_id ?> - Administration</title>

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
            color: var(--viking-dark);
            font-family: system-ui, -apple-system, sans-serif;
        }

        html { overflow-y: scroll; }

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
            background-color: var(--viking-bg-grey);
            color: var(--viking-white);
        }

        .table-custom thead th {
            color: var(--viking-white);
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

        .form-control:focus {
            border-color: var(--viking-red);
            box-shadow: 0 0 0 3px rgba(198,40,40,0.12);
        }

        .btn-sauvegarder {
            background-color: var(--viking-red);
            border: none;
            color: white;
            font-weight: 600;
        }

        .btn-sauvegarder:hover {
            background-color: var(--viking-dark-red);
            color: white;
        }

        .btn-supprimer {
            background-color: transparent;
            border: 1.5px solid var(--viking-red);
            color: var(--viking-red);
            font-weight: 600;
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
        .btn-supprimer:hover {
            background-color: var(--viking-red);
            color: white;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<?php include_once("../PHP/header.php") ?>

<main class="container mb-5 mt-4">

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> mb-4"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Titre -->
    <section class="mb-4">
        <div class="p-4 custom-card shadow-lg d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 mb-1">Client #<?= htmlspecialchars($client['CLI_NUM']) ?></h2>
                <div class="d-flex align-items-center gap-3 mt-2 flex-wrap">
                    <span class="fw-semibold fs-6"><?= htmlspecialchars($client['CLI_PRENOM']) ?> <?= htmlspecialchars($client['CLI_NOM']) ?></span>
                    <span style="background-color:#C62828;color:white;font-size:0.8rem;padding:3px 12px;border-radius:20px;"><?= htmlspecialchars($client['TYP_NOM']) ?></span>
                    <span class="text-muted small"><i class="bi bi-star me-1"></i><?= htmlspecialchars($client['CLI_NB_POINTS_EC']) ?> pts en cours</span>
                    <span class="text-muted small"><i class="bi bi-star-fill me-1"></i><?= htmlspecialchars($client['CLI_NB_POINTS_TOT']) ?> pts total</span>
                </div>
            </div>
            <a href="/administration/admin.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Retour
            </a>
        </div>
    </section>

    <!-- Réservations -->
    <section class="mb-4">
        <div class="p-4 custom-card shadow-lg">
            <h2 class="h4 mb-4">Réservations (<?= count($reservations) ?>)</h2>

            <?php if (empty($reservations)): ?>
                <p class="text-muted">Aucune réservation.</p>
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
                                    <td><?= htmlspecialchars($res['DEPART'] ?? 'Inconnu') ?></td>
                                    <td><?= htmlspecialchars($res['ARRIVEE'] ?? 'Inconnu') ?></td>
                                    <td><strong><?= htmlspecialchars($res['RES_PRIX_TOT']) ?> €</strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Formulaire modification -->
    <section class="mb-4">
        <div class="p-4 custom-card shadow-lg">
            <h2 class="h4 mb-4">Modifier les informations</h2>

            <form method="POST">
                <input type="hidden" name="action" value="modifier">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Nom</label>
                        <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($client['CLI_NOM']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Prénom</label>
                        <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($client['CLI_PRENOM']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Ville</label>
                        <input type="text" name="ville" class="form-control" value="<?= htmlspecialchars($client['CLI_VILLE'] ?: '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Téléphone</label>
                        <input type="text" name="tel" class="form-control" value="<?= htmlspecialchars($client['CLI_TELEPHONE'] ?: '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($client['CLI_COURRIEL'] ?: '') ?>">
                    </div>
                </div>
                <div class="mt-4 d-flex gap-3">
                    <button type="submit" class="btn btn-sauvegarder">
                        <i class="bi bi-floppy me-1"></i> Enregistrer
                    </button>
                    <button type="button" class="btn btn-supprimer"
                        onclick="if(confirm('Êtes-vous sûr de vouloir supprimer ce client ? Cette action est irréversible.')) { document.getElementById('form-supprimer').submit(); }">
                        <i class="bi bi-trash me-1"></i> Supprimer ce compte
                    </button>
                </div>
            </form>

            <form id="form-supprimer" method="POST" style="display:none;">
                <input type="hidden" name="action" value="supprimer">
            </form>
        </div>
    </section>

</main>


<?php include_once("../PHP/footer.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>