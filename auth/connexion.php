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

    <?php include_once("../PHP/header.php") ?>



    <div class="login-wrapper">
        <div class="bg-white border rounded-3 p-4 w-100" style="max-width: 400px;">
            <a href="../" class="text-decoration-none text-muted small d-flex align-items-center gap-1 mb-3">
                <i class="bi bi-arrow-left"></i> Retour à l'accueil
            </a>
            <h4 class="text-center mb-4 fw-bold" style="color: var(--viking-red);">Connexion</h4>
            <form method="post" action="login.php">
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold small">Adresse e-mail</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="prenom.nom@exemple.fr">
                </div>

                <label for="password" class="form-label fw-semibold small">Mot de passe</label>
                <div class="mb-4">
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe">

                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('password', 'showPwd')">
                            <i class="bi bi-eye" id="showPwd"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex flex-column gap-2">
                    <input id="connexion" name="connexion" type="submit" class="btn w-100 fw-semibold text-white" style="background-color: #C62828;"
                        value="Se connecter">
                    <a href="/auth/formulaire_site.php" class="btn w-100 fw-semibold" style="border: 1.5px solid #C62828; color: #C62828;">Créer un
                        compte</a>
                </div>

                <?php

                if (isset($_SESSION["error"])) {
                    echo "<br><br>";
                    /* TODO Afficher erreur de connexion et tout */
                    if ($_SESSION["error"] == "notSet") {
                        echo ("<p class=\"text-center text-danger\">Veuillez entrer votre adresse courriel et votre mot de passe.</p>");
                    } elseif ($_SESSION["error"] == "emailNotSet") {
                        echo ("<p class=\"text-center text-danger\">Veuillez entrer votre adresse courriel.</p>");
                    } elseif ($_SESSION["error"] == "pwdNotSet") {
                        echo ("<p class=\"text-center text-danger\">Veuillez entrer votre mot de passe.</p>");
                    } elseif ($_SESSION["error"] == "badAuth") {
                        echo ("<p class=\"text-center text-danger\">Votre mot de passe ou adresse mail ne correspond pas !</p>");
                    } elseif ($_SESSION["error"] == "accCreated") {
                        echo ("<p class=\"text-center text-info\">Votre compte a été créé.</p>");
                    } elseif ($_SESSION["error"] == "doesntExist") {
                        echo ("<p class=\"text-center text-danger\">Ce compte n'existe pas.</p>");
                    }
                    unset($_SESSION["error"]);
                }

                ?>
            </form>
        </div>
    </div>


    <?php include_once("../PHP/footer.php"); ?>

    <script src="../JS/password-verify.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>