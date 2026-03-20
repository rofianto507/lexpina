<?php
session_start();
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
require_once("../config/configuration.php");
$q = $pdo->query("SELECT id, nama FROM lalin_kategoris WHERE status=1 ORDER BY nama");
$data = [];
while($r = $q->fetch()) {
  $data[] = [ 'id' => $r['id'], 'nama' => $r['nama'] ];
}
header('Content-Type: application/json');
echo json_encode($data);