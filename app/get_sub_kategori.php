<?php
session_start();
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
require_once("../config/configuration.php");
header('Content-Type: application/json');

$kategori_ids = [];
if (!empty($_GET['kategori_id'])) {
    // Handle kategori_id bisa array atau string koma
    if (is_array($_GET['kategori_id'])) {
        $kategori_ids = array_map('intval', $_GET['kategori_id']);
    } else {
        // Split string: '2,5,7'
        $kategori_ids = array_map('intval', explode(',', $_GET['kategori_id']));
    }
}

if(count($kategori_ids) > 0) {
    // Build placeholder (? , ? , ?)
    $placeholders = implode(',', array_fill(0, count($kategori_ids), '?'));
    $sql = "SELECT 
        ROW_NUMBER() OVER (ORDER BY nama) AS nomor_urut,
        id, 
        nama 
      FROM kriminal_sub_kategoris 
      WHERE status = 1 
        AND kategori_id IN ($placeholders)
      ORDER BY nama";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($kategori_ids);
} else {
    $stmt = $pdo->prepare("SELECT 
        ROW_NUMBER() OVER (ORDER BY nama) AS nomor_urut,
        id, 
        nama 
      FROM kriminal_sub_kategoris 
      WHERE status = 1 
      ORDER BY nama");
    $stmt->execute();
}

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));