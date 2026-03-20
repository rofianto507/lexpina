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

$kab_id = intval($_GET['kabupaten_id'] ?? 0);
$kabupaten_nama = '';
$qKab = $pdo->prepare("SELECT nama FROM kabupatens WHERE id=? LIMIT 1");
$qKab->execute([$kab_id]);
if($rowKab = $qKab->fetch(PDO::FETCH_ASSOC)){
    $kabupaten_nama = $rowKab['nama'];
}
$features = [];
$qKec = $pdo->prepare("
  SELECT kc.id, kc.nama, kc.geom,
    (SELECT COUNT(*) FROM kamtibmass
      LEFT JOIN sumbers s ON kamtibmass.sumber_id = s.id
      LEFT JOIN desas d ON kamtibmass.desa_id = d.id
      WHERE d.kecamatan_id = kc.id AND kamtibmass.status=1 AND kamtibmass.state!='SELESAI' $whereKategori $whereTahun
    ) AS total_kamtibmas 
  FROM kecamatans kc
  WHERE kc.kabupaten_id = ? AND kc.status=1
");
$qKec->execute([$kab_id]);
while($row = $qKec->fetch(PDO::FETCH_ASSOC)){
  $geometry = json_decode($row['geom'], true);
 
  $features[] = [
    "type" => "Feature",
    "properties" => [
      "id" => $row['id'],
      "nama" => $row['nama'],
      "kabupaten_nama" => $kabupaten_nama,
      "total_kamtibmas" => $row['total_kamtibmas'] 
    ],
    "geometry" => $geometry
  ];
}
$sumberArr = [];
$stmtSumber = $pdo->prepare(
    "SELECT DISTINCT s.nama 
     FROM kamtibmass
     LEFT JOIN desas d ON kamtibmass.desa_id = d.id 
     LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
     LEFT JOIN sumbers s ON kamtibmass.sumber_id = s.id
     WHERE kc.kabupaten_id = ? AND kamtibmass.status=1 AND kamtibmass.state!='SELESAI' AND kamtibmass.sumber_id IS NOT NULL $whereKategori $whereTahun"
);
$stmtSumber->execute([$kab_id]);
while($src = $stmtSumber->fetch(PDO::FETCH_ASSOC)) {
  if ($src['nama']) $sumberArr[] = $src['nama'];
}
echo json_encode([
  "type" => "FeatureCollection",
  "features" => $features,
  "sumber_dokumen" => $sumberArr
]);