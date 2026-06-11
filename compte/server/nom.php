<?php
session_start();

include_once "../../PHP/param_connexion.php";
include_once "../../PHP/pdo_agile.php";

$db = $dbOracle;
$db_userName = $db_usernameOracle;
$db_pwd = $db_passwordOracle;

if (isset($_POST["nom"]))
    $nouveauNom = $_POST["nom"];

if (isset($_POST["prenom"]))
    $nouveauPrenom = $_POST["prenom"];

$conn = OuvrirConnexionPDO($db, $db_userName, $db_pwd);

$queryAncienNom = $conn->prepare("SELECT cli_nom FROM VIK_CLIENT WHERE cli_num = :num");
$queryAncienNom->execute([
    "num" => $_SESSION["id"]
]);
$ancienNom = $queryAncienNom->fetchColumn();

$queryAncienPrenom = $conn->prepare("SELECT cli_Prenom FROM VIK_CLIENT WHERE cli_num = :num");
$queryAncienPrenom->execute([
    "num" => $_SESSION["id"]
]);
$ancienPrenom = $queryAncienPrenom->fetchColumn();

if ($ancienNom === $nouveauNom && $ancienPrenom === $nouveauPrenom) {
    $_SESSION["error"] = "noChange";
    header("Location: /compte/compte.php");
    die();
}

$update = $conn->prepare("UPDATE vik_client 
SET cli_nom = :nom, cli_prenom = :prenom 
WHERE cli_num = :num");
$update->execute([
    "nom" => $nouveauNom,
    "prenom" => $nouveauPrenom,
    "num" => $_SESSION["id"]
]);

$_SESSION["nom"] = $nouveauNom;
$_SESSION["prenom"] = $nouveauPrenom;

header("Location: /compte/compte.php");
die();