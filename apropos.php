<?php session_start(); ?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Qui sommes-nous - Viking Transport</title>

   
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
        }

        .btn-viking {
            background-color: var(--viking-red);
            color: white;
        }

        .btn-viking:hover {
            background-color: var(--viking-red-light);
            color: white;
        }

        .text-viking-red {
            color: var(--viking-red);
        }

        .bg-viking-dark {
            background-color: var(--viking-dark) !important;
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

        .text-muted {
            text-align: justify;
        }

        .card p {
            text-align : justify;
        }
    </style>
</head>
<body>

    <?php include_once("PHP/header.php") ?>

    <main class="container-fluid px-0 mb-5">
    <div class="row g-0">

        <!-- Bannière gauche -->
        <div class="col-lg-2 d-none d-lg-block">
            <img src="images/banniere_gauche" alt="" class="w-100" style="object-fit: cover;">
        </div>

        <!-- Contenu central -->
        <div class="col-12 col-lg-8">
            <div class="row justify-content-center g-4 px-3 pt-4">
                <div class="col-lg-10">

                    <div class="card p-4 shadow-sm border-0 bg-white mb-4">
                        <h2 class="h4 fw-bold text-viking-red mb-3">Notre Mission</h2>
                        <p class="text-muted fs-5">
                            <strong>Viking Transport</strong> est le réseau de cars normands officiel qui réunit l'ensemble des transports régionaux non urbains. Notre vocation est de casser l'isolement des territoires en permettant à chaque citoyen de réserver et d'effectuer sereinement des trajets d'une commune à une autre, que ce soit pour le travail, les études ou les loisirs.
                        </p>
                    </div>

                    <div class="card p-4 shadow-sm border-0 bg-white mb-4">
                        <h2 class="h4 fw-bold text-viking-red mb-3">Un réseau au service des Normands</h2>
                        <p class="text-muted">
                            Du Calvados à la Manche, en passant par l'Eure, l'Orne et la Seine-Maritime, nos lignes relient les plus grandes villes comme Caen, Évreux ou Rouen, jusqu'aux plus petites communes de la région. Grâce à des infrastructures optimisées et des correspondances pensées pour vous, nous simplifions vos déplacements quotidiens.
                        </p>
                    </div>

                    <div class="row g-3 text-center mb-4">
                        <div class="col-md-4">
                            <div class="card p-3 border-0 shadow-sm bg-white">
                                <span class="display-6 fw-bold text-viking-red">5</span>
                                <span class="text-muted small fw-bold">Départements desservis</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card p-3 border-0 shadow-sm bg-white">
                                <span class="display-6 fw-bold text-viking-red">19</span>
                                <span class="text-muted small fw-bold">Lignes de cars</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card p-3 border-0 shadow-sm bg-white">
                                <span class="display-6 fw-bold text-viking-red">100%</span>
                                <span class="text-muted small fw-bold">Normand</span>
                            </div>
                        </div>
                    </div>

                    <div class="card p-4 shadow-sm border-0 bg-white">
                        <h2 class="h4 fw-bold text-viking-red mb-3">Vers une mobilité plus connectée (2026)</h2>
                        <p class="text-muted mb-0">
                            En 2026, Viking Transport franchit un cap en modernisant l'intégralité de son système de réservation en ligne. Grâce à notre nouvelle plateforme web, vous pouvez désormais simuler vos trajets, acheter vos billets en quelques secondes, et profiter d'un tout nouveau programme de fidélité avantageux basé sur vos kilomètres parcourus.
                        </p>
                    </div>

                </div>
            </div>
        </div>

        <!-- Bannière droite -->
        <div class="col-lg-2 d-none d-lg-block">
            <img src="images/banniere_droite" alt="" class="w-100" style="object-fit: cover;">
        </div>

    </div>
</main>

    <?php include_once("PHP/footer.php"); ?>

    
    <script 
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
    </script>
</body>
</html>