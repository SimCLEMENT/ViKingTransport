<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "harpagon.unicaen.fr";
$port = "1521";
$sid = "info";
$user = "agile_5";
$password = "lemeilleurgroupe";
$dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$sid)))";

try {
    $conn = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $lignes = $conn->query("SELECT TRIM(LIG_NUM) AS LIGNE FROM VIK_LIGNE ORDER BY LIGNE")->fetchAll(PDO::FETCH_COLUMN);
    $ligne_requestee = isset($_GET['ligne']) ? trim($_GET['ligne']) : '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserver'])) {
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $date_form = $_POST['res_date'];
        $etapes_json = $_POST['etapes_json'] ?? '';
        
        if (empty($etapes_json)) {
            throw new Exception("Aucune étape reçue");
        }
        $etapes = json_decode($etapes_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Erreur JSON: " . json_last_error_msg());
        }
        if (empty($etapes) || !isset($etapes[0]['horaire'])) {
            throw new Exception("Données d'étape invalides");
        }
        $horaire = $etapes[0]['horaire'];

        $parcours = [];
        $distance_tot = 0;
        $duree_tot = 0; // Ajout pour calculer la durée totale
        $fullPath = [];
        $lignes_utilisees = [];

        foreach ($etapes as $idx => $etape) {
            $lig = $etape['ligne'];
            $dep = $etape['depart'];
            $arr = $etape['arrivee'];
            if ($idx === 0) $parcours[] = $dep;
            $parcours[] = $arr;

            // Ajout de NOE_DUREE_PROCHAIN dans la requête
            $stmt = $conn->prepare("
                SELECT COM_CODE_INSEE_ARRET, COM_CODE_INSEE_SUIVANT, NOE_DISTANCE_PROCHAIN, NOE_DUREE_PROCHAIN
                FROM VIK_NOEUD
                WHERE TRIM(LIG_NUM) = :ligne
                ORDER BY NOE_HEURE_PASSAGE
            ");
            $stmt->execute(['ligne' => $lig]);
            $noeuds = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $graph = [];
            
            foreach ($noeuds as $n) {
                $from = trim($n['COM_CODE_INSEE_ARRET']);
                $to = trim($n['COM_CODE_INSEE_SUIVANT']);
                $graph[$from] = [
                    'next' => $to, 
                    'dist' => (float)$n['NOE_DISTANCE_PROCHAIN'],
                    'duree' => (int)$n['NOE_DUREE_PROCHAIN'] // Récupération de la durée
                ];
            }
            
            $current = $dep;
            $segment_dist = 0;
            $segment_duree = 0; // Durée pour ce segment
            $segment_path = [$dep];
            
            while (isset($graph[$current]) && $graph[$current]['next'] != $arr) {
                $segment_dist += $graph[$current]['dist'];
                $segment_duree += $graph[$current]['duree'];
                $current = $graph[$current]['next'];
                $segment_path[] = $current;
            }
            if (isset($graph[$current]) && $graph[$current]['next'] == $arr) {
                $segment_dist += $graph[$current]['dist'];
                $segment_duree += $graph[$current]['duree'];
                $segment_path[] = $arr;
            } else {
                throw new Exception("Trajet invalide sur la ligne $lig entre $dep et $arr.");
            }
            
            $distance_tot += $segment_dist;
            $duree_tot += $segment_duree; // Cumul de la durée
            
            if ($idx == 0) $fullPath = $segment_path;
            else $fullPath = array_merge($fullPath, array_slice($segment_path, 1));
            
            $lignes_utilisees = array_merge($lignes_utilisees, array_fill(0, count($segment_path)-1, $lig));
        }

        $compressed = [];
        $last = null;
        foreach ($lignes_utilisees as $l) {
            if ($l !== $last) {
                $compressed[] = $l;
                $last = $l;
            }
        }

        $stmt = $conn->prepare("SELECT TAR_PRIX FROM VIK_TARIF WHERE :d BETWEEN TAR_MIN_DIST AND TAR_MAX_DIST");
        $stmt->execute(['d' => $distance_tot]);
        $prix = $stmt->fetchColumn();
        if ($prix === false) throw new Exception("Tarif introuvable.");

        // Calcul dynamique de l'heure d'arrivée
        $heure_arrivee = date('H:i', strtotime($horaire) + ($duree_tot * 60));

        $trajet_data = [
            'path' => $fullPath,
            'lignes' => $compressed,
            'distance' => round($distance_tot, 1),
            'duree_minutes' => $duree_tot, // Maintenant on utilise la vraie durée
            'heure_depart' => $horaire,
            'heure_arrivee' => $heure_arrivee, // Et la vraie heure d'arrivée calculée
            'prix' => (float)$prix
        ];

        $_SESSION['client_info'] = [
            'nom' => $nom,
            'prenom' => $prenom,
            'villes' => $parcours
        ];

        ?>
        <!DOCTYPE html>
        <html>
        <head><title>Redirection...</title></head>
        <body>
            <form method="POST" action="choix_paiement.php" id="redirectForm">
                <input type="hidden" name="trajet_json" value='<?= htmlspecialchars(json_encode($trajet_data), ENT_QUOTES) ?>'>
                <input type="hidden" name="date_voyage" value="<?= htmlspecialchars($date_form) ?>">
                <input type="hidden" name="heure_min" value="<?= htmlspecialchars($horaire) ?>">
                <input type="hidden" name="origine" value="manuel">
            </form>
            <script>document.getElementById('redirectForm').submit();</script>
        </body>
        </html>
        <?php
        exit;
    }

} catch (Exception $e) {
    $error_message = $e->getMessage();
}

