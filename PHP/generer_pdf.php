<?php
session_start();
$res_num = $_GET['res_num'] ?? '';
if (empty($res_num)) {
    die("Numéro de réservation manquant.");
}
require('../PHP/fpdf.php');

$host = "harpagon.unicaen.fr";
$port = "1521";
$sid = "info";
$user = "agile_5";
$password = "lemeilleurgroupe";
$dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$sid)))";

try {
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_CASE => PDO::CASE_UPPER
    ]);

    // 1. MODIFICATION SQL : Ajout de r.COM_CODE_INSEE_DEPART et r.COM_CODE_INSEE_ARRIVEE
    $sql = "
        SELECT 
            r.RES_NUM, r.RES_DATE, r.RES_PRIX_TOT, r.RES_NB_POINTS, r.RES_HEURE,
            r.CLI_NOM, r.CLI_NUM, r.CLI_PRENOM,
            r.COM_CODE_INSEE_DEPART, r.COM_CODE_INSEE_ARRIVEE,
            c1.COM_NOM AS VILLE_DEPART,
            c2.COM_NOM AS VILLE_ARRIVEE
        FROM VIK_RESERVATION r
        LEFT JOIN VIK_COMMUNE c1 ON r.COM_CODE_INSEE_DEPART = c1.COM_CODE_INSEE
        LEFT JOIN VIK_COMMUNE c2 ON r.COM_CODE_INSEE_ARRIVEE = c2.COM_CODE_INSEE
        WHERE r.RES_NUM = :res_num
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['res_num' => $res_num]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$res) die("Réservation introuvable.");

    // Récupération des étapes avec durée
    $sql_etapes = "
        SELECT e.LIG_NUM, 
               e.COM_CODE_INSEE_DEPART, 
               e.COM_CODE_INSEE_ARRIVEE,
               TO_CHAR(e.ETA_HEURE, 'HH24:MI') AS ETA_HEURE,
               (SELECT NOE_DUREE_PROCHAIN 
                FROM VIK_NOEUD n 
                WHERE n.LIG_NUM = e.LIG_NUM 
                  AND n.COM_CODE_INSEE_ARRET = e.COM_CODE_INSEE_DEPART 
                  AND n.COM_CODE_INSEE_SUIVANT = e.COM_CODE_INSEE_ARRIVEE 
                  AND ROWNUM = 1) AS DUREE,
               c_dep.COM_NOM AS VILLE_DEPART,
               c_arr.COM_NOM AS VILLE_ARRIVEE
        FROM VIK_ETAPE e
        JOIN VIK_COMMUNE c_dep ON e.COM_CODE_INSEE_DEPART = c_dep.COM_CODE_INSEE
        JOIN VIK_COMMUNE c_arr ON e.COM_CODE_INSEE_ARRIVEE = c_arr.COM_CODE_INSEE
        WHERE e.RES_NUM = :res_num
        ORDER BY e.ETA_HEURE ASC
    ";
    $stmt_etapes = $conn->prepare($sql_etapes);
    $stmt_etapes->execute(['res_num' => $res_num]);
    $etapes = $stmt_etapes->fetchAll(PDO::FETCH_ASSOC);

    // Construction de la feuille de route
    $feuille_route = [];
    $feuille_route[] = [
        'ordre' => 1,
        'ligne' => !empty($etapes) ? $etapes[0]['LIG_NUM'] : '--',
        'heure' => $res['RES_HEURE'],
        'commune' => $res['VILLE_DEPART']
    ];

    $heure_ts = strtotime($res['RES_HEURE']);
    foreach ($etapes as $etape) {
        $duree = (int)$etape['DUREE'];
        $heure_ts += $duree * 60;
        $heure_arrivee = date('H:i', $heure_ts);
        $feuille_route[] = [
            'ordre' => count($feuille_route) + 1,
            'ligne' => $etape['LIG_NUM'],
            'heure' => $heure_arrivee,
            'commune' => $etape['VILLE_ARRIVEE']
        ];
    }

} catch (Exception $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

class BilletPDF extends FPDF
{
    function Header()
    {
        $this->SetFillColor(198, 40, 40);
        $this->Rect(0, 0, 210, 40, 'F');
        $this->SetFont('Arial', 'B', 22);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 20, utf8_decode("VIKING TRANSPORT - BILLET DE VOYAGE"), 0, 1, 'C');
    }

    function Footer()
    {
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(112, 103, 103);
        $this->Cell(0, 5, utf8_decode("© 2026 Viking Transport"), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode("Ce document sert de titre de transport officiel à présenter au conducteur."), 0, 0, 'C');
    }
}

$pdf = new BilletPDF();
$pdf->AddPage();
$pdf->SetMargins(20, 20, 20);
$pdf->Ln(25);

