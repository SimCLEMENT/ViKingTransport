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

            min-height: 400px;
            width: 100%;

            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;

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
<body class="d-flex flex-column min-vh-100">

<?php include_once "PHP/header.php"; ?>

<div class="container-fluid p-0 flex-grow-1 d-flex flex-column">
    <div class="hero-section flex-grow-1 text-center">
        <pre>
            <h1>Nous contacter</h1>
            <h2><a class="link-danger" href="tel:0784716389">+33 7 84 71 63 89</a></h2<br><h2><a class="link-danger" href="mailto:kylian.langlois@etu.unicaen.fr">support@viking.transport</a></h2>
        </pre>
    </div>
</div>

<?php include_once "PHP/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>