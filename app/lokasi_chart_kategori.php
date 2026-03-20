<?php
session_start();
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
require_once("../config/configuration.php");
header("Content-Type: application/json");
$data = [];
$q = $pdo->query("
    SELECT lk.id, lk.nama,lk.warna,
        COUNT(l.id) as total
    FROM lokasi_kategoris lk
    LEFT JOIN lokasis l ON l.kategori_id = lk.id AND l.status=1
    where l.status=1
    GROUP BY lk.id
    ORDER BY total desc, lk.nama
");
while($row = $q->fetch(PDO::FETCH_ASSOC)) {
    $data[] = [
        'id'    => $row['id'],
        'label' => $row['nama'],
        'total' => $row['total'],
        'color' => $row['warna']
    ];
}
echo json_encode($data);