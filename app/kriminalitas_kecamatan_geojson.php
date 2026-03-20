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
if(!empty($_GET['kategori'])){
    if(is_array($_GET['kategori'])) $kategoriFilter = array_map('intval', $_GET['kategori']);
    else $kategoriFilter = array_map('intval', explode(',', $_GET['kategori']));
    $kategoriIn = implode(',', array_map('intval',$kategoriFilter));
}
$whereKategori = "";
if(!empty($kategoriIn)){
    // Cari semua id sub kategori: kategori utama -> sub
    $subIDs = [];
    $qSK = $pdo->query("SELECT id FROM kriminal_sub_kategoris WHERE kategori_id IN ($kategoriIn)");
    $subIDs = $qSK->fetchAll(PDO::FETCH_COLUMN);
    if(count($subIDs)){
        $whereKategori = "AND co.sub_kategori_id IN (".implode(',',array_map('intval',$subIDs)).")";
    } else {
        $whereKategori = "AND 1=0";
    }
}
$subKategoriFilter = [];
if (!empty($_GET['sub_kategori'])) {
    if (is_array($_GET['sub_kategori'])) $subKategoriFilter = array_map('intval', $_GET['sub_kategori']);
    else $subKategoriFilter = array_map('intval', explode(',', $_GET['sub_kategori']));
}
$whereSubKategori = '';
if (count($subKategoriFilter) > 0) {
    $subKSK = implode(',', array_map('intval', $subKategoriFilter));
    $whereSubKategori = " AND co.sub_kategori_id IN ($subKSK) ";
    $whereKategori = ''; // Override filter kategori jika filter sub kategori dipilih, karena sub kategori sudah pasti punya kategori
}


$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$whereTahun = "";
if ($tahun) $whereTahun = "AND s.tahun = $tahun";
$bulan = $_GET['bulan'] ?? '';
$whereBulan = '';
if (!empty($bulan)) {
    $dates = explode(' to ', $bulan);
    $startDate = isset($dates[0]) && !empty($dates[0]) ? DateTime::createFromFormat('d/m/Y', trim($dates[0])) : null;
    $endDate   = isset($dates[1]) && !empty($dates[1]) ? DateTime::createFromFormat('d/m/Y', trim($dates[1])) : null;
    if ($startDate && $endDate) {
        $startStr = $startDate->format('Y-m-d');
        $endStr   = $endDate->format('Y-m-d');
        $whereBulan = " AND co.tanggal >= '$startStr' AND co.tanggal <= '$endStr' ";
    } elseif ($startDate) {
        $startStr = $startDate->format('Y-m-d');
        $whereBulan = " AND co.tanggal = '$startStr' ";
    }
}
$kab_id = intval($_GET['kabupaten_id'] ?? 0);
$polsek_id = intval($_GET['polsek_id'] ?? 0);
$wherePolsek = "";
if ($polsek_id) $wherePolsek = "AND kc.polsek_id = $polsek_id";

$sqlProses = "
  SELECT kc.id as kec_id, COUNT(co.id) as total_proses
  FROM kecamatans kc
    LEFT JOIN desas d ON d.kecamatan_id = kc.id
    LEFT JOIN kriminals co ON co.desa_id = d.id
    LEFT JOIN sumbers s ON co.sumber_id = s.id
  WHERE kc.kabupaten_id = ? AND co.status=1 AND co.state!='SELESAI'
  $whereKategori $whereSubKategori $whereTahun $whereBulan $wherePolsek
  GROUP BY kc.id
";
$qProses = $pdo->prepare($sqlProses);
$qProses->execute([$kab_id]);
$dataProses = [];
while($row = $qProses->fetch(PDO::FETCH_ASSOC)) {
  $dataProses[$row['kec_id']] = $row['total_proses'];
}

// Query kasus selesai (state = 'SELESAI')
$sqlSelesai = "
  SELECT kc.id as kec_id, COUNT(co.id) as total_selesai
  FROM kecamatans kc
    LEFT JOIN desas d ON d.kecamatan_id = kc.id
    LEFT JOIN kriminals co ON co.desa_id = d.id
    LEFT JOIN sumbers s ON co.sumber_id = s.id
  WHERE kc.kabupaten_id = ? AND co.status=1 AND co.state='SELESAI'
  $whereKategori $whereSubKategori $whereTahun $whereBulan $wherePolsek
  GROUP BY kc.id
";
$qSelesai = $pdo->prepare($sqlSelesai);
$qSelesai->execute([$kab_id]);
$dataSelesai = [];
while($row = $qSelesai->fetch(PDO::FETCH_ASSOC)) {
  $dataSelesai[$row['kec_id']] = $row['total_selesai'];
}

$kabupaten_nama = '';
 
  $qKab = $pdo->prepare("SELECT nama FROM kabupatens WHERE id=? LIMIT 1");
  $qKab->execute([$kab_id]);
 
if($rowKab = $qKab->fetch(PDO::FETCH_ASSOC)){
    $kabupaten_nama = $rowKab['nama'];
}

$features = [];
$qKec = $pdo->prepare("SELECT id, nama, geom FROM kecamatans WHERE kabupaten_id=? AND status=1");
$qKec->execute([$kab_id]);
while($row = $qKec->fetch(PDO::FETCH_ASSOC)){
  $geometry = json_decode($row['geom'], true);
  $kec_id = $row['id'];
  $proses = $dataProses[$kec_id] ?? 0;
  $selesai = $dataSelesai[$kec_id] ?? 0;
  $features[] = [
    "type" => "Feature",
    "properties" => [
      "id" => $kec_id,
      "nama" => $row['nama'],
      "kabupaten_nama" => $kabupaten_nama,
      "total_proses" => $proses,
      "total_selesai" => $selesai,
      "total_kriminalitas" => $proses + $selesai
    ],
    "geometry" => $geometry
  ];
}
$sumberArr = [];
if($akses=='POLDA'){
   $sumberSql = "SELECT DISTINCT s.nama 
 FROM kriminals co
 LEFT JOIN desas d ON co.desa_id = d.id 
 LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
 LEFT JOIN sumbers s ON co.sumber_id = s.id
 WHERE kc.kabupaten_id = ? AND co.status=1 AND co.sumber_id IS NOT NULL AND co.state!='SELESAI' $whereKategori $whereSubKategori $whereTahun $whereBulan";
} else {
$sumberSql = "SELECT DISTINCT s.nama 
 FROM kriminals co
 LEFT JOIN desas d ON co.desa_id = d.id 
 LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
 LEFT JOIN sumbers s ON co.sumber_id = s.id
 WHERE kc.kabupaten_id = ? AND co.status=1 AND co.sumber_id IS NOT NULL AND co.state!='SELESAI' AND co.tujuan='$akses' $whereKategori $whereSubKategori $whereTahun $whereBulan";
}
$stmtSumber = $pdo->prepare($sumberSql);
$stmtSumber->execute([$kab_id]);
while($src = $stmtSumber->fetch(PDO::FETCH_ASSOC)) {
  if ($src['nama']) $sumberArr[] = $src['nama'];
}
echo json_encode([
  "type" => "FeatureCollection",
  "features" => $features,
  "sumber_dokumen" => $sumberArr
]);