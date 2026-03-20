<?php
require_once("../config/configuration.php");
header('Content-Type: application/json');
$kabupaten_id = isset($_GET['kabupaten_id']) ? intval($_GET['kabupaten_id']) : 0;
$polsek_id = isset($_GET['polsek_id']) ? intval($_GET['polsek_id']) : 0;

$sql = "SELECT id, nama, kode, polsek_id FROM kecamatans WHERE status=1 AND kabupaten_id=?";
$params = [$kabupaten_id];

if ($polsek_id > 0) {
    $sql .= " AND polsek_id=?";
    $params[] = $polsek_id;
}

$sql .= " ORDER BY nama";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));