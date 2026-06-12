<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['client_info']) || !isset($_POST['trajet_json']) || !isset($_POST['date_voyage'])) {
    header('Location: reservation_trajet.php');
    exit;
}

// On conserve les données dans des variables pour le formulaire
$trajet_json = htmlspecialchars($_POST['trajet_json']);
$date_voyage = htmlspecialchars($_POST['date_voyage']);
$heure_min = htmlspecialchars($_POST['heure_min'] ?? '00:00');
$prix_apres_type = htmlspecialchars($_POST['prix_apres_type']);
$prix_initial = htmlspecialchars($_POST['prix_initial']);
$points_utilises = htmlspecialchars($_POST['points_utilises'] ?? 0);
$reduction_points = htmlspecialchars($_POST['reduction_points'] ?? 0);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Paiement sécurisé - Viking Transport</title>
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

        html { overflow-y: scroll; }

        .card-paiement {
            background: white;
            border-radius: 1rem;
            max-width: 500px;
            margin: auto;
        }

        .card-input {
            border-radius: 10px;
            border: 1px solid #ddd;
            padding: 0.75rem;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-spinner {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
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
        <div class="card card-paiement shadow p-4">
            <h3 class="mb-4 text-center"><i class="bi bi-credit-card"></i> Paiement par carte bancaire</h3>
            <p class="text-muted text-center">Montant à régler : <strong><?= number_format((float)$prix_apres_type - (float)$reduction_points, 2, ',', ' ') ?> €</strong></p>

            <form method="POST" action="confirmation_reservation.php" id="paymentForm">
                <input type="hidden" name="trajet_json" value="<?= $trajet_json ?>">
                <input type="hidden" name="date_voyage" value="<?= $date_voyage ?>">
                <input type="hidden" name="heure_min" value="<?= $heure_min ?>">
                <input type="hidden" name="prix_apres_type" value="<?= $prix_apres_type ?>">
                <input type="hidden" name="prix_initial" value="<?= $prix_initial ?>">
                <input type="hidden" name="points_utilises" value="<?= $points_utilises ?>">
                <input type="hidden" name="reduction_points" value="<?= $reduction_points ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold">Numéro de carte</label>
                    <input type="text" name="card_number" class="form-control card-input" placeholder="1234 5678 9012 3456" maxlength="19" required pattern="[0-9\s]{16,19}" inputmode="numeric">
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Date d'expiration</label>
                        <input type="text" name="expiry" class="form-control card-input" placeholder="MM/AA" maxlength="5" required pattern="(0[1-9]|1[0-2])\/[0-9]{2}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">CVV</label>
                        <input type="text" name="cvv" class="form-control card-input" placeholder="123" maxlength="3" required pattern="[0-9]{3}">
                    </div>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="saveCard" name="save_card">
                    <label class="form-check-label" for="saveCard">Mémoriser ma carte (simulation)</label>
                </div>
                <button type="submit" name="paiement_cb" class="btn btn-danger w-100 py-2 fw-bold">Payer</button>
                <a href="choix_paiement.php" class="btn btn-outline-secondary w-100 mt-2">Retour</a>
            </form>
            <div class="mt-3 text-center small text-muted">
                <i class="bi bi-lock-fill"></i> Paiement 100% sécurisé (simulation)
            </div>
        </div>
    </div>
    <?php include_once("../PHP/footer.php"); ?>
</body>

</html>