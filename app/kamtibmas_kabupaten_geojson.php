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
    $kategoriIn = implode(',', array_map('intval', $kategoriFilter));
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
if($tahun) $whereTahun = " AND s.tahun = $tahun ";

$features = [];
$qKab = $pdo->query("
  SELECT k.id, k.nama, k.geom,
    (SELECT COUNT(*) FROM kamtibmass
    LEFT JOIN desas d ON kamtibmass.desa_id=d.id
    LEFT JOIN kecamatans kc ON d.kecamatan_id=kc.id
    LEFT JOIN sumbers s ON kamtibmass.sumber_id = s.id
    WHERE kc.kabupaten_id=k.id and kamtibmass.status=1 and kamtibmass.state!='SELESAI'
      $whereKategori $whereTahun
) AS total_kamtibmas,
(SELECT COUNT(*) FROM kamtibmass
    LEFT JOIN desas d ON kamtibmass.desa_id=d.id
    LEFT JOIN kecamatans kc ON d.kecamatan_id=kc.id
    LEFT JOIN sumbers s ON kamtibmass.sumber_id = s.id
    WHERE kc.kabupaten_id=k.id AND kamtibmass.is_menonjol=1 and kamtibmass.status=1 and kamtibmass.state!='SELESAI'
      $whereKategori $whereTahun
) AS total_menonjol
  FROM kabupatens k
  WHERE k.status=1
");
while($row = $qKab->fetch(PDO::FETCH_ASSOC)){
  $geometry = json_decode($row['geom'], true);
  $has_menonjol = ($row['total_menonjol'] > 0) ? 1 : 0;
  $features[] = [
    "type" => "Feature",
    "properties" => [
      "id" => $row['id'],
      "nama" => $row['nama'],
      "total_kamtibmas" => $row['total_kamtibmas'],
      "has_menonjol" => $has_menonjol
    ],
    "geometry" => $geometry
  ];
}
  $sumberArr = [];
$sumberSql = "SELECT DISTINCT s.nama FROM kamtibmass
              LEFT JOIN sumbers s ON kamtibmass.sumber_id=s.id
              WHERE kamtibmass.status=1 and kamtibmass.sumber_id is not null AND kamtibmass.state!='SELESAI' $whereKategori $whereTahun";
 
$stmtSumber = $pdo->prepare($sumberSql);
$stmtSumber->execute();
  while($src = $stmtSumber->fetch(PDO::FETCH_ASSOC)) {
    if($src['nama']) $sumberArr[] = $src['nama'];
  }
echo json_encode([
  "type" => "FeatureCollection",
  "features" => $features,
  "sumber_dokumen" => $sumberArr
]);