<?php
session_start();
$akses = $_SESSION["akses"] ?? "";
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401); echo json_encode(["error" => "Unauthorized"]); exit;
}
require_once("../config/configuration.php");
header("Content-Type: application/json");

// PARAM FILTER
$level = $_GET['level'] ?? 'provinsi';
$parent_id = intval($_GET['parent_id'] ?? 0);
$kategoriFilter = [];
if (!empty($_GET['kategori'])) {
    if (is_array($_GET['kategori'])) $kategoriFilter = array_map('intval', $_GET['kategori']);
    else $kategoriFilter = array_map('intval', explode(',', $_GET['kategori']));
}
$whereKategori = "";
if(count($kategoriFilter) > 0) {
    $kategoriIn = implode(',', array_map('intval', $kategoriFilter));
    $whereKategori = "AND k.sub_kategori_id IN (
        SELECT id FROM kriminal_sub_kategoris WHERE kategori_id IN ($kategoriIn)
    )";
}
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
$whereTujuan = "";
if($akses != 'POLDA') {
    $whereTujuan .= " AND k.tujuan='$akses'";
}
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$whereTahun = "";
if($tahun) $whereTahun = "AND s.tahun = $tahun";
$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : 0;
$whereBulan = "";
if ($bulan) $whereBulan = "AND MONTH(k.tanggal) = $bulan AND YEAR(k.tanggal) = $tahun";
$whereWilayah = '';
if($level === 'kabupaten' && $parent_id) {
    $whereWilayah = "AND d.kecamatan_id IN (SELECT id FROM kecamatans WHERE kabupaten_id = $parent_id)";
} elseif($level === 'kecamatan' && $parent_id) {
    $whereWilayah = "AND k.desa_id IN (SELECT id FROM desas WHERE kecamatan_id = $parent_id)";
} elseif($level === 'desa' && $parent_id) {
    $whereWilayah = "AND k.desa_id = $parent_id";
}

// AGGREGATE by JAM
$sql = "
  SELECT
    SUM(CASE WHEN TIME(tanggal) >= '00:00:00' AND TIME(tanggal) < '06:00:00' THEN k.poin ELSE 0 END) as dini_hari,
    SUM(CASE WHEN TIME(tanggal) >= '06:00:00' AND TIME(tanggal) < '12:00:00' THEN k.poin ELSE 0 END) as pagi_hari,
    SUM(CASE WHEN TIME(tanggal) >= '12:00:00' AND TIME(tanggal) < '18:00:00' THEN k.poin ELSE 0 END) as siang_hari,
    SUM(CASE WHEN TIME(tanggal) >= '18:00:00' AND TIME(tanggal) < '24:00:00' THEN k.poin ELSE 0 END) as malam_hari
  FROM kriminals k
    LEFT JOIN sumbers s ON k.sumber_id = s.id
    LEFT JOIN desas d ON k.desa_id = d.id
  WHERE k.status=1 AND k.state != 'SELESAI'
    $whereKategori
    $whereSubKategori
    $whereTahun
    $whereBulan
    $whereWilayah
    $whereTujuan
";
$q = $pdo->query($sql);
$row = $q->fetch(PDO::FETCH_ASSOC);

// Format output [{label, total}, ...]
$result = [
  ['label' => 'Dini Hari (00-06)', 'total' => (int)($row['dini_hari']??0)],
  ['label' => 'Pagi Hari (06-12)', 'total' => (int)($row['pagi_hari']??0)],
  ['label' => 'Siang Hari (12-18)', 'total' => (int)($row['siang_hari']??0)],
  ['label' => 'Malam Hari (18-24)', 'total' => (int)($row['malam_hari']??0)],
];
echo json_encode($result);