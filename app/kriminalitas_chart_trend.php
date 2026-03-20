<?php
session_start();
$akses = $_SESSION['akses'] ?? '';
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
if (count($kategoriFilter) > 0) {
    $kategoriIn = implode(',', $kategoriFilter);
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
if ($tahun) $whereTahun = "AND YEAR(k.tanggal) = $tahun";
$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : 0;
$whereBulan = "";
if ($bulan) $whereBulan = "AND MONTH(k.tanggal) = $bulan AND YEAR(k.tanggal) = $tahun";
$whereWilayah = '';
if ($level === 'kabupaten' && $parent_id) {
    $whereWilayah = "AND d.kecamatan_id IN (SELECT id FROM kecamatans WHERE kabupaten_id = $parent_id)";
} elseif ($level === 'kecamatan' && $parent_id) {
    $whereWilayah = "AND k.desa_id IN (SELECT id FROM desas WHERE kecamatan_id = $parent_id)";
} elseif ($level === 'desa' && $parent_id) {
    $whereWilayah = "AND k.desa_id = $parent_id";
}

// AGGREGATE by BULAN, dengan state selesai/proses
$sql = "
    SELECT 
        MONTH(k.tanggal) as bulan,
         SUM(CASE WHEN k.state = 'SELESAI' THEN k.poin ELSE 0 END) as total_selesai,
        SUM(CASE WHEN k.state = 'PROSES' THEN k.poin ELSE 0 END) as total_proses
    FROM kriminals k
      LEFT JOIN desas d ON k.desa_id = d.id
    WHERE k.status=1
      $whereKategori
      $whereSubKategori
      $whereTujuan
      $whereTahun
      $whereBulan
      $whereWilayah
    GROUP BY bulan
    ORDER BY bulan
";
$q = $pdo->query($sql);

// Inisialisasi array bulan default 0
$data = [];
for ($i=1; $i<=12; $i++) {
  $data[$i] = ['selesai'=>0, 'proses'=>0, 'total'=>0, 'pct_selesai'=>0, 'pct_proses'=>0];
}
$q = $pdo->query($sql);
foreach ($q as $row) {
  $total = intval($row['total_selesai']) + intval($row['total_proses']);
  $pct_selesai = $total > 0 ? round($row['total_selesai']/$total*100, 1) : 0;
  $pct_proses  = $total > 0 ? round($row['total_proses']/$total*100, 1)  : 0;
  $data[intval($row['bulan'])] = [
    'selesai'     => intval($row['total_selesai']),
    'proses'      => intval($row['total_proses']),
    'total'       => $total,
    'pct_selesai' => $pct_selesai,
    'pct_proses'  => $pct_proses
  ];
}
$response = [
  'labels'       => ["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des"],
  'data_selesai' => [],
  'data_proses'  => [],
  'pct_selesai'  => [],
  'pct_proses'   => []
];
foreach ($data as $val) {
  $response['data_selesai'][] = $val['selesai'];
  $response['data_proses' ][] = $val['proses'];
  $response['pct_selesai'][]  = $val['pct_selesai'];
  $response['pct_proses'][]   = $val['pct_proses'];
}
echo json_encode($response);
?>