<?php
session_start();

include_once "../../PHP/param_connexion.php";
include_once "../../PHP/pdo_agile.php";

$db = $dbOracle;
$db_userName = $db_usernameOracle;
$db_pwd = $db_passwordOracle;

if (isset($_POST["tel"]))
    $nouveautelephone = $_POST["tel"];

$conn = OuvrirConnexionPDO($db, $db_userName, $db_pwd);

$queryAncientelephone = $conn->prepare("SELECT cli_telephone FROM VIK_CLIENT WHERE cli_num = :num");
$queryAncientelephone->execute([
    "num" => $_SESSION["id"]
]);
$ancientelephone = $queryAncientelephone->fetchColumn();

if ($ancientelephone === $nouveautelephone) {
    $_SESSION["error"] = "noChange";
    header("Location: /compte/compte.php");
    die();
}

$update = $conn->prepare("UPDATE vik_client 
SET cli_telephone = :telephone
WHERE cli_num = :num");
$update->execute([
    "telephone" => $nouveautelephone,
    "num" => $_SESSION["id"]
]);

header("Location: /compte/compte.php");
die();