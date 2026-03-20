<?php
session_start();
$akses = $_SESSION["akses"] ?? "";
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
require_once("../config/configuration.php");
header("Content-Type: application/json");
$kategoriFilter = [];
if(!empty($_GET['kategori'])) {
    if(is_array($_GET['kategori'])) $kategoriFilter = array_map('intval', $_GET['kategori']);
    else $kategoriFilter = array_map('intval', explode(',', $_GET['kategori']));
    $kategoriIn = implode(',', array_map('intval', $kategoriFilter));
}
$whereKategori = "";
if(!empty($kategoriIn)){
    $whereKategori = "AND km.kategori_id IN ($kategoriIn)";
}
if($akses != 'POLDA') {
    $whereKategori .= " AND km.tujuan='$akses'";
}
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$whereTahun = "";
if($tahun) $whereTahun = "AND s.tahun = $tahun";
$mode = $_GET['mode'] ?? 'kabupaten';
$parent_id = intval($_GET['parent_id'] ?? 0);
$data = [];

if ($mode == 'kabupaten') {
    $q = $pdo->query("
        SELECT kk.nama, COUNT(km.id) as total
        FROM kamtibmas_kategoris kk
        LEFT JOIN kamtibmass km ON km.kategori_id = kk.id
        LEFT JOIN sumbers s ON km.sumber_id = s.id
        LEFT JOIN desas d ON km.desa_id = d.id
        LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
        LEFT JOIN kabupatens k ON kc.kabupaten_id = k.id
        WHERE km.status=1 AND km.state!='SELESAI' $whereKategori $whereTahun
        GROUP BY kk.id
        ORDER BY kk.nama
    ");
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $data[] = ['label' => $row['nama'], 'total' => $row['total']];
    }
} else if ($mode == 'kecamatan' && $parent_id) {
    $q = $pdo->prepare("
        SELECT kk.nama, COUNT(km.id) as total
        FROM kamtibmas_kategoris kk
        LEFT JOIN kamtibmass km ON km.kategori_id = kk.id
        LEFT JOIN sumbers s ON km.sumber_id = s.id
        LEFT JOIN desas d ON km.desa_id = d.id
        LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
        WHERE kc.kabupaten_id = ? AND km.status=1 AND km.state!='SELESAI' $whereKategori $whereTahun
        GROUP BY kk.id
        ORDER BY kk.nama
    ");
    $q->execute([$parent_id]);

    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $data[] = ['label' => $row['nama'], 'total' => $row['total']];
    }
} else if ($mode == 'desa' && $parent_id) {
    $q = $pdo->prepare("
        SELECT kk.nama, COUNT(km.id) as total
        FROM kamtibmas_kategoris kk
        LEFT JOIN kamtibmass km ON km.kategori_id = kk.id
        LEFT JOIN sumbers s ON km.sumber_id = s.id
        LEFT JOIN desas d ON km.desa_id = d.id
        WHERE d.kecamatan_id = ? AND km.status=1 AND km.state!='SELESAI' $whereKategori $whereTahun
        GROUP BY kk.id
        ORDER BY kk.nama
    ");
    $q->execute([$parent_id]);
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $data[] = ['label' => $row['nama'], 'total' => $row['total']];
    }
}
echo json_encode($data);