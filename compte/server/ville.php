<?php
session_start();

include_once "../../PHP/param_connexion.php";
include_once "../../PHP/pdo_agile.php";

$db = $dbOracle;
$db_userName = $db_usernameOracle;
$db_pwd = $db_passwordOracle;

if (isset($_POST["ville"]))
    $nouveauville = $_POST["ville"];

$conn = OuvrirConnexionPDO($db, $db_userName, $db_pwd);

$queryAncienville = $conn->prepare("SELECT cli_ville FROM VIK_CLIENT WHERE cli_num = :num");
$queryAncienville->execute([
    "num" => $_SESSION["id"]
]);
$ancienville = $queryAncienville->fetchColumn();

if ($ancienville === $nouveauville) {
    $_SESSION["error"] = "noChange";
    header("Location: /compte/compte.php");
    die();
}

$update = $conn->prepare("UPDATE vik_client 
SET cli_ville = :ville
WHERE cli_num = :num");
$update->execute([
    "ville" => $nouveauville,
    "num" => $_SESSION["id"]
]);

header("Location: /compte/compte.php");
die();