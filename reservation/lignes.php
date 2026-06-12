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

$sqlCoords = "SELECT COM_CODE_INSEE, COM_NOM, LAT, LNG FROM VIK_COMMUNE WHERE LAT IS NOT NULL";
$stmt = $conn->query($sqlCoords);
$communesCoords = $stmt->fetchAll(PDO::FETCH_ASSOC);


foreach ($communesCoords as &$c) {
    $c['LAT'] = (float) str_replace(',', '.', $c['LAT']);
    $c['LNG'] = (float) str_replace(',', '.', $c['LNG']);
}
$communesCoordsJson = json_encode($communesCoords);

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

$lignesOrdonnees = [];
foreach ($ligneNoeuds as $lig => $data) {
    $departs = array_keys($ligneNoeuds[$lig]['departs']);
    $arrivees = array_keys($ligneNoeuds[$lig]['arrivees']);
    $debut = null;
    foreach ($departs as $d) {
        if (!in_array($d, $arrivees)) {
            $debut = $d;
            break;
        }
    }
    if (!$debut) $debut = $departs[0] ?? null;
    if (!$debut) continue;


    $suivantMap = [];
    foreach ($noeuds as $n) {
        if ($n['LIG_NUM'] === $lig) {
            $suivantMap[$n['DEPART']] = $n['ARRIVEE'];
        }
    }

    $ordre = [$debut];
    $current = $debut;
    $visited = [$debut => true];
    while (isset($suivantMap[$current]) && !isset($visited[$suivantMap[$current]])) {
        $current = $suivantMap[$current];
        $visited[$current] = true;
        $ordre[] = $current;
    }
    $lignesOrdonnees[$lig] = $ordre;
}

$lignesOrdonneesJson = json_encode($lignesOrdonnees);

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

$lignesJson = json_encode($lignes);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Lignes - Viking Transport</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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

    <main class="container mb-5 mt-4">
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
                <div id="map" style="height: 600px; border-radius: 8px;"></div>
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

    <?php include_once("../PHP/footer.php"); ?>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const map = L.map('map', {
            minZoom: 6,
            maxBounds: [
                [41.0, -5.5],
                [51.5, 10.0] 
            ],
            maxBoundsViscosity: 1.0 
        }).setView([49.1, -0.4], 8);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        const communes = <?= $communesCoordsJson ?>;
        const lignesOrdonnees = <?= $lignesOrdonneesJson ?>;
        const lignesInfo = <?= $lignesJson ?>;

        const communeIndex = {};
        communes.forEach(c => {
            communeIndex[c.COM_CODE_INSEE] = c;
        });

        function couleurLigne(ligNum) {
            let hash = 0;
            for (let i = 0; i < ligNum.length; i++) {
                hash = ligNum.charCodeAt(i) + ((hash << 5) - hash);
            }
            const hue = Math.abs(hash) % 360;
            return `hsl(${hue}, 75%, 42%)`;
        }

        Object.entries(lignesOrdonnees).forEach(([ligNum, arrets]) => {
            const coords = arrets
                .map(code => communeIndex[code])
                .filter(c => c && c.LAT && c.LNG)
                .map(c => [c.LAT, c.LNG]);

            if (coords.length < 2) return;

            const couleur = couleurLigne(ligNum);
            const ligInfo = lignesInfo.find(l => l.LIG_NUM === ligNum);
            const label = ligInfo ?
                `Ligne ${ligNum} : ${ligInfo.VILLE_DEPART} → ${ligInfo.VILLE_ARRIVEE}` :
                `Ligne ${ligNum}`;

            L.polyline(coords, {
                color: couleur,
                weight: 3,
                opacity: 0.85
            }).addTo(map).bindPopup(`<strong style="color:${couleur}">${label}</strong>`);
        });

        communes.forEach(c => {
            L.marker([c.LAT, c.LNG], {
                icon: L.divIcon({
                    className: '',
                    html: `<div style="
                width: 10px; height: 10px;
                background: #da2121;
                border: 2px solid white;
                border-radius: 50%;
                box-shadow: 0 2px 6px rgba(0,0,0,0.4);"></div>`,
                    iconSize: [10, 10],
                    iconAnchor: [5, 5]
                })
            }).addTo(map).bindPopup(`
        <div style="text-align:center; min-width:120px;">
            <strong style="color:#333;">${c.COM_NOM}</strong>
        </div>
    `);
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>