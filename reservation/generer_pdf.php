<?php
session_start();
// Sécurité de base : s'assurer qu'un numéro de réservation valide est demandé
$res_num = $_GET['res_num'] ?? '';
if (empty($res_num)) {
    die("Numéro de réservation manquant.");
}

// Inclure la bibliothèque FPDF (Assurez-vous d'avoir le fichier fpdf.php dans votre dossier)
require('../PHP/fpdf.php');

// Configuration de la connexion Oracle Harpagon
$host = "harpagon.unicaen.fr";
$port = "1521";
$sid = "info";
$user = "agile_5";
$password = "lemeilleurgroupe";

$dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$sid)))";

try {
    $conn = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Requête complète pour récupérer la réservation avec les NOMS des communes de départ et d'arrivée
    $sql = "
        SELECT 
            r.RES_NUM, r.RES_DATE, r.RES_PRIX_TOT, r.RES_NB_POINTS, r.RES_HEURE,
            r.CLI_NOM, r.CLI_PRENOM,
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
    
    if (!$res) {
        die("Réservation introuvable.");
    }
    
} catch (Exception $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// --- CRÉATION DU DOCUMENT PDF ---
class BilletPDF extends FPDF {
    // En-tête de la facture / billet
    function Header() {
        // Couleurs de la charte Viking Transport : Rouge (#C62828)
        $this->SetFillColor(198, 40, 40);
        $this->Rect(0, 0, 210, 40, 'F');
        
        // Texte du bandeau
        $this->SetFont('Arial', 'B', 22);
        $this->SetTextColor(255, 255, 255);
        // Utilisation de utf8_decode pour éviter les problèmes d'accents avec FPDF
        $this->Cell(0, 20, utf8_decode("VIKING TRANSPORT - BILLET DE VOYAGE"), 0, 1, 'C');
    }

    // Pied de page
    function Footer() {
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(112, 103, 103); // Gris charte (--viking-dark-grey)
        $this->Cell(0, 5, utf8_decode("© 2026 Viking Transport — Agence Asgard Tech"), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode("Ce document sert de titre de transport officiel à présenter au conducteur."), 0, 0, 'C');
    }
}

// Instanciation de la classe FPDF
$pdf = new BilletPDF();
$pdf->AddPage();
$pdf->SetMargins(20, 20, 20);
$pdf->Ln(25); // Espace après le bandeau de l'en-tête

// Bloc Référence du Billet
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(112, 103, 103);
$pdf->Cell(0, 10, generer_pdf . phputf8_decode("Référence de Réservation : #") . $res['RES_NUM'], 0, 1, 'L');

// Ligne de séparation rouge fine
$pdf->SetDrawColor(198, 40, 40);
$pdf->SetLineWidth(0.8);
$pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
$pdf->Ln(7);

// Informations Passager
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 8, utf8_decode("INFORMATIONS PASSAGER"), 0, 1);

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(40, 7, utf8_decode("Nom :"), 0, 0);
$pdf->Cell(0, 7, strtoupper(utf8_decode($res['CLI_NOM'])), 0, 1);
$pdf->Cell(40, 7, utf8_decode("Prénom :"), 0, 0);
$pdf->Cell(0, 7, utf8_decode($res['CLI_PRENOM']), 0, 1);
$pdf->Ln(5);

// Informations Trajet
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode("DÉTAILS DU TRAJET"), 0, 1);

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(40, 7, utf8_decode("Date de voyage :"), 0, 0);

// On explique à PHP le format exact renvoyé par Oracle (Jour/Mois/Année sur 2 chiffres)
$date_objet = DateTime::createFromFormat('d/m/y', $res['RES_DATE']);

if ($date_objet) {
    // On la réaffiche proprement avec l'année sur 4 chiffres
    $date_voyage = $date_objet->format('d/m/Y');
} else {
    // Sécurité au cas où le format changerait
    $date_voyage = $res['RES_DATE']; 
}

$pdf->Cell(0, 7, $date_voyage, 0, 1);

$pdf->Cell(40, 7, utf8_decode("Heure de départ :"), 0, 0);
$pdf->Cell(0, 7, $res['RES_HEURE'], 0, 1);

$pdf->Cell(40, 7, utf8_decode("Gare de Départ :"), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 7, utf8_decode($res['VILLE_DEPART']), 0, 1);

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(40, 7, utf8_decode("Gare d'Arrivée :"), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 7, utf8_decode($res['VILLE_ARRIVEE']), 0, 1);
$pdf->Ln(10);

// Encadré Récapitulatif Prix & Points (Style Facture)
$pdf->SetFillColor(229, 232, 232); // Fond gris clair de votre charte (--viking-light-grey)
$pdf->Rect(20, $pdf->GetY(), 170, 25, 'F');

$pdf->SetY($pdf->GetY() + 4);
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(198, 40, 40); // Texte rouge viking
$pdf->Cell(10, 6, "", 0, 0);
$pdf->Cell(80, 6, utf8_decode("Points de fidélité cumulés :"), 0, 0);
$pdf->Cell(0, 6, $res['RES_NB_POINTS'] . " pts", 0, 1, 'R');

$pdf->Cell(10, 6, "", 0, 0);
$pdf->Cell(80, 6, utf8_decode("MONTANT TOTAL PAYÉ TTC :"), 0, 0);
$pdf->Cell(0, 6, number_format($res['RES_PRIX_TOT'], 2, ',', ' ') . " EUR", 0, 1, 'R');

// Envoi du flux PDF au navigateur pour affichage/téléchargement immédiat
$pdf->Output('I', 'Billet_Viking_' . $res['RES_NUM'] . '.pdf');
?>