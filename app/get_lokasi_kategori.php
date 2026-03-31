<?php
session_start();
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
require_once("../config/configuration.php");
header("Content-Type: application/json");

try {
    $stmt = $pdo->query("SELECT id, nama FROM lokasi_kategoris WHERE status=1 ORDER BY nama ASC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        "success" => true,
        "data" => $data
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Gagal memuat data lokasi"
    ]);
}