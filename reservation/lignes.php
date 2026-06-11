<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_username = "agile_5";
$db_password = "lemeilleurgroupe";
$db = "oci:dbname=harpagon.unicaen.fr:1521/info.harpagon.unicaen.fr;charset=AL32UTF8";

try {
    $conn = new PDO($db, $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$sqlLignes = "SELECT TRIM(LIG_NUM) AS LIG_NUM, COM_CODE_INSEE_DEBU, COM_CODE_INSEE_TERM FROM VIK_LIGNE ORDER BY LENGTH(TRIM(LIG_NUM)) ASC, LIG_NUM ASC";
$stmt = $conn->query($sqlLignes);
$lignesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sqlNoeuds = "SELECT TRIM(LIG_NUM) AS LIG_NUM, TRIM(COM_CODE_INSEE_ARRET) AS DEPART, TRIM(COM_CODE_INSEE_SUIVANT) AS ARRIVEE FROM VIK_NOEUD";
$stmt = $conn->query($sqlNoeuds);
$noeuds = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ligneNoeuds = [];
foreach ($noeuds as $n) {
    $lig = $n['LIG_NUM'];
    $dep = $n['DEPART'];
    $arr = $n['ARRIVEE'];
    if (!isset($ligneNoeuds[$lig])) {
        $ligneNoeuds[$lig] = ['departs' => [], 'arrivees' => []];
    }
    $ligneNoeuds[$lig]['departs'][$dep] = true;
    $ligneNoeuds[$lig]['arrivees'][$arr] = true;
}

$terminusParLigne = [];
foreach ($lignesData as $l) {
    $lig = $l['LIG_NUM'];
    if (isset($ligneNoeuds[$lig])) {
        $departs = $ligneNoeuds[$lig]['departs'];
        $arrivees = $ligneNoeuds[$lig]['arrivees'];
        $terminus = null;
        foreach ($arrivees as $node => $_) {
            if (!isset($departs[$node])) {
                $terminus = $node;
                break;
            }
        }
        if (!$terminus) $terminus = $l['COM_CODE_INSEE_TERM'];
    } else {
        $terminus = $l['COM_CODE_INSEE_TERM'];
    }
    $terminusParLigne[$lig] = $terminus;
}

$allVilles = [];
foreach ($lignesData as $l) {
    $allVilles[] = $l['COM_CODE_INSEE_DEBU'];
    $allVilles[] = $terminusParLigne[$l['LIG_NUM']];
}
$allVilles = array_unique($allVilles);
$allVilles = array_filter($allVilles);
if (!empty($allVilles)) {
    $placeholders = rtrim(str_repeat('?,', count($allVilles)), ',');
    $stmt = $conn->prepare("SELECT COM_CODE_INSEE, COM_NOM FROM VIK_COMMUNE WHERE COM_CODE_INSEE IN ($placeholders)");
    $stmt->execute(array_values($allVilles));
    $villesNoms = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} else {
    $villesNoms = [];
}

$lignes = [];
foreach ($lignesData as $l) {
    $lig = $l['LIG_NUM'];
    $villeDepart = $villesNoms[$l['COM_CODE_INSEE_DEBU']] ?? $l['COM_CODE_INSEE_DEBU'];
    $villeArrivee = $villesNoms[$terminusParLigne[$lig]] ?? $terminusParLigne[$lig];
    $lignes[] = [
        'LIG_NUM' => $lig,
        'VILLE_DEPART' => $villeDepart,
        'VILLE_ARRIVEE' => $villeArrivee
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Lignes - Viking Transport</title>
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
        }

        body {
            background: linear-gradient(rgba(33, 33, 33, 0.6), rgba(33, 33, 33, 0.6)), url('../images/arriere_plan_lignes.jpg') no-repeat center center;
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
            font-family: system-ui, -apple-system, sans-serif;
        }

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

        .custom-card {
            background-color: var(--viking-white);
            border: none;
            border-radius: 10px;
        }

        h2.h4 {
            color: var(--viking-dark-grey);
            font-weight: 700;
            position: relative;
            padding-left: 14px;
        }

        h2.h4::before {
            content: "";
            position: absolute;
            left: 0;
            top: 15%;
            height: 70%;
            width: 4px;
            background-color: var(--viking-red);
            border-radius: 2px;
        }

        .table-custom {
            --bs-table-bg: transparent;
            --bs-table-striped-bg: #F9FAFA;
            --bs-table-hover-bg: #F2F4F4;
            color: var(--viking-dark-grey);
        }

        .table-custom thead th {
            border-bottom: 3px solid var(--viking-red);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 14px 16px;
        }

        .table-custom tbody td {
            padding: 12px 16px;
            border-color: var(--viking-light-grey);
            vertical-align: middle;
        }

        .table-custom tbody td:first-child {
            font-weight: 700;
        }

        .btn-reserver {
            background-color: var(--viking-red);
            color: white;
            border: none;
            font-weight: 600;
            padding: 6px 16px;
            border-radius: 20px;
        }

        .btn-reserver:hover {
            background-color: var(--viking-dark-red);
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

    <main class="container mb-5">
        <section class="mb-4">
            <div class="p-4 custom-card shadow-lg">
                <h2 class="h4 mb-3">Nos lignes de car</h2>
                <p class="text-muted mb-0">Retrouvez ci-dessous l'ensemble des lignes desservant la Normandie.</p>
            </div>
        </section>

        <section class="mb-4">
            <div class="p-4 custom-card shadow-lg">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h4 mb-1">Réservation rapide</h2>
                        <p class="text-muted mb-0">Recherchez et réservez votre trajet en indiquant vos villes de départ et d'arrivée.</p>
                    </div>
                    <a href="reservation_trajet.php" class="btn btn-reserver">
                        <i class="bi bi-ticket-perforated me-1"></i> Réserver un trajet
                    </a>
                </div>
            </div>
        </section>

        <section class="mb-4">
            <div class="p-4 custom-card shadow-lg text-center">
                <h2 class="h4 mb-4 text-start">Plan du réseau Viking Transport</h2>
                <div class="bg-light p-2 rounded border d-flex justify-content-center align-items-center" style="overflow: hidden; height: 600px;">
                    <img id="carte-zoom" src="../images/carte.png" alt="Carte des lignes Viking Transport" style="height: 100%; max-width: 100%; object-fit: contain; cursor: grab; transition: transform 0.05s ease-out;">
                </div>
            </div>
        </section>

        <section id="lignes">
            <div class="p-4 custom-card shadow-lg">
                <h2 class="h4 mb-4">Liste des lignes</h2>
                <div class="table-responsive">
                    <table class="table table-custom table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Ligne</th>
                                <th>Départ</th>
                                <th>Arrivée</th>
                                <th>Horaires</th>
                                <th>Réservation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lignes as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['LIG_NUM']) ?></td>
                                    <td><?= htmlspecialchars($row['VILLE_DEPART']) ?></td>
                                    <td><?= htmlspecialchars($row['VILLE_ARRIVEE']) ?></td>
                                    <td><a href="horaires.php?ligne=<?= urlencode($row['LIG_NUM']) ?>">Voir les horaires <i class="bi bi-arrow-right"></i></a></td>
                                    <td><a href="reservation_trajet_manuel.php?ligne=<?= urlencode($row['LIG_NUM']) ?>" class="btn btn-reserver btn-sm">Réserver</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-light text-center py-3 border-top text-muted small">
        <div class="container">
            <p class="mb-0">© 2026 Viking Transport — Développé par l'agence Asgard Tech</p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/@panzoom/panzoom@4.5.1/dist/panzoom.min.js"></script>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const element = document.getElementById('carte-zoom');

            const initPanzoom = () => {
                const panzoom = Panzoom(element, {
                    maxScale: 8,
                    minScale: 1,
                    startScale: 1,
                    contain: 'outside',
                    canvas: true,
                });

                element.parentElement.addEventListener('wheel', panzoom.zoomWithWheel);

                element.addEventListener('mousedown', () => {
                    element.style.cursor = 'grabbing';
                });
                element.addEventListener('mouseup', () => {
                    element.style.cursor = 'grab';
                });
            };

            if (element.complete) {
                initPanzoom();
            } else {
                element.addEventListener('load', initPanzoom);
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>