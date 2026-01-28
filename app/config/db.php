<?php
declare(strict_types=1);

$host = "db";
$db   = "tienda_db";
$user = "root";
$pass = "root";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;port=3306;dbname=$db;charset=$charset";


$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
  // En producción no se muestra el error. Aquí lo dejamos simple para desarrollo.
  die("DB ERROR: " . $e->getMessage());
}
