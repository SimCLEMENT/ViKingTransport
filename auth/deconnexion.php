<?php
session_start();

// Suppression des variables de session
unset($_SESSION['type']);
unset($_SESSION['nom']);
unset($_SESSION['prenom']);
unset($_SESSION['id']);

// Facultatif : destruction complète de la session
session_destroy();

// Redirection
header('Location: /');
exit();
?>