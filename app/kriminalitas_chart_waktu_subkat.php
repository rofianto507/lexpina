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
$bulan = $_GET['bulan'] ?? '';

$whereBulan = '';
if (!empty($bulan)) {
    $dates = explode(' to ', $bulan);
    $startDate = isset($dates[0]) && !empty($dates[0]) ? DateTime::createFromFormat('d/m/Y', trim($dates[0])) : null;
    $endDate   = isset($dates[1]) && !empty($dates[1]) ? DateTime::createFromFormat('d/m/Y', trim($dates[1])) : null;
    if ($startDate && $endDate) {
        $startStr = $startDate->format('Y-m-d');
        $endStr   = $endDate->format('Y-m-d');
        $whereBulan = " AND k.tanggal >= '$startStr' AND k.tanggal <= '$endStr' ";
    } elseif ($startDate) {
        $startStr = $startDate->format('Y-m-d');
        $whereBulan = " AND k.tanggal = '$startStr' ";
    }
}
$whereWilayah = '';
if($level === 'kabupaten' && $parent_id) {
    $whereWilayah = "AND d.kecamatan_id IN (SELECT id FROM kecamatans WHERE kabupaten_id = $parent_id)";
} elseif($level === 'kecamatan' && $parent_id) {
    $whereWilayah = "AND k.desa_id IN (SELECT id FROM desas WHERE kecamatan_id = $parent_id)";
} elseif($level === 'desa' && $parent_id) {
    $whereWilayah = "AND k.desa_id = $parent_id";
}
$subkat = $_GET['subkat'] ?? '';

// AGGREGATE by JAM
$sql = "
  SELECT
    SUM(CASE WHEN TIME(tanggal) >= '00:00:00' AND TIME(tanggal) < '02:59:00' THEN k.poin ELSE 0 END) as 00_00_02_59,
    SUM(CASE WHEN TIME(tanggal) >= '03:00:00' AND TIME(tanggal) < '05:59:00' THEN k.poin ELSE 0 END) as 03_00_05_59,
    SUM(CASE WHEN TIME(tanggal) >= '06:00:00' AND TIME(tanggal) < '08:59:00' THEN k.poin ELSE 0 END) as 06_00_08_59,
    SUM(CASE WHEN TIME(tanggal) >= '09:00:00' AND TIME(tanggal) < '11:59:00' THEN k.poin ELSE 0 END) as 09_00_11_59,
    SUM(CASE WHEN TIME(tanggal) >= '12:00:00' AND TIME(tanggal) < '14:59:00' THEN k.poin ELSE 0 END) as 12_00_14_59,
    SUM(CASE WHEN TIME(tanggal) >= '15:00:00' AND TIME(tanggal) < '17:59:00' THEN k.poin ELSE 0 END) as 15_00_17_59,
    SUM(CASE WHEN TIME(tanggal) >= '18:00:00' AND TIME(tanggal) < '20:59:00' THEN k.poin ELSE 0 END) as 18_00_20_59,
    SUM(CASE WHEN TIME(tanggal) >= '21:00:00' AND TIME(tanggal) < '23:59:00' THEN k.poin ELSE 0 END) as 21_00_23_59
  FROM kriminals k
    LEFT JOIN sumbers s ON k.sumber_id = s.id
    LEFT JOIN desas d ON k.desa_id = d.id
  WHERE k.status=1 AND k.state != 'SELESAI' AND k.sub_kategori_id = $subkat
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
  ['label' => '00.00-02.59', 'total' => (int)($row['00_00_02_59']??0)],
  ['label' => '03.00-05.59', 'total' => (int)($row['03_00_05_59']??0)],
  ['label' => '06.00-08.59', 'total' => (int)($row['06_00_08_59']??0)],
  ['label' => '09.00-11.59', 'total' => (int)($row['09_00_11_59']??0)],
  ['label' => '12.00-14.59', 'total' => (int)($row['12_00_14_59']??0)],
  ['label' => '15.00-17.59', 'total' => (int)($row['15_00_17_59']??0)],
  ['label' => '18.00-20.59', 'total' => (int)($row['18_00_20_59']??0)],
  ['label' => '21.00-23.59', 'total' => (int)($row['21_00_23_59']??0)],
];
echo json_encode($result);