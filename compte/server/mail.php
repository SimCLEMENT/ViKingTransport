<?php
session_start();

include_once "../../PHP/param_connexion.php";
include_once "../../PHP/pdo_agile.php";

$db = $dbOracle;
$db_userName = $db_usernameOracle;
$db_pwd = $db_passwordOracle;

if (isset($_POST["mail"]))
    $nouveauMail = $_POST["mail"];

$conn = OuvrirConnexionPDO($db, $db_userName, $db_pwd);

$queryAncienMail = $conn->prepare("SELECT cli_courriel FROM VIK_CLIENT WHERE cli_num = :num");
$queryAncienMail->execute([
    "num" => $_SESSION["id"]
]);
$ancienMail = $queryAncienMail->fetchColumn();

if ($ancienMail === $nouveauMail) {
    $_SESSION["error"] = "noChange";
    header("Location: /compte/compte.php");
    die();
}

$queryEmails = $conn->prepare("SELECT cli_courriel FROM vik_client");
$queryEmails->execute();

foreach ($queryEmails->fetchAll(PDO::FETCH_COLUMN) as $email) {
    if ($_POST["mail"] == $email) {
        $_SESSION["error"] = "alreadyExists";
        header("Location: /compte/edit.php");
        die();
    }
}

$update = $conn->prepare("UPDATE vik_client 
SET cli_courriel = :mail
WHERE cli_num = :num");
$update->execute([
    "mail" => $nouveauMail,
    "num" => $_SESSION["id"]
]);

header("Location: /compte/compte.php");
die();