<?php
session_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VikingTransport — Créer un compte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
	<link rel="icon" type="image/x-icon" href="../images/logo_blanc.png">

    <style>
        :root {
            --viking-red: #C62828;
            --viking-dark-red: #9b1e1e;
            --viking-dark-grey: #706767;
            --viking-bg-grey: #8A8181;
            --viking-light-grey: #E5E8E8;
            --viking-white: #FFFFFF;
        }

        html {
            overflow-y: scroll;
        }

        body {
            background-color: var(--viking-bg-grey);
            font-family: system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header.site-header {
            background-color: var(--viking-white);
            border-bottom: 5px solid var(--viking-red);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
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

    <?php include_once("../PHP/header.php"); ?>

    <div class="login-wrapper">
        <div class="bg-white border rounded-3 p-4 w-100" style="max-width: 600px;">

            <h4 class="text-center mb-4 fw-bold" style="color: var(--viking-red);">Rejoindre Viking Transport</h4>

            <form method="POST" action="formulaire_inscription.php" id="inscriptionForm" novalidate>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="cli_prenom" class="form-label fw-semibold small">Prénom</label>
                        <input type="text" class="form-control" id="cli_prenom" name="cli_prenom"
                               placeholder="Ex: Ragnar" maxlength="64" required>
                        <div class="invalid-feedback">Le prénom est requis (64 caractères max).</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="cli_nom" class="form-label fw-semibold small">Nom</label>
                        <input type="text" class="form-control text-uppercase" id="cli_nom" name="cli_nom"
                               placeholder="Ex: LOTHBROK" maxlength="64" required>
                        <div class="invalid-feedback">Le nom est requis (64 caractères max).</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="cli_courriel" class="form-label fw-semibold small">Adresse e-mail</label>
                    <input type="email" class="form-control" id="cli_courriel" name="cli_courriel"
                           placeholder="ragnar@exemple.fr" maxlength="64" required>
                    <div class="form-text text-muted" style="font-size:0.75rem;">64 caractères maximum.</div>
                    <div class="invalid-feedback" id="courriel-feedback">Adresse e-mail invalide (64 caractères max).</div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="cli_mdp" class="form-label fw-semibold small">Mot de passe</label>
                        <input type="password" class="form-control" id="cli_mdp" name="cli_mdp"
                               placeholder="Votre mot de passe" maxlength="255" required>
                        <div class="invalid-feedback">Veuillez saisir un mot de passe.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="cli_mdp_confirm" class="form-label fw-semibold small">Confirmation du mot de passe</label>
                        <input type="password" class="form-control" id="cli_mdp_confirm" name="cli_mdp_confirm"
                               placeholder="Confirmez votre mot de passe" maxlength="255" required>
                        <div class="invalid-feedback" id="mdp-confirm-feedback">Les mots de passe ne correspondent pas.</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="cli_telephone" class="form-label fw-semibold small">Téléphone</label>
                        <input type="tel" class="form-control" id="cli_telephone" name="cli_telephone"
                               placeholder="0612345678" maxlength="16"
                               pattern="[\d\s.\-]+" required>
                        <div class="form-text text-muted" style="font-size:0.75rem;">Chiffres uniquement, 16 max (espaces/tirets autorisés à la saisie).</div>
                        <div class="invalid-feedback" id="tel-feedback">Téléphone invalide. Chiffres, espaces, points ou tirets uniquement (16 chiffres max une fois nettoyé).</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="cli_ville" class="form-label fw-semibold small">Ville</label>
                        <input type="text" class="form-control" id="cli_ville" name="cli_ville"
                               placeholder="Ex: Caen" maxlength="64" required>
                        <div class="invalid-feedback">La ville est requise (64 caractères max).</div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="dep_num" class="form-label fw-semibold small">Département de résidence</label>
                    <select class="form-select" id="dep_num" name="dep_num" required>
                        <option value="" selected disabled>Choisir un département...</option>
                        <option value="14">Calvados (14)</option>
                        <option value="50">Manche (50)</option>
                        <option value="61">Orne (61)</option>
                        <option value="27">Eure (27)</option>
                        <option value="76">Seine-Maritime (76)</option>
                    </select>
                    <div class="invalid-feedback">Veuillez choisir un département.</div>
                </div>

                <div class="d-flex flex-column gap-2">
                    <?php
                        if ($_SESSION["error"] == "alreadyExists") {
                            echo "<p class=\"text-center text-danger\">Un utilisateur avec cette adresse e-mail existe déjà !</p>";
                        }
                        unset($_SESSION["error"]);
                    ?>
                    <button type="submit" class="btn w-100 fw-semibold text-white" style="background-color: #C62828;">Créer mon compte</button>
                    <a href="connexion.php" class="btn w-100 fw-semibold" style="border: 1.5px solid #C62828; color: #C62828;">J'ai déjà un compte</a>
                </div>

            </form>

        </div>
    </div>

    <footer class="bg-light text-center py-3 border-top text-muted small mt-auto">
        <div class="container">
            <p class="mb-0">© 2026 Viking Transport — Développé par l'agence <strong>Asgard Tech</strong></p>
        </div>
    </footer>

    <script>
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        function validerPrenom() {
            const el = document.getElementById('cli_prenom');
            if (!el.value.trim() || el.value.length > 64) {
                el.classList.replace('is-valid', 'is-invalid') || el.classList.add('is-invalid');
                return false;
            }
            el.classList.replace('is-invalid', 'is-valid') || el.classList.add('is-valid');
            return true;
        }

        function validerNom() {
            const el = document.getElementById('cli_nom');
            if (!el.value.trim() || el.value.length > 64) {
                el.classList.replace('is-valid', 'is-invalid') || el.classList.add('is-invalid');
                return false;
            }
            el.classList.replace('is-invalid', 'is-valid') || el.classList.add('is-valid');
            return true;
        }

        function validerCourriel() {
            const el = document.getElementById('cli_courriel');
            const fb = document.getElementById('courriel-feedback');
            if (!emailRegex.test(el.value) || el.value.length > 64) {
                fb.textContent = el.value.length > 64
                    ? `Adresse trop longue : ${el.value.length}/64 caractères.`
                    : 'Adresse e-mail invalide (le symbole @ est obligatoire).';
                el.classList.replace('is-valid', 'is-invalid') || el.classList.add('is-invalid');
                return false;
            }
            el.classList.replace('is-invalid', 'is-valid') || el.classList.add('is-valid');
            return true;
        }

        function validerMdp() {
            const el = document.getElementById('cli_mdp');
            if (!el.value.trim()) {
                el.classList.remove('is-valid');
                el.classList.add('is-invalid');
                return false;
            }
            el.classList.remove('is-invalid');
            el.classList.add('is-valid');
            return true;
        }

        function validerMdpConfirm() {
            const mdp     = document.getElementById('cli_mdp');
            const confirm = document.getElementById('cli_mdp_confirm');
            const fb      = document.getElementById('mdp-confirm-feedback');

            if (!confirm.value.trim()) {
                fb.textContent = 'Veuillez confirmer votre mot de passe.';
                confirm.classList.remove('is-valid');
                confirm.classList.add('is-invalid');
                return false;
            }

            if (confirm.value !== mdp.value) {
                fb.textContent = 'Les mots de passe ne correspondent pas.';
                confirm.classList.remove('is-valid');
                confirm.classList.add('is-invalid');
                return false;
            }

            confirm.classList.remove('is-invalid');
            confirm.classList.add('is-valid');
            return true;
        }

        function validerTelephone(nettoyerValeur = false) {
            const el = document.getElementById('cli_telephone');
            const fb = document.getElementById('tel-feedback');
            const telNettoye = el.value.replace(/[\s.\-]/g, '');
            if (!/^\d+$/.test(telNettoye) || telNettoye.length > 16 || telNettoye.length < 7) {
                fb.textContent = telNettoye.length > 16
                    ? `Numéro trop long : ${telNettoye.length} chiffres (16 max).`
                    : 'Téléphone invalide. Saisissez au moins 7 chiffres.';
                el.classList.replace('is-valid', 'is-invalid') || el.classList.add('is-invalid');
                return false;
            }
            if (nettoyerValeur) el.value = telNettoye;
            el.classList.replace('is-invalid', 'is-valid') || el.classList.add('is-valid');
            return true;
        }

        function validerVille() {
            const el = document.getElementById('cli_ville');
            if (!el.value.trim() || el.value.length > 64) {
                el.classList.replace('is-valid', 'is-invalid') || el.classList.add('is-invalid');
                return false;
            }
            el.classList.replace('is-invalid', 'is-valid') || el.classList.add('is-valid');
            return true;
        }

        function validerDep() {
            const el = document.getElementById('dep_num');
            if (!el.value) {
                el.classList.replace('is-valid', 'is-invalid') || el.classList.add('is-invalid');
                return false;
            }
            el.classList.replace('is-invalid', 'is-valid') || el.classList.add('is-valid');
            return true;
        }

        document.getElementById('cli_nom').addEventListener('input', function () {
            const pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);
        });

        document.getElementById('cli_telephone').addEventListener('input', function () {
            this.value = this.value.replace(/[^\d\s.\-]/g, '');
        });

        document.getElementById('cli_mdp').addEventListener('blur', function () {
            validerMdp();
            if (document.getElementById('cli_mdp_confirm').value) validerMdpConfirm();
        });

        document.getElementById('cli_prenom').addEventListener('blur', validerPrenom);
        document.getElementById('cli_nom').addEventListener('blur', validerNom);
        document.getElementById('cli_courriel').addEventListener('blur', validerCourriel);
        document.getElementById('cli_telephone').addEventListener('blur', () => validerTelephone(false));
        document.getElementById('cli_ville').addEventListener('blur', validerVille);
        document.getElementById('dep_num').addEventListener('blur', validerDep);
        document.getElementById('cli_mdp_confirm').addEventListener('blur', validerMdpConfirm);

        document.getElementById('inscriptionForm').addEventListener('submit', function (e) {
            const valid = [
                validerPrenom(),
                validerNom(),
                validerCourriel(),
                validerMdp(),
                validerMdpConfirm(),
                validerTelephone(true),
                validerVille(),
                validerDep()
            ].every(Boolean);

            if (!valid) e.preventDefault();
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
