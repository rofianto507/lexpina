<?php
// Salin file ini menjadi configuration.php dan sesuaikan isinya
// cp configuration.example.php configuration.php

error_reporting(E_ALL);

$app_name        = "Peta Digital";
$app_description = "Peta Digital Rawan Konflik Polda Sumsel";
$long_description= "Aplikasi web yang menampilkan informasi daerah rawan konflik dan kamtibmas di wilayah Polda Sumsel.";
$path            = "http://localhost/pdk";   // Sesuaikan dengan URL lokal Anda

$host    = 'localhost';
$db      = 'petadigi_db';     // Nama database
$user    = 'root';            // Username MySQL
$pass    = '';                // Password MySQL
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
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}
?>
