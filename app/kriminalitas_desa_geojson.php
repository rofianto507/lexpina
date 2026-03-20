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
$kec_id = intval($_GET['kecamatan_id'] ?? 0);
$kategoriFilter = [];
if(!empty($_GET['kategori'])){
    if(is_array($_GET['kategori'])) $kategoriFilter = array_map('intval', $_GET['kategori']);
    else $kategoriFilter = array_map('intval', explode(',', $_GET['kategori']));
    $kategoriIn = implode(',', array_map('intval',$kategoriFilter));
}
$whereKategori = "";
if(!empty($kategoriIn)){
    $subIDs = [];
    $qSK = $pdo->prepare("SELECT id FROM kriminal_sub_kategoris WHERE kategori_id IN ($kategoriIn)");
    $qSK->execute();
    $subIDs = $qSK->fetchAll(PDO::FETCH_COLUMN);
    if(count($subIDs)){
        $whereKategori = "AND k.sub_kategori_id IN (".implode(',',array_map('intval',$subIDs)).")";
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
    $whereSubKategori = " AND k.sub_kategori_id IN ($subKSK) ";
    $whereKategori = ''; // Override filter kategori jika filter sub kategori dipilih, karena sub kategori sudah pasti punya kategori
}

$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$whereTahun = "";
if ($tahun) $whereTahun = "AND s.tahun = $tahun";

$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : 0;
$whereBulan = "";
if ($bulan) $whereBulan = " AND MONTH(k.tanggal) = $bulan and YEAR(k.tanggal) = $tahun ";

$nama_kabupaten = '';
$nama_kecamatan = '';
$qKec = $pdo->prepare("SELECT k.nama as kec_nama, kb.nama as kab_nama FROM kecamatans k JOIN kabupatens kb ON k.kabupaten_id = kb.id WHERE k.id=? LIMIT 1");
$qKec->execute([$kec_id]);
if($rowKec = $qKec->fetch(PDO::FETCH_ASSOC)){
    $nama_kecamatan = $rowKec['kec_nama'];
    $nama_kabupaten = $rowKec['kab_nama'];
}

$features = [];
// Query untuk setiap desa: hitung total proses & selesai
$qDesa = $pdo->prepare("SELECT id, nama, geom, jenis FROM desas WHERE kecamatan_id=? AND status=1");
$qDesa->execute([$kec_id]);
while($row = $qDesa->fetch(PDO::FETCH_ASSOC)){
  $desa_id = $row['id'];
  $geometry = json_decode($row['geom'], true);

  // Hitung total PROSES per desa
  $sqlProses = "SELECT COUNT(*) FROM kriminals k
      LEFT JOIN sumbers s ON k.sumber_id = s.id
      WHERE k.desa_id=? AND k.status=1 AND k.state!='SELESAI' $whereKategori $whereSubKategori $whereTahun $whereBulan";
  if ($akses != 'POLDA') $sqlProses .= " AND k.tujuan='$akses'";
  $qProses = $pdo->prepare($sqlProses);
  $qProses->execute([$desa_id]);
  $total_proses = $qProses->fetchColumn() ?: 0;

  // Hitung total SELESAI per desa
  $sqlSelesai = "SELECT COUNT(*) FROM kriminals k
      LEFT JOIN sumbers s ON k.sumber_id = s.id
      WHERE k.desa_id=? AND k.status=1 AND k.state='SELESAI' $whereKategori $whereSubKategori $whereTahun $whereBulan";
  if ($akses != 'POLDA') $sqlSelesai .= " AND k.tujuan='$akses'";
  $qSelesai = $pdo->prepare($sqlSelesai);
  $qSelesai->execute([$desa_id]);
  $total_selesai = $qSelesai->fetchColumn() ?: 0;

  $features[] = [
    "type" => "Feature",
    "properties" => [
      "id" => $row['id'],
      "nama" => $row['nama'],
      "kabupaten_nama" => $nama_kabupaten,
      "kecamatan_nama" => $nama_kecamatan,
      "jenis" => $row['jenis'],
      "total_proses" => intval($total_proses),
      "total_selesai" => intval($total_selesai),
      "total_kriminalitas" => intval($total_proses) + intval($total_selesai)
    ],
    "geometry" => $geometry
  ];
}

$sumberArr = [];
if($akses == 'POLDA') {
$sumberSql = "SELECT DISTINCT s.nama
 FROM kriminals k
 LEFT JOIN sumbers s ON k.sumber_id = s.id
 LEFT JOIN desas d ON k.desa_id = d.id
 WHERE d.kecamatan_id = ? AND k.status=1 AND k.sumber_id IS NOT NULL AND k.state!='SELESAI' $whereKategori $whereSubKategori $whereTahun $whereBulan";
} else {
$sumberSql = "SELECT DISTINCT s.nama
 FROM kriminals k
 LEFT JOIN sumbers s ON k.sumber_id = s.id
 LEFT JOIN desas d ON k.desa_id = d.id
 WHERE d.kecamatan_id = ? AND k.status=1 AND k.sumber_id IS NOT NULL AND k.state!='SELESAI' AND k.tujuan='$akses' $whereKategori $whereSubKategori $whereTahun $whereBulan";
}
$stmtSumber = $pdo->prepare($sumberSql);
$stmtSumber->execute([$kec_id]);
while($src = $stmtSumber->fetch(PDO::FETCH_ASSOC)) {
  if ($src['nama']) $sumberArr[] = $src['nama'];
}
echo json_encode([
  "type" => "FeatureCollection",
  "features" => $features,
  "sumber_dokumen" => $sumberArr
]);