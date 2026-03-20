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
    $whereKategori = "AND kamtibmass.kategori_id IN ($kategoriIn)";
}
if($akses != 'POLDA') {
    $whereKategori .= " AND kamtibmass.tujuan='$akses'";
}
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$whereTahun = "";
if ($tahun) $whereTahun = "AND s.tahun = $tahun";

$kec_id = intval($_GET['kecamatan_id'] ?? 0);
$nama_kabupaten = '';
$nama_kecamatan = '';
$qKec = $pdo->prepare("SELECT k.nama as kec_nama, kb.nama as kab_nama FROM kecamatans k JOIN kabupatens kb ON k.kabupaten_id = kb.id WHERE k.id=? LIMIT 1");
$qKec->execute([$kec_id]);
if($rowKec = $qKec->fetch(PDO::FETCH_ASSOC)){
    $nama_kecamatan = $rowKec['kec_nama'];
    $nama_kabupaten = $rowKec['kab_nama'];
}
$features = [];
$qDesa = $pdo->prepare("
  SELECT d.id, d.nama, d.geom,
    (SELECT COUNT(*) FROM kamtibmass
     LEFT JOIN sumbers s ON kamtibmass.sumber_id = s.id
     WHERE kamtibmass.desa_id = d.id AND kamtibmass.status=1 AND kamtibmass.state!='SELESAI' $whereKategori $whereTahun
    ) AS total_kamtibmas 
  FROM desas d
  WHERE d.kecamatan_id = ? AND d.status=1
");
$qDesa->execute([$kec_id]);
while($row = $qDesa->fetch(PDO::FETCH_ASSOC)){
  $geometry = json_decode($row['geom'], true);
  $features[] = [
    "type" => "Feature",
    "properties" => [
      "id" => $row['id'],
      "nama" => $row['nama'],
      "kabupaten_nama" => $nama_kabupaten,
      "kecamatan_nama" => $nama_kecamatan,
      "total_kamtibmas" => $row['total_kamtibmas']
    ],
    "geometry" => $geometry
  ];
}
$sumberArr = [];
$stmtSumber = $pdo->prepare(
    "SELECT DISTINCT s.nama
     FROM kamtibmass
     LEFT JOIN sumbers s ON kamtibmass.sumber_id = s.id
     LEFT JOIN desas d ON kamtibmass.desa_id = d.id
     WHERE d.kecamatan_id = ? AND kamtibmass.status=1 AND kamtibmass.state!='SELESAI' AND kamtibmass.sumber_id IS NOT NULL $whereKategori $whereTahun"
);
$stmtSumber->execute([$kec_id]);
while($src = $stmtSumber->fetch(PDO::FETCH_ASSOC)) {
  if ($src['nama']) $sumberArr[] = $src['nama'];
}
echo json_encode([
  "type" => "FeatureCollection",
  "features" => $features,
  "sumber_dokumen" => $sumberArr
]);