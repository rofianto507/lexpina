<?php
session_start();
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
require_once("../config/configuration.php");
header("Content-Type: application/json");
$kategoriFilter = [];
if(!empty($_GET['kategori'])){
    if(is_array($_GET['kategori'])) $kategoriFilter = array_map('intval', $_GET['kategori']);
    else $kategoriFilter = array_map('intval', explode(',', $_GET['kategori']));
    $kategoriIn = implode(',', array_map('intval', $kategoriFilter));
}
$whereKategori = '';
if(!empty($kategoriIn)){
    $whereKategori = "AND l.kategori_id IN ($kategoriIn)";
}
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$whereTahun = "";
if ($tahun) $whereTahun = "AND s.tahun = $tahun";
$mode = $_GET['mode'] ?? 'kabupaten';
$parent_id = intval($_GET['parent_id'] ?? 0);

$data = [];

if ($mode == 'kabupaten') {
    // Pie Lalin kategori seluruh wilayah
    $q = $pdo->query("
        SELECT lk.nama, COUNT(l.id) as total
        FROM lalin_kategoris lk
        LEFT JOIN lalins l ON l.kategori_id = lk.id AND l.status=1 and l.state!='SELESAI'
        LEFT JOIN sumbers s ON l.sumber_id = s.id
        WHERE 1=1 $whereKategori $whereTahun
        GROUP BY lk.id
        ORDER BY lk.nama
    ");
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $data[] = ['label' => $row['nama'], 'total' => $row['total']];
    }
} else if ($mode == 'kecamatan' && $parent_id) {
    $q = $pdo->prepare("
        SELECT lk.nama, COUNT(l.id) as total
        FROM lalin_kategoris lk
        LEFT JOIN lalins l ON l.kategori_id = lk.id AND l.status=1 and l.state!='SELESAI'
        LEFT JOIN desas d ON l.desa_id = d.id
        LEFT JOIN kecamatans k ON d.kecamatan_id = k.id
        LEFT JOIN sumbers s ON l.sumber_id = s.id
        WHERE k.kabupaten_id = ? $whereKategori $whereTahun
        GROUP BY lk.id
        ORDER BY lk.nama
    ");
    $q->execute([$parent_id]);
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $data[] = ['label' => $row['nama'], 'total' => $row['total']];
    }
} else if ($mode == 'desa' && $parent_id) {
    $q = $pdo->prepare("
        SELECT lk.nama, COUNT(l.id) as total
        FROM lalin_kategoris lk
        LEFT JOIN lalins l ON l.kategori_id = lk.id AND l.status=1 and l.state!='SELESAI'
        LEFT JOIN desas d ON l.desa_id = d.id
        LEFT JOIN sumbers s ON l.sumber_id = s.id
        WHERE d.kecamatan_id = ? $whereKategori $whereTahun
        GROUP BY lk.id
        ORDER BY lk.nama
    ");
    $q->execute([$parent_id]);
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $data[] = ['label' => $row['nama'], 'total' => $row['total']];
    }
}
echo json_encode($data);