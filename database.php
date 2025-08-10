<?php
$host = '127.0.0.1';        // ou 'localhost'
$db   = 'task_crud';        // nom de ta base
$user = 'root';             // ton utilisateur MySQL
$pass = '';                 // mot de passe MySQL (vide par défaut sur Laragon)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit('Erreur de connexion : '.$e->getMessage());
}
