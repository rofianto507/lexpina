<?php
session_start();
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
require_once("../config/configuration.php");
header("Content-Type: application/json");
$mode = $_GET['mode'] ?? 'provinsi'; // provinsi, kabupaten, kecamatan, desa
$parent_id = intval($_GET['parent_id'] ?? 0);

$data = [];
if ($mode == 'provinsi') {
    // Total konflik per kategori seluruh provinsi
    $q = $pdo->query("
        SELECT kk.nama, COUNT(k.id) as total
        FROM konflik_kategoris kk
        LEFT JOIN konfliks k ON k.kategori_id = kk.id
        GROUP BY kk.id
        ORDER BY kk.nama
    ");
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $data[] = ['label' => $row['nama'], 'total' => $row['total']];
    }
} else if ($mode == 'kabupaten' && $parent_id) {
    // Total konflik per kategori DI kabupaten tertentu
    $q = $pdo->prepare("
        SELECT kk.nama, COUNT(k.id) as total
        FROM konflik_kategoris kk
        LEFT JOIN konfliks k ON k.kategori_id = kk.id
        LEFT JOIN desas d ON k.desa_id = d.id
        LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
        WHERE kc.kabupaten_id = ?
        GROUP BY kk.id
        ORDER BY kk.nama
    ");
    $q->execute([$parent_id]);
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $data[] = ['label' => $row['nama'], 'total' => $row['total']];
    }
} else if ($mode == 'kecamatan' && $parent_id) {
    // Total konflik per kategori di kecamatan
    $q = $pdo->prepare("
        SELECT kk.nama, COUNT(k.id) as total
        FROM konflik_kategoris kk
        LEFT JOIN konfliks k ON k.kategori_id = kk.id
        LEFT JOIN desas d ON k.desa_id = d.id
        WHERE d.kecamatan_id = ?
        GROUP BY kk.id
        ORDER BY kk.nama
    ");
    $q->execute([$parent_id]);
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $data[] = ['label' => $row['nama'], 'total' => $row['total']];
    }
} else if ($mode == 'desa' && $parent_id) {
    // Total konflik per kategori di desa
    $q = $pdo->prepare("
        SELECT kk.nama, COUNT(k.id) as total
        FROM konflik_kategoris kk
        LEFT JOIN konfliks k ON k.kategori_id = kk.id
        WHERE k.desa_id = ?
        GROUP BY kk.id
        ORDER BY kk.nama
    ");
    $q->execute([$parent_id]);
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $data[] = ['label' => $row['nama'], 'total' => $row['total']];
    }
}
echo json_encode($data);