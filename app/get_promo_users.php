<?php
session_start();

include("../config/configuration.php");
if(!isset($_SESSION["id"]) || !isset($_SESSION["nama"])) {
  header('HTTP/1.1 401 Unauthorized');
  header('Content-Type: application/json');
  echo json_encode([]);
  exit;
}

$kode = isset($_GET['kode']) ? strtoupper(trim($_GET['kode'])) : '';
if ($kode === '') {
  header('Content-Type: application/json');
  echo json_encode([]);
  exit;
}

try {
  $stmt = $pdo->prepare("SELECT t.id, t.user_id, t.total_transfer, t.diskon_nominal, t.status, t.created_at, u.nama, u.username FROM transaksis t LEFT JOIN users u ON t.user_id = u.id WHERE t.kode_promo = ? ORDER BY t.created_at DESC");
  $stmt->execute([$kode]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  header('Content-Type: application/json');
  echo json_encode($rows);
} catch (PDOException $e) {
  header('Content-Type: application/json');
  echo json_encode([]);
}
