<?php
// ============================================================
// acessoBD.php — Ligação à base de dados
// ============================================================

$host = 'localhost';
$dbname = 'ipca-pw2';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("<div style='font-family:Arial;color:red;padding:20px;'>
         <strong>Erro na ligação à base de dados:</strong><br>"
        . htmlspecialchars($e->getMessage()) . "</div>");
}
