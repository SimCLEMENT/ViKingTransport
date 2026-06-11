<?php
session_start();

if (!isset($_SESSION['id']) || ($_SESSION['id'] != 200 && $_SESSION['type'] != 200)) {
    header("Location: /auth/connexion.php");
    exit;
}

if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    header("Location: /administration/admin.php");
    exit;
}

$lig_id   = trim($_GET['id']);
$host     = "harpagon.unicaen.fr";
$port     = "1521";
$sid      = "info";
$user     = "agile_5";
$password = "lemeilleurgroupe";
$dsn      = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$sid)))";

$message = '';
$message_type = 'success';

// Traitement modification ligne
if (isset($_POST['action']) && $_POST['action'] === 'modifier_ligne') {
    try {
        $conn_upd = new PDO($dsn, $user, $password);
        $sql_upd = "UPDATE VIK_LIGNE SET
                        COM_CODE_INSEE_DEBU = :debu,
                        COM_CODE_INSEE_TERM = :term
                    WHERE TRIM(LIG_NUM) = :id";
        $stmt_upd = $conn_upd->prepare($sql_upd);
        $stmt_upd->execute([
            'debu' => $_POST['debu'],
            'term' => $_POST['term'],
            'id'   => $lig_id
        ]);
        $conn_upd = null;
        $message = "Ligne modifiée avec succès.";
    } catch (PDOException $e) {
        $message = "Erreur : " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Traitement modification horaire noeud
if (isset($_POST['action']) && $_POST['action'] === 'modifier_noeud') {
    try {
        $conn_upd = new PDO($dsn, $user, $password);
        $sql_upd = "UPDATE VIK_NOEUD SET
                        NOE_HEURE_PASSAGE = TO_DATE(:heure, 'HH24:MI')
                    WHERE TRIM(LIG_NUM) = :lig
                    AND TRIM(COM_CODE_INSEE_ARRET) = :arret";
        $stmt_upd = $conn_upd->prepare($sql_upd);
        $stmt_upd->execute([
            'heure' => $_POST['heure'],
            'lig'   => $lig_id,
            'arret' => $_POST['arret']
        ]);
        $conn_upd = null;
        $message = "Horaire modifié avec succès.";
    } catch (PDOException $e) {
        $message = "Erreur : " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Traitement suppression ligne
if (isset($_POST['action']) && $_POST['action'] === 'supprimer') {
    try {
        $conn_del = new PDO($dsn, $user, $password);
        $conn_del->prepare("DELETE FROM VIK_NOEUD WHERE TRIM(LIG_NUM) = :id")->execute(['id' => $lig_id]);
        $conn_del->prepare("DELETE FROM VIK_ETAPE WHERE TRIM(LIG_NUM) = :id")->execute(['id' => $lig_id]);
        $conn_del->prepare("DELETE FROM VIK_LIGNE WHERE TRIM(LIG_NUM) = :id")->execute(['id' => $lig_id]);
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

    // Infos ligne
    $sql = "SELECT l.LIG_NUM, l.COM_CODE_INSEE_DEBU, l.COM_CODE_INSEE_TERM,
                   c1.COM_NOM AS VILLE_DEP, c2.COM_NOM AS VILLE_ARR
            FROM VIK_LIGNE l
            JOIN VIK_COMMUNE c1 ON l.COM_CODE_INSEE_DEBU = c1.COM_CODE_INSEE
            JOIN VIK_COMMUNE c2 ON l.COM_CODE_INSEE_TERM = c2.COM_CODE_INSEE
            WHERE TRIM(l.LIG_NUM) = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['id' => $lig_id]);
    $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ligne) {
        header("Location: /administration/admin.php");
        exit;
    }

    // Noeuds / horaires
    $sql2 = "SELECT n.COM_CODE_INSEE_ARRET, c.COM_NOM,
                    TO_CHAR(n.NOE_HEURE_PASSAGE, 'HH24:MI') AS HEURE,
                    n.NOE_DISTANCE_PROCHAIN, n.NOE_DUREE_PROCHAIN
             FROM VIK_NOEUD n
             JOIN VIK_COMMUNE c ON n.COM_CODE_INSEE_ARRET = c.COM_CODE_INSEE
             WHERE TRIM(n.LIG_NUM) = :id
             ORDER BY n.NOE_HEURE_PASSAGE ASC";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->execute(['id' => $lig_id]);
    $noeuds = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Toutes les communes pour la modale
    $sql3 = "SELECT COM_CODE_INSEE, COM_NOM FROM VIK_COMMUNE ORDER BY COM_NOM ASC";
    $stmt3 = $conn->prepare($sql3);
    $stmt3->execute();
    $communes = $stmt3->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Ligne <?= htmlspecialchars($lig_id) ?> - Administration</title>

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

        .nav-col { flex: 1; display: flex; align-items: center; }
        .nav-col.nav-center { justify-content: center; gap: 2rem; }
        .nav-col.nav-right { justify-content: flex-end; }

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

        .btn-supprimer:hover {
            background-color: var(--viking-red);
            color: white;
        }
    </style>
</head>
<body>

<?php include_once("../PHP/header.php") ?>

<main class="container mb-5 mt-4">

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> mb-4"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Titre -->
    <section class="mb-4">
        <div class="p-4 custom-card shadow-lg d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 mb-1">Ligne <?= htmlspecialchars($lig_id) ?></h2>
                <div class="d-flex align-items-center gap-3 mt-2">
                    <span class="fw-semibold fs-6"><?= htmlspecialchars($ligne['VILLE_DEP']) ?></span>
                    <i class="bi bi-arrow-right text-muted"></i>
                    <span class="fw-semibold fs-6"><?= htmlspecialchars($ligne['VILLE_ARR']) ?></span>
                </div>
            </div>
            <a href="/administration/admin.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Retour
            </a>
        </div>
    </section>

    <!-- Modifier communes -->
    <section class="mb-4">
        <div class="p-4 custom-card shadow-lg">
            <h2 class="h4 mb-4">Modifier les communes</h2>

            <form method="POST">
                <input type="hidden" name="action" value="modifier_ligne">
                <div class="mb-3">
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCommunes">
                        <i class="bi bi-search me-1"></i> Voir les codes INSEE
                    </button>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Code INSEE départ</label>
                        <input type="text" name="debu" class="form-control" value="<?= htmlspecialchars($ligne['COM_CODE_INSEE_DEBU']) ?>">
                        <div class="form-text"><?= htmlspecialchars($ligne['VILLE_DEP']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Code INSEE terminus</label>
                        <input type="text" name="term" class="form-control" value="<?= htmlspecialchars($ligne['COM_CODE_INSEE_TERM']) ?>">
                        <div class="form-text"><?= htmlspecialchars($ligne['VILLE_ARR']) ?></div>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-3">
                    <button type="submit" class="btn btn-sauvegarder">
                        <i class="bi bi-floppy me-1"></i> Enregistrer
                    </button>
                    <button type="button" class="btn btn-supprimer"
                        onclick="if(confirm('Êtes-vous sûr de vouloir supprimer cette ligne ? Tous les noeuds et étapes associés seront supprimés.')) { document.getElementById('form-supprimer').submit(); }">
                        <i class="bi bi-trash me-1"></i> Supprimer cette ligne
                    </button>
                </div>
            </form>

            <form id="form-supprimer" method="POST" style="display:none;">
                <input type="hidden" name="action" value="supprimer">
            </form>
        </div>
    </section>

    <!-- Horaires / Noeuds -->
    <section class="mb-4">
        <div class="p-4 custom-card shadow-lg">
            <h2 class="h4 mb-4">Horaires des arrêts (<?= count($noeuds) ?>)</h2>

            <?php if (empty($noeuds)): ?>
                <p class="text-muted">Aucun arrêt trouvé pour cette ligne.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Arrêt</th>
                                <th>Commune</th>
                                <th>Heure de passage</th>
                                <th>Dist. prochain (km)</th>
                                <th>Durée prochain (min)</th>
                                <th>Modifier</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($noeuds as $n): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($n['COM_CODE_INSEE_ARRET']) ?></strong></td>
                                    <td><?= htmlspecialchars($n['COM_NOM']) ?></td>
                                    <td><?= htmlspecialchars($n['HEURE'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($n['NOE_DISTANCE_PROCHAIN'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($n['NOE_DUREE_PROCHAIN'] ?? '—') ?></td>
                                    <td>
                                        <form method="POST" class="d-flex gap-2 align-items-center">
                                            <input type="hidden" name="action" value="modifier_noeud">
                                            <input type="hidden" name="arret" value="<?= htmlspecialchars($n['COM_CODE_INSEE_ARRET']) ?>">
                                            <input type="time" name="heure" class="form-control form-control-sm" value="<?= htmlspecialchars($n['HEURE'] ?? '') ?>" style="width:120px;">
                                            <button type="submit" class="btn btn-sauvegarder btn-sm">
                                                <i class="bi bi-floppy"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>

<!-- Modale communes -->
<div class="modal fade" id="modalCommunes" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="border-bottom: 3px solid #C62828;">
                <h5 class="modal-title fw-bold">Codes INSEE des communes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="searchCommune" class="form-control mb-3" placeholder="Rechercher une commune...">
                <div class="table-responsive">
                    <table class="table table-custom table-striped align-middle mb-0" id="tableCommunes">
                        <thead>
                            <tr>
                                <th>Code INSEE</th>
                                <th>Commune</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($communes as $com): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($com['COM_CODE_INSEE']) ?></strong></td>
                                    <td><?= htmlspecialchars($com['COM_NOM']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('searchCommune').addEventListener('input', function() {
    const val = this.value.toLowerCase();
    document.querySelectorAll('#tableCommunes tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
    });
});
</script>

</main>

<footer class="bg-light text-center py-3 border-top text-muted small">
    <div class="container">
        <p class="mb-0">© 2026 Viking Transport — Développé par l'agence <strong>Asgard Tech</strong></p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>