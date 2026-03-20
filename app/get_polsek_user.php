<?php
require_once("../config/configuration.php");
header('Content-Type: application/json');
$polres_id = isset($_GET['polres_id']) ? intval($_GET['polres_id']) : 0;
$stmt = $pdo->prepare("SELECT id, nama FROM polseks WHERE status=1 AND polres_id=? ORDER BY nama");
$stmt->execute([$polres_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));