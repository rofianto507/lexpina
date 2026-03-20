<?php
session_start();
$akses = $_SESSION["akses"] ?? "";
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
require_once("../config/configuration.php");
$level = isset($_GET['level']) ? $_GET['level'] : 'provinsi';
$wilayah_id = isset($_GET['wilayah_id']) ? intval($_GET['wilayah_id']) : 0;
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : 0;
$whereWilayah = '';
if($level == 'kabupaten' && $wilayah_id) {
    $whereWilayah = "AND d.kecamatan_id IN (
        SELECT id FROM kecamatans WHERE kabupaten_id = $wilayah_id
    )";
} elseif($level == 'kecamatan' && $wilayah_id) {
    $whereWilayah = "AND k.desa_id IN (
        SELECT id FROM desas WHERE kecamatan_id = $wilayah_id
    )";
} elseif($level == 'desa' && $wilayah_id) {
    $whereWilayah = "AND k.desa_id = $wilayah_id";
}
 
$whereTahun = '';
if ($tahun) {
    $whereTahun = "AND s.tahun = $tahun";
}

$whereBulan = '';
if ($bulan) {
    $whereBulan = "AND MONTH(k.tanggal) = $bulan AND YEAR(k.tanggal) = $tahun";
}

$kategoriFilter = [];
if(!empty($_GET['kategori'])) {
    if(is_array($_GET['kategori'])) $kategoriFilter = array_map('intval', $_GET['kategori']);
    else $kategoriFilter = array_map('intval', explode(',', $_GET['kategori']));
    $kategoriIn = implode(',', array_map('intval', $kategoriFilter));
}

$whereKategori = "";
if(!empty($kategoriIn)) $whereKategori = "AND k.sub_kategori_id IN (
    SELECT id FROM kriminal_sub_kategoris WHERE kategori_id IN ($kategoriIn)
)";
$subKategoriFilter = [];
if (!empty($_GET['sub_kategori'])) {
    if (is_array($_GET['sub_kategori'])) $subKategoriFilter = array_map('intval', $_GET['sub_kategori']);
    else $subKategoriFilter = array_map('intval', explode(',', $_GET['sub_kategori']));
}
$whereSubKategori = '';
if (count($subKategoriFilter) > 0) {
    $subKSK = implode(',', array_map('intval', $subKategoriFilter));
    $whereSubKategori = " AND k.sub_kategori_id IN ($subKSK) ";
    $whereKategori = ''; // Override filter kategori jika filter sub kategori dipilih, karena sub kategori sudah pasti punya kategori
}

if($akses != 'POLDA') {
    $whereKategori .= " AND k.tujuan='$akses'";
}
// Query total & selesai
$sql_total = "SELECT SUM(k.poin) as total
                FROM kriminals k
                LEFT JOIN sumbers s ON k.sumber_id = s.id
                LEFT JOIN desas d ON k.desa_id = d.id
                WHERE k.status=1 $whereKategori $whereSubKategori $whereTahun $whereBulan $whereWilayah";
$sql_selesai = "SELECT SUM(k.poin) as total
                  FROM kriminals k
                  LEFT JOIN sumbers s ON k.sumber_id = s.id
                  LEFT JOIN desas d ON k.desa_id = d.id
                  WHERE k.status=1 AND k.state='SELESAI' $whereKategori $whereSubKategori $whereTahun $whereBulan $whereWilayah";
$q_total = $pdo->query($sql_total);
$total = $q_total->fetchColumn();
$q_selesai = $pdo->query($sql_selesai);
$selesai = $q_selesai->fetchColumn();

echo json_encode([
    "crime_total" => (int)($total?:0),
    "crime_clearance" => (int)($selesai?:0),
    "crime_rate" => ($total > 0 ? round(($selesai / $total) * 100,1) : 0)
]);