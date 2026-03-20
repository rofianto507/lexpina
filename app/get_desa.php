<?php
require_once("../config/configuration.php");
header('Content-Type: application/json');
$kecamatan_id = isset($_GET['kecamatan_id']) ? intval($_GET['kecamatan_id']) : 0;
$stmt = $pdo->prepare("SELECT id, nama,kode FROM desas WHERE status=1 AND kecamatan_id=? ORDER BY nama");
$stmt->execute([$kecamatan_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));