<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_POST['origine'])) {
    $_SESSION['origine_reservation'] = $_POST['origine'];
}
if (!isset($_SESSION['origine_reservation'])) {
    $_SESSION['origine_reservation'] = 'auto';
}

if (!isset($_SESSION['client_info']) || !isset($_POST['trajet_json'])) {
    header('Location: reservation_trajet.php');
    exit;
}

$client = $_SESSION['client_info'];
$choix = json_decode($_POST['trajet_json'], true);
$dateVoyage = $_POST['date_voyage'];
$heureMin = $_POST['heure_min'] ?? '00:00';

$host = "harpagon.unicaen.fr";
$port = "1521";
$sid = "info";
$user = "agile_5";
$password = "lemeilleurgroupe";
$dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$sid)))";

try {
    $conn = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Récupération du type de client (pourcentage à payer)
    $client_id = 0;
    $pourcentage_a_payer = 100;
    $points_disponibles = 0;
    if (isset($_SESSION['id']) && $_SESSION['id'] > 0) {
        $client_id = (int)$_SESSION['id'];
        $stmt = $conn->prepare("SELECT t.TYP_REDUC, c.CLI_NB_POINTS_EC 
                                FROM VIK_CLIENT c 
                                JOIN VIK_TYPE_CLIENT t ON c.TYP_NUM = t.TYP_NUM 
                                WHERE c.CLI_NUM = :id");
        $stmt->execute(['id' => $client_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $pourcentage_a_payer = (float)$data['TYP_REDUC'];
            $points_disponibles = (int)$data['CLI_NB_POINTS_EC'];
        }
    }

    // Prix initial (sans réduction)
    $prix_initial = $choix['prix'];
    // Prix après application du pourcentage fidélité (ex: 10€ * 95% = 9.50€)
    $prix_apres_type = round($prix_initial * ($pourcentage_a_payer / 100), 2);

    // Récupérer les paliers de réduction par points
    $reductions_points = [];
    $stmt = $conn->prepare("SELECT RED_NB_POINTS, RED_VALEUR FROM VIK_REDUCTION ORDER BY RED_NB_POINTS");
    $stmt->execute();
    $reductions_points = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ajouter l'option "0 points" si elle n'existe pas
    $hasZero = false;
    foreach ($reductions_points as $p) {
        if ($p['RED_NB_POINTS'] == 0) $hasZero = true;
    }
    if (!$hasZero) {
        array_unshift($reductions_points, ['RED_NB_POINTS' => 0, 'RED_VALEUR' => 0]);
    }

    // Filtrer les paliers que le client peut utiliser
    $paliers_possibles = [];
    foreach ($reductions_points as $p) {
        if ($p['RED_NB_POINTS'] <= $points_disponibles) {
            $paliers_possibles[] = $p;
        }
    }
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Paiement - Viking Transport</title>
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
            font-family: system-ui;
        }

        .card-paiement {
            background: white;
            border-radius: 1rem;
        }

        .prix-final {
            font-size: 2rem;
            color: #C62828;
            font-weight: bold;
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
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

    <?php include_once("../PHP/header.php"); ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card card-paiement shadow p-4">
                    <h3 class="mb-4">Récapitulatif du paiement</h3>
                    <div class="mb-3">
                        <strong>Trajet choisi :</strong><br>
                        Lignes : <?= implode(' -> ', $choix['lignes']) ?><br>
                        Départ : <?= $choix['heure_depart'] ?> - Arrivée : <?= $choix['heure_arrivee'] ?><br>
                        Distance : <?= $choix['distance'] ?> km
                    </div>
                    <div class="mb-3">
                        <strong>Prix de base :</strong> <?= number_format($prix_initial, 2, ',', ' ') ?> €
                    </div>
                    <?php if ($pourcentage_a_payer < 100): ?>
                        <div class="mb-3 text-success">
                            <i class="bi bi-star-fill"></i> Tarif fidélité : <?= $pourcentage_a_payer ?>% du prix de base<br>
                            (soit <?= number_format($prix_apres_type, 2, ',', ' ') ?> € au lieu de <?= number_format($prix_initial, 2, ',', ' ') ?> €)
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <strong>Sous-total :</strong> <?= number_format($prix_apres_type, 2, ',', ' ') ?> €
                    </div>

                    <form method="POST" action="paiement_cb.php">
                        <input type="hidden" name="trajet_json" value="<?= htmlspecialchars($_POST['trajet_json']) ?>">
                        <input type="hidden" name="date_voyage" value="<?= htmlspecialchars($dateVoyage) ?>">
                        <input type="hidden" name="heure_min" value="<?= htmlspecialchars($heureMin) ?>">
                        <input type="hidden" name="prix_apres_type" value="<?= $prix_apres_type ?>">
                        <input type="hidden" name="prix_initial" value="<?= $prix_initial ?>">

                        <?php if ($client_id > 0): ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Utiliser mes points fidélité</label>
                                <select name="points_utilises" class="form-select" id="pointsSelect" onchange="updatePrix()">
                                    <?php foreach ($paliers_possibles as $p): ?>
                                        <option value="<?= $p['RED_NB_POINTS'] ?>" data-reduction="<?= $p['RED_VALEUR'] ?>">
                                            <?= $p['RED_NB_POINTS'] ?> points → économie de <?= number_format($p['RED_VALEUR'], 2, ',', ' ') ?> €
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Points disponibles : <?= $points_disponibles ?></div>
                            </div>
                            <div class="mb-3">
                                <strong>Total à payer :</strong> <span id="prixFinal" class="prix-final"><?= number_format($prix_apres_type, 2, ',', ' ') ?> €</span>
                            </div>
                            <input type="hidden" name="reduction_points" id="reductionPoints" value="0">
                        <?php else: ?>
                            <div class="alert alert-info">Connectez-vous pour utiliser vos points fidélité.</div>
                            <input type="hidden" name="points_utilises" value="0">
                            <input type="hidden" name="reduction_points" value="0">
                            <div class="mb-3">
                                <strong>Total à payer :</strong> <span class="prix-final"><?= number_format($prix_apres_type, 2, ',', ' ') ?> €</span>
                            </div>
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">Confirmer le paiement</button>
                            <a href="<?= ($_SESSION['origine_reservation'] == 'manuel') ? 'reservation_trajet_manuel.php' : 'reservation_choix.php' ?>" class="btn btn-outline-secondary">Retour</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include_once("../PHP/footer.php"); ?>
    <script>
        function updatePrix() {
            let select = document.getElementById('pointsSelect');
            let reduction = parseFloat(select.options[select.selectedIndex].getAttribute('data-reduction'));
            let prixApresType = <?= $prix_apres_type ?>;
            let prixFinal = prixApresType - reduction;
            if (prixFinal < 0) prixFinal = 0;
            document.getElementById('prixFinal').innerText = prixFinal.toFixed(2).replace('.', ',') + ' €';
            document.getElementById('reductionPoints').value = reduction;
        }
        // Appel initial pour que le total corresponde à l'option sélectionnée par défaut
        document.addEventListener('DOMContentLoaded', updatePrix);
    </script>
</body>

</html>