$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(112, 103, 103);
$pdf->Cell(0, 10, utf8_decode("Référence de Réservation : #") . $res['RES_NUM'], 0, 1, 'L');
$pdf->SetDrawColor(198, 40, 40);
$pdf->SetLineWidth(0.8);
$pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
$pdf->Ln(7);

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 8, utf8_decode("INFORMATIONS PASSAGER"), 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(40, 7, utf8_decode("Nom :"), 0, 0);
$pdf->Cell(0, 7, strtoupper(utf8_decode($res['CLI_NOM'])), 0, 1);
$pdf->Cell(40, 7, utf8_decode("Prénom :"), 0, 0);
$pdf->Cell(0, 7, utf8_decode($res['CLI_PRENOM']), 0, 1);
$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode("DÉTAILS DU TRAJET"), 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(40, 7, utf8_decode("Date de voyage :"), 0, 0);
$date_objet = DateTime::createFromFormat('d/m/y', $res['RES_DATE']);
$date_voyage = $date_objet ? $date_objet->format('d/m/Y') : $res['RES_DATE'];
$pdf->Cell(0, 7, $date_voyage, 0, 1);
$pdf->Cell(40, 7, utf8_decode("Heure de départ :"), 0, 0);
$pdf->Cell(0, 7, $res['RES_HEURE'], 0, 1);
$pdf->Cell(40, 7, utf8_decode("Arrêt de Départ :"), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 7, utf8_decode($res['VILLE_DEPART']), 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(40, 7, utf8_decode("Arrêt d'Arrivée :"), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 7, utf8_decode($res['VILLE_ARRIVEE']), 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(40, 7, utf8_decode("Heure d'arrivée :"), 0, 0);
$dernier_heure = end($feuille_route)['heure'] ?? '--:--';
$pdf->Cell(0, 7, $dernier_heure, 0, 1);
$pdf->Ln(10);

if (count($feuille_route) > 0) {
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, utf8_decode("FEUILLE DE ROUTE - CHRONOLOGIE DES ARRETS"), 0, 1);
    $pdf->SetLineWidth(0.2);
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(20, 6, utf8_decode(" Arrêt n°"), 1, 0, 'C', true);
    $pdf->Cell(20, 6, utf8_decode(" Ligne"), 1, 0, 'C', true);
    $pdf->Cell(30, 6, utf8_decode(" Horaire"), 1, 0, 'L', true);
    $pdf->Cell(100, 6, utf8_decode(" Commune / Nom de l'arrêt"), 1, 1, 'L', true);
    $pdf->SetFont('Arial', '', 10);
    foreach ($feuille_route as $etape) {
        $pdf->Cell(20, 6, $etape['ordre'], 1, 0, 'C');
        $pdf->Cell(20, 6, ' ' . $etape['ligne'], 1, 0, 'C');
        $pdf->Cell(30, 6, ' ' . $etape['heure'], 1, 0, 'L');
        $pdf->Cell(100, 6, ' ' . utf8_decode($etape['commune']), 1, 1, 'L');
    }
    $pdf->Ln(10);
    $pdf->SetLineWidth(0.8);
    $pdf->SetDrawColor(198, 40, 40);
}

// ==========================================
// BLOC GRIS (PRIX ET POINTS)
// ==========================================
// On vérifie si le client a un compte (CLI_NUM n'est ni vide, ni à 0)
$has_account = (!empty($res['CLI_NUM']) && $res['CLI_NUM'] != 0);

// On adapte la hauteur de la boîte grise (25mm si compte, 15mm sinon)
$hauteur_boite = $has_account ? 25 : 15;

$current_y = $pdf->GetY();
$pdf->SetFillColor(229, 232, 232);
$pdf->Rect(20, $current_y, 170, $hauteur_boite, 'F');

// On descend un peu pour centrer verticalement le texte
$pdf->SetY($current_y + ($has_account ? 4 : 4.5));

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(198, 40, 40);

// Ligne 1 : Points de fidélité (seulement si connecté)
if ($has_account) {
    $pdf->Cell(10, 6, "", 0, 0);
    $pdf->Cell(80, 6, utf8_decode("Points de fidélité cumulés :"), 0, 0);
    $pdf->Cell(0, 6, $res['RES_NB_POINTS'] . " pts", 0, 1, 'R');
}

// Ligne 2 : Prix (toujours présente)
$pdf->Cell(10, 6, "", 0, 0);
$pdf->Cell(80, 6, utf8_decode("MONTANT TOTAL PAYÉ TTC :"), 0, 0);

$prix_db = $res['RES_PRIX_TOT'];
if (is_string($prix_db)) $prix_db = str_replace(',', '.', $prix_db);
$prix_float = (float) $prix_db;
if ($prix_float > 1000) $prix_float = $prix_float / 100;
$pdf->Cell(0, 6, number_format($prix_float, 2, ',', ' ') . " EUR", 0, 1, 'R');

// On replace le curseur tout en bas de la boîte grise pour que la suite s'affiche bien
$pdf->SetY($current_y + $hauteur_boite);

// ==========================================
// CONSTRUCTION ET AFFICHAGE DU QR CODE
// ==========================================
$pdf->Ln(10);

// Sécurité saut de page (le QR code prend environ 35mm de haut avec le texte)
if ($pdf->GetY() > 240) {
    $pdf->AddPage();
    $pdf->Ln(10);
}

$yCodeBarre = $pdf->GetY();
$chaine_code_barre = $res['RES_NUM'] . '-' . trim($res['COM_CODE_INSEE_DEPART']) . '-' . trim($res['COM_CODE_INSEE_ARRIVEE']);

// 1. Appel d'une API pour générer le QR Code (Format PNG)
// On encode la chaîne avec urlencode() pour éviter les problèmes d'URL
$url_qr = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&margin=0&data=' . urlencode($chaine_code_barre);

// 2. On place l'image centrée
// Calcul du centrage : (Largeur A4 210 - Largeur Image 30) / 2 = 90
// $pdf->Image(URL_Image, X, Y, Largeur, Hauteur, Type)
$pdf->Image($url_qr, 90, $yCodeBarre, 30, 30, 'PNG');

// 3. On affiche le texte juste en dessous du carré de l'image
$pdf->SetY($yCodeBarre + 32);
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, $chaine_code_barre, 0, 1, 'C');

$pdf->Output('I', 'Billet_Viking_' . $res['RES_NUM'] . '.pdf');
?>