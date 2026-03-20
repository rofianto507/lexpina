<?php
session_start();
require_once("../config/configuration.php");
header('Content-Type: application/json');
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$polres_id = $_SESSION["polres_id"] ?? 0;
$sql = "SELECT id, nama FROM polress WHERE status=1";
$params = [];

if ($polres_id > 0) {
    $sql .= " AND id=?";
    $params[] = $polres_id;
}

$sql .= " ORDER BY nama";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
