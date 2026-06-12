<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_GET['depart']) && isset($_GET['arrivee']) && empty($_POST)) {
    $_SESSION['client_info'] = [
        'nom' => 'Invité',
        'prenom' => '',
        'villes' => [$_GET['depart'], $_GET['arrivee']]
    ];
    header("Location: reservation_choix.php");
    exit;
}

if (!isset($_SESSION['client_info'])) {
    header('Location: reservation_trajet.php');
    exit;
}

$host = "harpagon.unicaen.fr";
$port = "1521";
$sid = "info";
$user = "agile_5";
$password = "lemeilleurgroupe";
$dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$sid)))";

$client = $_SESSION['client_info'];
$villesCodes = $client['villes'];

try {
    $conn = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Noms des villes
    $placeholders = rtrim(str_repeat('?,', count($villesCodes)), ',');
    $stmt = $conn->prepare("SELECT COM_CODE_INSEE, COM_NOM FROM VIK_COMMUNE WHERE COM_CODE_INSEE IN ($placeholders)");
    $stmt->execute($villesCodes);
    $villesNoms = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $villesNomListe = array_map(fn($c) => $villesNoms[$c] ?? $c, $villesCodes);
    $trajetStr = implode(' → ', $villesNomListe);

    // ------------------------------------------------------------
    // Récupération de tous les arcs avec TOUS les horaires
    // ------------------------------------------------------------
    $allEdges = [];
    $arcsSimple = [];
    $stmt = $conn->query("
        SELECT 
            TRIM(LIG_NUM) AS LIGNE,
            TRIM(COM_CODE_INSEE_ARRET) AS DEPART,
            TRIM(COM_CODE_INSEE_SUIVANT) AS ARRIVEE,
            NOE_DISTANCE_PROCHAIN AS DISTANCE,
            NOE_DUREE_PROCHAIN AS DUREE,
            TO_CHAR(NOE_HEURE_PASSAGE, 'HH24:MI') AS HEURE
        FROM VIK_NOEUD
        ORDER BY DEPART, ARRIVEE, HEURE
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dep = $row['DEPART'];
        $arr = $row['ARRIVEE'];
        $arc = [
            'ligne' => $row['LIGNE'],
            'dist'  => (float)$row['DISTANCE'],
            'duree' => (float)$row['DUREE'],
            'heure' => $row['HEURE']
        ];
        if (!isset($allEdges[$dep][$arr])) {
            $allEdges[$dep][$arr] = [];
            $arcsSimple[$dep][$arr] = $arc;
        }
        $allEdges[$dep][$arr][] = $arc;
    }
    foreach ($allEdges as $dep => $arrs) {
        foreach ($arrs as $arr => $arcs) {
            usort($allEdges[$dep][$arr], fn($a, $b) => $a['heure'] <=> $b['heure']);
        }
    }

    // ------------------------------------------------------------
    // Fonctions Dijkstra et Yen
    // ------------------------------------------------------------
    function dijkstraPath($graph, $start, $end, $weightField, $heureMin)
    {
        $dist = [];
        $prev = [];
        $nodes = [];
        foreach ($graph as $from => $toList) {
            $nodes[] = $from;
            foreach ($toList as $to => $arcs) $nodes[] = $to;
        }
        $nodes = array_unique($nodes);
        foreach ($nodes as $n) {
            $dist[$n] = INF;
            $prev[$n] = null;
        }
        $dist[$start] = 0;
        $Q = $nodes;
        while (!empty($Q)) {
            $u = null;
            $minD = INF;
            foreach ($Q as $n) if ($dist[$n] < $minD) {
                $minD = $dist[$n];
                $u = $n;
            }
            if ($u === null) break;
            $Q = array_values(array_diff($Q, [$u]));
            if ($u == $end) break;
            if (isset($graph[$u])) {
                foreach ($graph[$u] as $v => $arcs) {
                    if (in_array($v, $Q)) {
                        $bestWeight = INF;
                        foreach ($arcs as $arc) {
                            if ($u == $start && $heureMin !== null && $arc['heure'] < $heureMin) continue;
                            $w = $arc[$weightField];
                            if ($w < $bestWeight) $bestWeight = $w;
                        }
                        if ($bestWeight != INF) {
                            $alt = $dist[$u] + $bestWeight;
                            if ($alt < $dist[$v]) {
                                $dist[$v] = $alt;
                                $prev[$v] = $u;
                            }
                        }
                    }
                }
            }
        }
        $path = [];
        $u = $end;
        if ($prev[$u] !== null || $u == $start) {
            while ($u !== null) {
                array_unshift($path, $u);
                $u = $prev[$u];
            }
        }
        return $path;
    }

    function computePathCost($graph, $path, $weightField, $heureMin)
    {
        $total = 0;
        for ($i = 0; $i < count($path) - 1; $i++) {
            $dep = $path[$i];
            $arr = $path[$i + 1];
            $best = INF;
            foreach ($graph[$dep][$arr] as $arc) {
                if ($i == 0 && $heureMin !== null && $arc['heure'] < $heureMin) continue;
                $w = $arc[$weightField];
                if ($w < $best) $best = $w;
            }
            $total += $best;
        }
        return $total;
    }

    function yenKSP($graph, $start, $end, $weightField, $heureMin, $K = 5)
    {
        $A = [];
        $B = [];
        $firstPath = dijkstraPath($graph, $start, $end, $weightField, $heureMin);
        if (empty($firstPath)) return [];
        $A[] = ['path' => $firstPath, 'cost' => computePathCost($graph, $firstPath, $weightField, $heureMin)];
        for ($k = 1; $k < $K; $k++) {
            $prevPath = $A[$k - 1]['path'];
            for ($i = 0; $i < count($prevPath) - 1; $i++) {
                $spurNode = $prevPath[$i];
                $rootPath = array_slice($prevPath, 0, $i + 1);
                $tmpGraph = $graph;
                foreach ($A as $a) {
                    if (array_slice($a['path'], 0, $i + 1) == $rootPath && isset($a['path'][$i + 1])) {
                        $nextNode = $a['path'][$i + 1];
                        if (isset($tmpGraph[$spurNode][$nextNode])) unset($tmpGraph[$spurNode][$nextNode]);
                    }
                }
                for ($j = 0; $j < $i; $j++) {
                    $from = $rootPath[$j];
                    $to = $rootPath[$j + 1];
                    if (isset($tmpGraph[$from][$to])) unset($tmpGraph[$from][$to]);
                }
                $spurPath = dijkstraPath($tmpGraph, $spurNode, $end, $weightField, $heureMin);
                if (!empty($spurPath)) {
                    $totalPath = array_merge($rootPath, array_slice($spurPath, 1));
                    $cost = computePathCost($graph, $totalPath, $weightField, $heureMin);
                    $B[] = ['path' => $totalPath, 'cost' => $cost];
                }
            }
            if (empty($B)) break;
            usort($B, fn($a, $b) => $a['cost'] - $b['cost']);
            $A[] = array_shift($B);
        }
        return $A;
    }

    // ------------------------------------------------------------
    // Récupération des paramètres date/heure
    // ------------------------------------------------------------
    $dateVoyage = $_GET['date'] ?? date('Y-m-d');
    $heureMin = $_GET['heure'] ?? '00:00';
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['date']) && isset($_GET['heure'])) {
        $dateVoyage = $_GET['date'];
        $heureMin = $_GET['heure'];
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['date_voyage']) && isset($_POST['heure_min'])) {
        $dateVoyage = $_POST['date_voyage'];
        $heureMin = $_POST['heure_min'];
        header("Location: reservation_choix.php?date=$dateVoyage&heure=" . urlencode($heureMin));
        exit;
    }

    // ------------------------------------------------------------
    // Génération des trajets complets
    // ------------------------------------------------------------
    $segmentsPaths = [];
    for ($idx = 0; $idx < count($villesCodes) - 1; $idx++) {
        $dep = $villesCodes[$idx];
        $arr = $villesCodes[$idx + 1];
        $hMin = ($idx == 0) ? $heureMin : '00:00';
        $paths = yenKSP($allEdges, $dep, $arr, 'duree', $hMin, 5);
        $segmentsPaths[] = $paths;
    }
    $itineraries = [[]];
    foreach ($segmentsPaths as $segPaths) {
        $new = [];
        foreach ($itineraries as $itin) {
            foreach ($segPaths as $p) {
                $newItin = $itin;
                $newItin[] = $p;
                $new[] = $newItin;
                if (count($new) >= 20) break 2;
            }
        }
        $itineraries = $new;
    }

    function buildTrajetFromCombination($comb, $arcsSimple, $allEdges, $conn, $heureMin)
    {
        $fullPath = [];
        foreach ($comb as $seg) {
            $path = $seg['path'];
            if (empty($fullPath)) $fullPath = $path;
            else $fullPath = array_merge($fullPath, array_slice($path, 1));
        }
        $valid = true;
        $lignesRaw = [];
        $distance = 0;
        $duree = 0;
        $heureDep = null;
        $cumulDuree = 0;
        for ($i = 0; $i < count($fullPath) - 1; $i++) {
            $dep = $fullPath[$i];
            $arr = $fullPath[$i + 1];
            if (!isset($arcsSimple[$dep][$arr])) {
                $valid = false;
                break;
            }
            $arcsList = $allEdges[$dep][$arr];
            $hMinSegment = ($i == 0) ? $heureMin : date('H:i', strtotime($heureMin) + $cumulDuree * 60);
            $chosenArc = null;
            foreach ($arcsList as $arc) {
                if ($arc['heure'] >= $hMinSegment) {
                    $chosenArc = $arc;
                    break;
                }
            }
            if (!$chosenArc) {
                $valid = false;
                break;
            }
            $lignesRaw[] = $chosenArc['ligne'];
            $distance += $chosenArc['dist'];
            $duree += $chosenArc['duree'];
            if ($i == 0) $heureDep = $chosenArc['heure'];
            $cumulDuree += $chosenArc['duree'];
        }
        if (!$valid || $distance > 500) return null;
        // Compression des lignes
        $compressed = [];
        $last = null;
        foreach ($lignesRaw as $l) {
            if ($l !== $last) {
                $compressed[] = $l;
                $last = $l;
            }
        }
        $heureArr = date('H:i', strtotime($heureDep) + $duree * 60);
        $prix = 0;
        $stmt = $conn->prepare("SELECT TAR_PRIX FROM VIK_TARIF WHERE :dist BETWEEN TAR_MIN_DIST AND TAR_MAX_DIST");
        $stmt->execute(['dist' => $distance]);
        $prix = $stmt->fetchColumn();
        if ($prix === false) $prix = 0;
        return [
            'path' => $fullPath,
            'lignes' => $compressed,
            'distance' => round($distance, 1),
            'duree_minutes' => $duree,
            'heure_depart' => $heureDep,
            'heure_arrivee' => $heureArr,
            'prix' => (float)$prix
        ];
    }

    $tousLesTrajets = [];
    foreach ($itineraries as $comb) {
        $t = buildTrajetFromCombination($comb, $arcsSimple, $allEdges, $conn, $heureMin);
        if ($t) $tousLesTrajets[] = $t;
    }

    // Suppression des doublons (comparaison sur le chemin)
    $unique = [];
    foreach ($tousLesTrajets as $t) {
        $key = implode('|', $t['path']);
        if (!isset($unique[$key])) $unique[$key] = $t;
    }
    $tousLesTrajets = array_values($unique);

    // Trier pour extraire plus rapide et plus court
    $rapides = $tousLesTrajets;
    usort($rapides, fn($a, $b) => $a['duree_minutes'] - $b['duree_minutes']);
    $plusRapide = $rapides[0] ?? null;
    $courts = $tousLesTrajets;
    usort($courts, fn($a, $b) => $a['distance'] - $b['distance']);
    $plusCourt = $courts[0] ?? null;
    // Les autres (tous sauf les deux premiers distincts)
    $autres = [];
    foreach ($tousLesTrajets as $t) {
        if (($plusRapide && $t !== $plusRapide) && ($plusCourt && $t !== $plusCourt)) $autres[] = $t;
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Choix du trajet - Viking Transport</title>
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
            font-family: system-ui;
        }

        html { overflow-y: scroll; }
        .trajet-card {
            border: 2px solid #e0e0e0;
            border-radius: 16px;
            transition: 0.2s;
            cursor: pointer;
            background: white;
            margin-bottom: 12px;
            padding: 1rem;
        }
        .trajet-card:hover {
            border-color: #C62828;
            transform: translateY(-2px);
        }
        .prix {
            color: #C62828;
            font-weight: bold;
            font-size: 1.5rem;
        }
        .trajets-list {
            max-height: 500px;
            overflow-y: auto;
        }
        .btn-primary {
            background-color: var(--viking-red);
            color: white;
            border: none;
            border-color: var(--viking-red);
            --bs-btn-active-bg: #9b1e1e;
            --bs-btn-active-color: var(--viking-white);
            --bs-btn-active-border-color: #9b1e1e;
            --bs-btn-focus-shadow-rgb: 198, 40, 40;
            --bs-btn-active-shadow: none;
        }
        .btn-primary:hover {
            background-color: #9b1e1e;
            color: white;
        }

        .nav-col { flex: 1; display: flex; align-items: center; }
        .nav-col.nav-center { justify-content: center; gap: 2rem; }
        .nav-col.nav-right { justify-content: flex-end; }

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
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
<?php include_once("../PHP/header.php"); ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow border-0 rounded-4 p-4 bg-white">
                <h3>Bonjour <?= htmlspecialchars($client['prenom']) ?> !</h3>
                <p>Trajet : <strong><?= htmlspecialchars($trajetStr) ?></strong></p>
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>
                <form method="GET" action="reservation_choix.php" class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Date de départ</label>
                        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($dateVoyage) ?>" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Heure min. de départ</label>
                        <input type="time" name="heure" class="form-control" value="<?= htmlspecialchars($heureMin) ?>" step="60" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">Actualiser</button>
                    </div>
                </form>
                <?php if (empty($tousLesTrajets)): ?>
                    <div class="alert alert-warning">
                        Aucun trajet trouvé pour l'heure demandée.<br>
                        Note : Les trajets de plus de 500 km ne sont pas proposés. Veuillez effectuer plusieurs voyages.
                    </div>
                <?php else: ?>
                    <form method="POST" action="choix_paiement.php" id="choixForm">
                        <input type="hidden" name="date_voyage" value="<?= htmlspecialchars($dateVoyage) ?>">
                        <input type="hidden" name="heure_min" value="<?= htmlspecialchars($heureMin) ?>">
                        <input type="hidden" name="trajet_json" id="trajet_json">
                        <h4>Trajet le plus rapide</h4>
                        <?php if ($plusRapide): ?>
                            <div class="trajet-card" onclick="submitTrajet(<?= htmlspecialchars(json_encode($plusRapide)) ?>)">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?= implode(' → ', $plusRapide['lignes']) ?></strong><br>
                                        Départ <?= $plusRapide['heure_depart'] ?> → Arrivée <?= $plusRapide['heure_arrivee'] ?><br>
                                        Durée : <?= floor($plusRapide['duree_minutes'] / 60) ?>h<?= $plusRapide['duree_minutes'] % 60 ?> • <?= $plusRapide['distance'] ?> km
                                    </div>
                                    <div class="prix"><?= number_format($plusRapide['prix'], 2, ',', ' ') ?> €</div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <h4 class="mt-3">Trajet le plus court</h4>
                        <?php if ($plusCourt): ?>
                            <div class="trajet-card" onclick="submitTrajet(<?= htmlspecialchars(json_encode($plusCourt)) ?>)">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?= implode(' → ', $plusCourt['lignes']) ?></strong><br>
                                        Départ <?= $plusCourt['heure_depart'] ?> → Arrivée <?= $plusCourt['heure_arrivee'] ?><br>
                                        Durée : <?= floor($plusCourt['duree_minutes'] / 60) ?>h<?= $plusCourt['duree_minutes'] % 60 ?> • <?= $plusCourt['distance'] ?> km
                                    </div>
                                    <div class="prix"><?= number_format($plusCourt['prix'], 2, ',', ' ') ?> €</div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <h4 class="mt-3">Tous les autres trajets disponibles</h4>
                        <div class="trajets-list">
                            <?php foreach ($autres as $t): ?>
                                <div class="trajet-card" onclick="submitTrajet(<?= htmlspecialchars(json_encode($t)) ?>)">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?= implode(' → ', $t['lignes']) ?></strong><br>
                                            <?= $t['heure_depart'] ?> → <?= $t['heure_arrivee'] ?> (<?= floor($t['duree_minutes'] / 60) ?>h<?= $t['duree_minutes'] % 60 ?>) • <?= $t['distance'] ?> km
                                        </div>
                                        <div class="prix"><?= number_format($t['prix'], 2, ',', ' ') ?> €</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include_once("../PHP/footer.php"); ?>
<script>
    function submitTrajet(trajet) {
        document.getElementById('trajet_json').value = JSON.stringify(trajet);
        document.getElementById('choixForm').submit();
    }
</script>
</body>
</html>