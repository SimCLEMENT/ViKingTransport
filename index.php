<?php
session_start();

// Connexion à la base pour récupérer les communes
$host = "harpagon.unicaen.fr";
$port = "1521";
$sid = "info";
$user = "agile_5";
$password = "lemeilleurgroupe";
$dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$sid)))";

$communes = [];
try {
    $conn = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $communes = $conn->query("SELECT TRIM(COM_CODE_INSEE) AS CODE, COM_NOM FROM VIK_COMMUNE ORDER BY COM_NOM ASC")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    // En cas d'erreur, on laisse vide (ou on affiche un message)
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Viking Transport - Accueil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /* Vos styles existants */
        :root {
            --viking-red: #C62828;
            --viking-dark-red: #9b1e1e;
            --viking-dark-grey: #706767;
            --viking-bg-grey: #8A8181;
            --viking-light-grey: #E5E8E8;
            --viking-white: #FFFFFF;
            --viking-dark: #212121;
        }
        html { overflow-y: scroll; }
        body { background-color: #f8f9fa; font-family: system-ui, -apple-system, sans-serif; }
        header.site-header {
            background-color: var(--viking-white);
            border-bottom: 5px solid var(--viking-red);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        .nav-link { color: var(--viking-red); }
        .nav-link:hover { color: var(--viking-dark-red); }
        .nav-link.active { color: var(--viking-dark-red) !important; font-weight: bold; }
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
        .hero-section {
            background: linear-gradient(rgba(33, 33, 33, 0.6), rgba(33, 33, 33, 0.6)),
                url('images/arriere_plan_index.jpg') no-repeat center center;
            background-size: cover;
            color: white;
            padding: 90px 0;
        }
        .btn-viking { background-color: var(--viking-red); color: white; }
        .btn-viking:hover { background-color: var(--viking-dark-red); color: white; }
        .text-viking-dark { color: var(--viking-dark); }
        .nav-col { flex: 1; display: flex; align-items: center; }
        .nav-col.nav-center { justify-content: center; gap: 2rem; }
        .nav-col.nav-right { justify-content: flex-end; }
    </style>
</head>
<body>

<?php include_once "PHP/header.php"; ?>

<div class="hero-section text-center mb-5">
    <div class="container">
        <?php if (isset($_SESSION["id"]) && isset($_SESSION["prenom"])): ?>
            <h2 class="display-5 fw-bold text-warning mb-3">
                Bonjour <?php echo htmlspecialchars($_SESSION["prenom"]); ?> !
            </h2>
            <p class="mb-3">
                <a href="/compte/compte.php" class="btn btn-sm btn-outline-light rounded-pill px-3">
                    <i class="bi bi-eye me-1"></i>Voir mes informations et points fidélité
                </a>
            </p>
        <?php endif; ?>

        <h1 class="display-4 fw-bold mb-3">Voyagez sereinement en Normandie</h1>
        <p class="lead mb-4">Réservez votre car régional en quelques clics</p>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card p-4 shadow text-dark bg-white">
                    <form method="GET" action="reservation/reservation_choix.php" class="row g-3 align-items-end text-start">
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Commune de départ</label>
                            <select name="depart" class="form-select" required>
                                <option value="">Sélectionnez une ville...</option>
                                <?php foreach ($communes as $code => $nom): ?>
                                    <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($nom) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Commune d'arrivée</label>
                            <select name="arrivee" class="form-select" required>
                                <option value="">Sélectionnez une destination...</option>
                                <?php foreach ($communes as $code => $nom): ?>
                                    <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($nom) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-viking w-100 py-2 fw-bold">Rechercher un trajet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Le reste de la page (cards, inscription, footer) reste inchangé -->
<main class="container mb-5">
    <div class="row g-4 text-center justify-content-center">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm p-3 bg-white">
                <div class="card-body">
                    <div class="mb-3 text-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-map" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M15.817.113A.5.5 0 0 1 16 .5v14a.5.5 0 0 1-.402.49l-5 1a.5.5 0 0 1-.196 0L5.5 15.01l-4.902.98A.5.5 0 0 1 0 15.5v-14a.5.5 0 0 1 .402-.49l5-1a.5.5 0 0 1 .196 0L10.5.99l4.902-.98a.5.5 0 0 1 .415.103M10 1.91l-4-.8v12.98l4 .8zm1 12.98 4-.8V1.11l-4 .8zm-6-.8V1.11l-4 .8v12.98z" />
                        </svg>
                    </div>
                    <h3 class="h5 fw-bold text-viking-dark">Lignes & Réseau</h3>
                    <p class="text-muted small">Consultez l'ensemble de nos lignes de cars normands traversant la région.</p>
                    <a href="#" class="btn btn-outline-secondary btn-sm mt-2">Voir le plan</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm p-3 bg-white">
                <div class="card-body">
                    <div class="mb-3 text-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-clock" viewBox="0 0 16 16">
                            <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z" />
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                        </svg>
                    </div>
                    <h3 class="h5 fw-bold text-viking-dark">Horaires en temps réel</h3>
                    <p class="text-muted small">Ne ratez plus jamais votre car. Visualisez les fiches horaires par portion de ligne.</p>
                    <a href="#" class="btn btn-outline-secondary btn-sm mt-2">Vérifier un horaire</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm p-3 bg-white">
                <div class="card-body">
                    <div class="mb-3 text-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-tags" viewBox="0 0 16 16">
                            <path d="M3 2v4.586l7 7L14.586 9l-7-7zM2 2a1 1 0 0 1 1-1h4.586a1 1 0 0 1 .707.293l7 7a1 1 0 0 1 0 1.414l-4.586 4.586a1 1 0 0 1-1.414 0l-7-7A1 1 0 0 1 2 6.586z" />
                            <path d="M5.5 5.5a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1m0 1a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3M1 7.086a1 1 0 0 0 .293.707L8.75 15.25l-.043.043a1 1 0 0 1-1.414 0l-7-7A1 1 0 0 1 0 7.586V3a1 1 0 0 1 1-1z" />
                        </svg>
                    </div>
                    <h3 class="h5 fw-bold text-viking-dark">Grille Tarifaire</h3>
                    <p class="text-muted small">Consultez notre tarification simple et transparente calculée selon la distance parcourue.</p>
                    <a href="tarif.html" class="btn btn-outline-secondary btn-sm mt-2">Découvrir les tarifs</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php if (!isset($_SESSION["id"])): ?>
    <section id="inscription" class="bg-dark text-white py-5">
        <div class="container text-center">
            <h2 class="text-white mb-3">Rejoignez le programme de fidélité Viking</h2>
            <p class="lead text-secondary mb-4">Créez un compte gratuitement pour cumuler des points à chaque voyage et débloquer des réductions sur vos prochains billets !</p>
            <a href="/auth/formulaire_site.php" class="btn btn-viking btn-lg fw-bold px-4">Créer mon compte en 2 minutes</a>
        </div>
    </section>
<?php endif; ?>

<footer class="bg-light text-center py-3 border-top text-muted small">
    <div class="container">
        <p class="mb-0">© 2026 Viking Transport - Développé par l'agence <strong>Asgard Tech</strong></p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>