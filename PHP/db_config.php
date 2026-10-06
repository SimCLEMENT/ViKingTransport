<?php
$host = getenv('DB_HOST') ?: 'db';
$user = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME') ?: 'vikingtransport';
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

// alias pour les fichiers qui utilisent d'autres noms de variables
$db = $dsn;
$db_username = $user;
$db_password = $password;
