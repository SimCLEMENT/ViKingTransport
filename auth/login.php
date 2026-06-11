<?php

session_start();

include_once("../PHP/param_connexion.php");
include_once("../PHP/pdo_agile.php");

$db = $dbOracle;
$db_username = $db_usernameOracle;
$db_passwd = $db_passwordOracle;

function redirect($err, $url) {
    $_SESSION["error"] = $err;
    header("Location: $url");
    die();
}

if (!isset($_POST['email']) && !isset($_POST['password'])) {
    redirect("notSet", "/PHP/connexion.php"); // Il manque MDP et email
} elseif (!isset($_POST['email']) && isset($_POST['password'])) {
    redirect("emailNotSet", "/PHP/connexion.php"); // Il manque l'email
} elseif (isset($_POST['email']) && !isset($_POST['password'])) {
    redirect("pwdNotSet", "/PHP/connexion.php"); // Il manque le mdp
}

$conn = OuvrirConnexionPDO($db, $db_username, $db_passwd);

$usersQuery = $conn->prepare("SELECT * FROM VIK_CLIENT WHERE CLI_COURRIEL = :email");
$usersQuery->execute([
    "email" => $_POST['email']
]);
$users = $usersQuery->fetchAll();

foreach ($users as $user) {
    if (
        $_POST["email"] === $user["CLI_COURRIEL"] &&
        password_verify($_POST["password"], $user["CLI_MDP"])
    ) {
        $_SESSION["type"] = $user["TYP_NUM"];
        $_SESSION["nom"] = $user["CLI_NOM"];
        $_SESSION["prenom"] = $user["CLI_PRENOM"];
        $_SESSION["id"] = $user["CLI_NUM"];
        header("Location:/");
        die();
    }
    $_SESSION["error"] = "badAuth";
    header("Location:/auth/connexion.php");
    die();
}

if (isset($_SESSION["id"])) {
    header("Location:/");
    die();
}