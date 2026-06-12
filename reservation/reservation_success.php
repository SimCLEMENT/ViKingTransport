<?php
session_start();
$ticket = $_GET['ticket'] ?? null;
if (!$ticket) {
    header('Location: reservation_trajet.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réservation confirmée - Viking Transport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: system-ui; }
        .card-custom { border-radius: 1rem; }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card card-custom shadow p-5 text-center">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                <h2 class="mt-3">Réservation confirmée !</h2>
                <p class="lead">Votre voyage a bien été enregistré.</p>
                <br>
                <p class="lead">Merci d'avoir choisi Viking Transport. <br>Nous vous souhaitons un excellent voyage !</p>
                <div class="bg-warning p-4 rounded-3 d-inline-block mb-4 mx-auto border" style="border-color: #ffc107 !important;">
                    <span class="d-block text-muted text-uppercase small fw-bold">Numéro de ticket</span>
                    <span class="fs-2 fw-bold text-dark">#<?= htmlspecialchars($ticket) ?></span>
                </div>
                <p class="text-muted">N'oubliez pas de télécharger votre ticket. Il sera à présenter au conducteur.</p>
                <div class="d-grid gap-3">
                    <a href="../PHP/generer_pdf.php?res_num=<?= urlencode($ticket) ?>" target="_blank" class="btn btn-dark btn-lg">
                        <i class="bi bi-file-earmark-pdf-fill text-danger"></i> Télécharger mon billet (PDF)
                    </a>
                    <a href="reservation_trajet.php" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-plus-circle"></i> Nouvelle réservation
                    </a>
                    <a href="../index.php" class="btn btn-link" style="text-decoration: none; font-weight: bold;"><i class="bi bi-arrow-left"></i> Revenir à l'accueil</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>