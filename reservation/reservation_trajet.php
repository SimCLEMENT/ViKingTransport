<?php
session_start();

$host = "harpagon.unicaen.fr";
$port = "1521";
$sid = "info";
$user = "agile_5";
$password = "lemeilleurgroupe";

$dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$sid)))";

try {
    $conn = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Récupération des communes pour les listes déroulantes
    $communes = $conn->query("SELECT TRIM(COM_CODE_INSEE) AS CODE, COM_NOM FROM VIK_COMMUNE ORDER BY COM_NOM ASC")->fetchAll(PDO::FETCH_KEY_PAIR);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $parcours = [];
        $parcours[] = $_POST['depart'];
        if (!empty($_POST['etapes'])) {
            foreach ($_POST['etapes'] as $etape) {
                if (!empty($etape)) $parcours[] = $etape;
            }
        }
        $parcours[] = $_POST['arrivee'];

        // Vérification : on interdit uniquement si départ == arrivée ET aucune étape
        $hasEtape = count($parcours) > 2;
        if (!$hasEtape && $parcours[0] == $parcours[1]) {
            $error_message = "La ville de départ et la ville d'arrivée ne peuvent pas être identiques lorsqu'aucune étape n'est ajoutée.";
        } else {
            $_SESSION['client_info'] = [
                'nom'    => $_POST['nom'],
                'prenom' => $_POST['prenom'],
                'villes' => $parcours
            ];
            header("Location: reservation_choix.php");
            exit;
        }
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

$get_depart = isset($_GET['depart']) ? $_GET['depart'] : '';
$get_arrivee = isset($_GET['arrivee']) ? $_GET['arrivee'] : '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Planifier un trajet - Viking Transport</title>
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

        html { overflow-y: scroll; }
        
        body {
            background-color: #f8f9fa;
            font-family: system-ui, sans-serif;
        }
        .btn-primary {
            background-color: var(--viking-red);
            color: white;
            border: none;
        }
        .btn-primary:hover {
            background-color: #9b1e1e;
            color: white;
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

        .nav-col { flex: 1; display: flex; align-items: center; }
        .nav-col.nav-center { justify-content: center; gap: 2rem; }
        .nav-col.nav-right { justify-content: flex-end; }
        .nav-link { color: var(--viking-red); }
        .nav-link:hover { color: var(--viking-dark-red); }
        .nav-link.active { color: var(--viking-dark-red) !important; font-weight: bold; }

    </style>
</head>
<body class="d-flex flex-column min-vh-100">
<?php include_once("../PHP/header.php"); ?>
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card shadow-lg border-0 p-5 rounded-4">
                    <h3 class="fw-bold mb-4 border-start border-4 border-danger ps-3">Où souhaitez-vous aller ?</h3>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                    <?php endif; ?>
                    <h6 class="mb-4 fw-bold" style="color: var(--viking-dark)">Renseignez les informations suivantes :</h6>
                    <form method="POST">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted">Nom</label>
                                <input type="text" class="form-control" name="nom" required <?php if (isset($_SESSION["nom"])) echo 'value="' . $_SESSION["nom"] . '"'; ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted">Prénom</label>
                                <input type="text" class="form-control" name="prenom" required <?php if (isset($_SESSION["prenom"])) echo 'value="' . $_SESSION["prenom"] . '"'; ?>>
                            </div>
                        </div>

                        <div class="bg-light p-4 rounded-3 mb-4 border">
                            <div class="mb-3">
                                <label class="form-label fw-bold"><i class="bi bi-geo-alt-fill text-danger"></i> Ville de départ</label>
                                <select class="form-select" name="depart" required>
                                    <option value="">Sélectionnez le départ...</option>
                                    <?php foreach ($communes as $code => $nom): ?>
                                        <option value="<?= $code ?>" <?= ($code == $get_depart) ? 'selected' : '' ?>><?= htmlspecialchars($nom) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div id="etapes-container"></div>

                            <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="addEtape()">
                                <i class="bi bi-plus-circle"></i> Ajouter une ville étape
                            </button>

                            <div class="mb-2">
                                <label class="form-label fw-bold"><i class="bi bi-flag-fill text-success"></i> Ville d'arrivée</label>
                                <select class="form-select" name="arrivee" required>
                                    <option value="">Sélectionnez l'arrivée...</option>
                                    <?php foreach ($communes as $code => $nom): ?>
                                        <option value="<?= $code ?>" <?= ($code == $get_arrivee) ? 'selected' : '' ?>><?= htmlspecialchars($nom) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3 fs-5 fw-bold rounded-3 shadow-sm">
                            Voir les trajets <i class="bi bi-arrow-right"></i>
                        </button>
                    </form>

                    <div class="mt-4 text-center">
                        <a href="reservation_trajet_manuel.php" class="btn btn-outline-secondary w-100 py-2">
                            <i class="bi bi-pencil-square"></i> Choisir manuellement mes trajets (par ligne)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>


    <script>
        const communes = <?= json_encode($communes) ?>;

        function addEtape() {
            let options = '<option value="">Sélectionnez une étape...</option>';
            for (let code in communes) {
                options += `<option value="${code}">${communes[code]}</option>`;
            }

            const html = `
                <div class="mb-3 ps-4 border-start border-2 border-secondary position-relative">
                    <i class="bi bi-x-circle-fill position-absolute text-danger" style="top: 35px; right: 10px; cursor: pointer;" onclick="this.parentElement.remove()"></i>
                    <label class="form-label fw-bold text-muted"><i class="bi bi-signpost-2"></i> Ville étape</label>
                    <select class="form-select" name="etapes[]" required>${options}</select>
                </div>
            `;
            document.getElementById('etapes-container').insertAdjacentHTML('beforeend', html);
        }
    </script>
<?php include_once "../PHP/footer.php"; ?>
</body>
</html>