// Préparation des données pour JavaScript
$stmt = $conn->query("SELECT TRIM(LIG_NUM) AS LIGNE FROM VIK_LIGNE ORDER BY LIGNE");
$allLignes = $stmt->fetchAll(PDO::FETCH_COLUMN);
$lignesInfo = [];
foreach ($allLignes as $lig) {
    $stmt = $conn->prepare("
        SELECT COM_CODE_INSEE_ARRET, COM_CODE_INSEE_SUIVANT, TO_CHAR(NOE_HEURE_PASSAGE, 'HH24:MI') AS HEURE
        FROM VIK_NOEUD WHERE TRIM(LIG_NUM)=:lig ORDER BY NOE_HEURE_PASSAGE
    ");
    $stmt->execute(['lig' => $lig]);
    $noeuds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $graph = [];
    $heures = [];
    $ordreCommunes = [];
    $incoming = [];
    foreach ($noeuds as $n) {
        $from = trim($n['COM_CODE_INSEE_ARRET']);
        $to = trim($n['COM_CODE_INSEE_SUIVANT']);
        if (!isset($graph[$from])) {
            $graph[$from] = ['next' => $to];
            $incoming[$to] = true;
        }
        if (!isset($heures[$from])) $heures[$from] = [];
        $heures[$from][$n['HEURE']] = true;
    }
    $start = null;
    foreach ($graph as $node => $v) {
        if (!isset($incoming[$node])) { $start = $node; break; }
    }
    if ($start) {
        $current = $start;
        $ordreCommunes = [$start];
        while (isset($graph[$current]) && $graph[$current]['next'] != $current) {
            $current = $graph[$current]['next'];
            $ordreCommunes[] = $current;
        }
    }
    $horaires = [];
    foreach ($heures as $commune => $hSet) {
        $horaires[$commune] = array_keys($hSet);
        sort($horaires[$commune]);
    }
    $communesNoms = [];
    if (!empty($ordreCommunes)) {
        $in = rtrim(str_repeat('?,', count($ordreCommunes)), ',');
        $st = $conn->prepare("SELECT COM_CODE_INSEE, COM_NOM FROM VIK_COMMUNE WHERE COM_CODE_INSEE IN ($in)");
        $st->execute($ordreCommunes);
        $communesNoms = $st->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    $lignesInfo[$lig] = [
        'ordre' => $ordreCommunes,
        'noms' => $communesNoms,
        'horaires' => $horaires
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réservation manuelle - Viking Transport</title>
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
        body { background-color: #f8f9fa; font-family: system-ui, sans-serif; }
        .custom-card { background-color: white; border-radius: 10px; }
        .btn-primary-custom { background-color: #C62828; color: white; }
        .btn-primary-custom:hover { background-color: #9b1e1e; }
        .btn-outline-primary {
            color: var(--viking-red);
            border-color: var(--viking-red);
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
    <?php include_once "../PHP/header.php"; ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="custom-card shadow-lg p-5">
                    <h3 class="fw-bold mb-4 border-start border-4 border-danger ps-3">Réservation manuelle - Construisez votre trajet</h3>
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                    <?php endif; ?>
                    <h6 class="mb-4 fw-bold" style="color: var(--viking-dark)">Renseignez les informations suivantes :</h6>
                    <form method="POST" id="reservationForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nom</label>
                                <input type="text" class="form-control" name="nom" required <?php if (isset($_SESSION["nom"])) echo 'value="' . htmlspecialchars($_SESSION["nom"]) . '"'; ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Prénom</label>
                                <input type="text" class="form-control" name="prenom" required <?php if (isset($_SESSION["prenom"])) echo 'value="' . htmlspecialchars($_SESSION["prenom"]) . '"'; ?>>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Date de réservation</label>
                            <input type="date" class="form-control" name="res_date" required min="<?= date('Y-m-d') ?>">
                        </div>

                        <div id="segments-container"></div>

                        <button type="button" id="ajouterSegmentBtn" class="btn btn-sm btn-outline-secondary mb-3">
                            <i class="bi bi-plus-circle"></i> Ajouter une correspondance (changer de ligne)
                        </button>

                        <div class="text-center">
                            <button type="submit" class="btn fw-bold btn-primary-custom w-100 py-2">Confirmer la réservation</button>
                            <a href="reservation_trajet.php" class="btn btn-link d-flex align-items-center justify-content-center gap-2 mt-3" style="text-decoration: none; font-weight: bold;"><i class="bi bi-arrow-left"></i> Retour à la recherche automatique</a>
                        </div>

                        <input type="hidden" name="reserver" value="1">
                        <input type="hidden" name="etapes_json" id="etapes_json">
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        const lignesData = <?= json_encode($lignesInfo) ?>;
        let segments = [];
        let segmentCounter = 0;
        const container = document.getElementById('segments-container');

        // Fonction pour trouver le nom d'une commune n'importe où dans les données
        function getNomCommune(code) {
            for (const info of Object.values(lignesData)) {
                if (info.noms && info.noms[code]) {
                    return info.noms[code];
                }
            }
            return code;
        }

        function getLignesDisponibles(commune) {
            const disponibles = [];
            for (const [code, info] of Object.entries(lignesData)) {
                const index = info.ordre.indexOf(commune);
                if (index !== -1 && index < info.ordre.length - 1) {
                    disponibles.push(code);
                }
            }
            return disponibles;
        }

        function updateArriveeOptions(segmentObj) {
            const info = lignesData[segmentObj.ligne];
            if (!info) return;
            const depIndex = info.ordre.indexOf(segmentObj.depart);
            segmentObj.arriveeSelect.innerHTML = '<option value="">-- Commune d\'arrivée --</option>';
            for (let i = depIndex+1; i < info.ordre.length; i++) {
                const code = info.ordre[i];
                segmentObj.arriveeSelect.innerHTML += `<option value="${code}">${info.noms[code] || code}</option>`;
            }
            segmentObj.arriveeSelect.disabled = false;
        }

        function updateHorairesPremierSegment(segmentObj) {
            const info = lignesData[segmentObj.ligne];
            if (!info || !segmentObj.depart) return;
            const heures = info.horaires[segmentObj.depart] || [];
            segmentObj.horaireSelect.innerHTML = '<option value="">-- Heure de départ --</option>';
            for (let h of heures) {
                segmentObj.horaireSelect.innerHTML += `<option value="${h}">${h}</option>`;
            }
            segmentObj.horaireSelect.disabled = false;
        }

        function ajouterSegment(prevArrivee = null, lignePredefinie = null) {
            const segmentDiv = document.createElement('div');
            segmentDiv.className = 'segment mb-4 p-3 border rounded bg-white';
            const segmentId = segmentCounter++;
            segmentDiv.dataset.id = segmentId;

            // Ligne
            const ligneSelect = document.createElement('select');
            ligneSelect.className = 'form-select mb-2';
            ligneSelect.innerHTML = '<option value="">-- Choisissez une ligne --</option>';
            for (const [code, info] of Object.entries(lignesData)) {
                ligneSelect.innerHTML += `<option value="${code}">Ligne ${code}</option>`;
            }

            // Départ
            const departSelect = document.createElement('select');
            departSelect.className = 'form-select mb-2';
            if (segments.length === 0) {
                departSelect.innerHTML = '<option value="">-- Commune de départ --</option>';
                departSelect.disabled = true;
            } else {
                departSelect.disabled = true;
                const nomCommune = getNomCommune(prevArrivee);
                departSelect.innerHTML = `<option value="${prevArrivee}">${nomCommune}</option>`;
                departSelect.value = prevArrivee;
            }

            // Arrivée
            const arriveeSelect = document.createElement('select');
            arriveeSelect.className = 'form-select mb-2';
            arriveeSelect.disabled = true;
            arriveeSelect.innerHTML = '<option value="">-- Commune d\'arrivée --</option>';

            // Horaire (seulement pour le premier)
            const horaireSelect = document.createElement('select');
            horaireSelect.className = 'form-select';
            horaireSelect.innerHTML = '<option value="">-- Heure de départ --</option>';
            horaireSelect.disabled = true;
            if (segments.length > 0) horaireSelect.style.display = 'none';

            // Bouton suppression
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-outline-danger mt-2';
            removeBtn.innerHTML = '<i class="bi bi-trash"></i> Supprimer ce segment';
            removeBtn.onclick = () => {
                segmentDiv.remove();
                segments = segments.filter(s => s.id !== segmentId);
                if (segments.length > 0) {
                    for (let i = 1; i < segments.length; i++) {
                        const prevArr = segments[i-1].arrivee;
                        if (prevArr) filtrerLignesPourSegment(segments[i], prevArr);
                    }
                }
                const firstSeg = segments[0];
                if (firstSeg && firstSeg.horaireSelect) {
                    firstSeg.horaireSelect.style.display = '';
                }
            };

            segmentDiv.appendChild(ligneSelect);
            segmentDiv.appendChild(departSelect);
            segmentDiv.appendChild(arriveeSelect);
            if (segments.length === 0) segmentDiv.appendChild(horaireSelect);
            segmentDiv.appendChild(removeBtn);
            container.appendChild(segmentDiv);

            const segmentObj = {
                id: segmentId,
                ligneSelect, departSelect, arriveeSelect, horaireSelect,
                ligne: ligneSelect.value,
                depart: (segments.length === 0) ? null : prevArrivee,
                arrivee: null,
                horaire: null
            };
            segments.push(segmentObj);

            // Premier segment
            if (segments.length === 1) {
                if (lignePredefinie && lignesData[lignePredefinie]) {
                    ligneSelect.value = lignePredefinie;
                    segmentObj.ligne = lignePredefinie;
                    const info = lignesData[lignePredefinie];
                    departSelect.innerHTML = '<option value="">-- Commune de départ --</option>';
                    for (let code of info.ordre) {
                        departSelect.innerHTML += `<option value="${code}">${info.noms[code] || code}</option>`;
                    }
                    departSelect.disabled = false;
                }
            } else {
                if (prevArrivee) {
                    filtrerLignesPourSegment(segmentObj, prevArrivee);
                }
                segmentObj.depart = prevArrivee;
            }

            // Écouteur ligne
            ligneSelect.addEventListener('change', () => {
                segmentObj.ligne = ligneSelect.value;
                const info = lignesData[segmentObj.ligne];
                if (!info) return;
                if (segments.length === 1) {
                    departSelect.innerHTML = '<option value="">-- Commune de départ --</option>';
                    for (let code of info.ordre) {
                        departSelect.innerHTML += `<option value="${code}">${info.noms[code] || code}</option>`;
                    }
                    departSelect.disabled = false;
                } else {
                    if (segmentObj.depart) {
                        updateArriveeOptions(segmentObj);
                    }
                }
            });

            // Écouteur départ (premier segment)
            if (segments.length === 1) {
                departSelect.addEventListener('change', () => {
                    segmentObj.depart = departSelect.value;
                    if (!segmentObj.depart) return;
                    updateArriveeOptions(segmentObj);
                    updateHorairesPremierSegment(segmentObj);
                });
            }

            // Écouteur arrivée
            arriveeSelect.addEventListener('change', () => {
                segmentObj.arrivee = arriveeSelect.value;
                const index = segments.findIndex(s => s.id === segmentObj.id);
                for (let i = index+1; i < segments.length; i++) {
                    const prevArr = segmentObj.arrivee;
                    if (prevArr) {
                        const nextSeg = segments[i];
                        const nom = getNomCommune(prevArr);
                        nextSeg.departSelect.innerHTML = `<option value="${prevArr}">${nom}</option>`;
                        nextSeg.departSelect.value = prevArr;
                        nextSeg.depart = prevArr;
                        filtrerLignesPourSegment(nextSeg, prevArr);
                    }
                }
            });
        }

        function filtrerLignesPourSegment(segmentObj, communePrecedente) {
            const ligneSelect = segmentObj.ligneSelect;
            const disponibles = getLignesDisponibles(communePrecedente);
            for (let i = 0; i < ligneSelect.options.length; i++) {
                const opt = ligneSelect.options[i];
                if (opt.value === "") continue;
                opt.style.display = disponibles.includes(opt.value) ? '' : 'none';
            }
            const visibleOptions = Array.from(ligneSelect.options).filter(opt => opt.value && opt.style.display !== 'none');
            if (visibleOptions.length === 0) {
                ligneSelect.disabled = true;
                segmentObj.arriveeSelect.disabled = true;
                segmentObj.arriveeSelect.innerHTML = '<option value="">-- Aucune ligne disponible --</option>';
            } else {
                ligneSelect.disabled = false;
                if (!segmentObj.ligne || !disponibles.includes(segmentObj.ligne)) {
                    ligneSelect.value = visibleOptions[0].value;
                    segmentObj.ligne = visibleOptions[0].value;
                }
                if (segmentObj.depart) {
                    updateArriveeOptions(segmentObj);
                }
            }
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', () => {
            const ligneRequestee = "<?= htmlspecialchars($ligne_requestee) ?>";
            if (ligneRequestee && lignesData[ligneRequestee]) {
                ajouterSegment(null, ligneRequestee);
            } else {
                ajouterSegment();
            }
        });

        document.getElementById('ajouterSegmentBtn').addEventListener('click', () => {
            if (segments.length === 0) {
                ajouterSegment();
                return;
            }
            const dernier = segments[segments.length-1];
            if (!dernier.arrivee) {
                alert("Veuillez d'abord sélectionner une commune d'arrivée pour le dernier segment.");
                return;
            }
            ajouterSegment(dernier.arrivee);
        });

        document.getElementById('reservationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const etapes = [];
            for (let seg of segments) {
                if (!seg.ligne || !seg.depart || !seg.arrivee) {
                    alert("Veuillez remplir tous les champs (ligne, départ, arrivée) de chaque segment.");
                    return;
                }
                if (seg === segments[0]) {
                    if (!seg.horaireSelect || seg.horaireSelect.disabled || !seg.horaireSelect.value) {
                        alert("Veuillez choisir une heure de départ pour le premier segment.");
                        return;
                    }
                    seg.horaire = seg.horaireSelect.value;
                }
                if (seg.depart === seg.arrivee) {
                    alert("Les communes de départ et d'arrivée doivent être différentes.");
                    return;
                }
                etapes.push({
                    ligne: seg.ligne,
                    depart: seg.depart,
                    arrivee: seg.arrivee,
                    horaire: seg.horaire ? seg.horaire : null
                });
            }
            if (etapes.length === 0) {
                alert("Ajoutez au moins un segment.");
                return;
            }
            document.getElementById('etapes_json').value = JSON.stringify(etapes);
            console.log("Envoi des étapes:", etapes);
            this.submit();
        });
    </script>
    <?php include_once("../PHP/footer.php"); ?>
</body>
</html>