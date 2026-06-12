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
    $conn = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
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

class BilletPDF extends FPDF {
    function Header() {
        $this->SetFillColor(198, 40, 40);
        $this->Rect(0, 0, 210, 40, 'F');
        $this->SetFont('Arial', 'B', 22);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 20, utf8_decode("VIKING TRANSPORT - BILLET DE VOYAGE"), 0, 1, 'C');
    }

    function Footer() {
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(112, 103, 103);
        $this->Cell(0, 5, utf8_decode("© 2026 Viking Transport — Agence Asgard Tech"), 0, 1, 'C');
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
$pdf->Cell(40, 7, utf8_decode("Gare de Départ :"), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 7, utf8_decode($res['VILLE_DEPART']), 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(40, 7, utf8_decode("Gare d'Arrivée :"), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 7, utf8_decode($res['VILLE_ARRIVEE']), 0, 1);
$pdf->Ln(10);

$pdf->SetFillColor(229, 232, 232);
$pdf->Rect(20, $pdf->GetY(), 170, 25, 'F');
$pdf->SetY($pdf->GetY() + 4);
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(198, 40, 40);
$pdf->Cell(10, 6, "", 0, 0);
$pdf->Cell(80, 6, utf8_decode("Points de fidélité cumulés :"), 0, 0);
$pdf->Cell(0, 6, $res['RES_NB_POINTS'] . " pts", 0, 1, 'R');
$pdf->Cell(10, 6, "", 0, 0);
$pdf->Cell(80, 6, utf8_decode("MONTANT TOTAL PAYÉ TTC :"), 0, 0);
// 🔽 Correction : division par 100 car le prix est stocké en centimes
$prix_euros = $res['RES_PRIX_TOT'] / 100;
$pdf->Cell(0, 6, number_format($prix_euros, 2, ',', ' ') . " EUR", 0, 1, 'R');

$pdf->Output('I', 'Billet_Viking_' . $res['RES_NUM'] . '.pdf');
?>