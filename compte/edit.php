<?php
session_start();
?>

<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VikingTransport — Connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --viking-red: #C62828;
            --viking-dark-red: #9b1e1e;
            --viking-dark-grey: #706767;
            --viking-bg: #f8f9fa;
            --viking-light-grey: #E5E8E8;
            --viking-white: #FFFFFF;
            --viking-dark: #212121;
        }

        html {
            overflow-y: scroll;
        }

        body {
            background-color: var(--viking-bg);
            font-family: system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        /* Navbar partagée */
        header {
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

        /* Contenu centré */
        .login-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
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

<?php include_once "../PHP/header.php"; ?>

<div class="container text-center mb-5">

    <?php
        switch ($_GET['edit']) {
            case "nom":
                include_once "edit-nom.php";
                break;
            case "mail":
                include_once "edit-mail.php";
                break;
            case "tel":
                include_once "edit-tel.php";
                break;
            case "ville":
                include_once "edit-ville.php";
                break;
        }
    ?>

</div>

<footer class="bg-light text-center py-3 border-top text-muted small fixed-bottom">
    <div class="container">
        <p class="mb-0">© 2026 Viking Transport - Développé par l'agence <strong>Asgard Tech</strong></p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>