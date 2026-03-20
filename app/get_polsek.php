<?php
require_once("../config/configuration.php");
header('Content-Type: application/json');
$polres_id = isset($_GET['polres_id']) ? intval($_GET['polres_id']) : 0;
$polsek_id = isset($_GET['polsek_id']) ? intval($_GET['polsek_id']) : 0;

$sql = "SELECT id, nama FROM polseks WHERE status=1 AND polres_id=?";
$params = [$polres_id];

if ($polsek_id > 0) {
    $sql .= " AND id=?";
    $params[] = $polsek_id;
}

$sql .= " ORDER BY nama";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));