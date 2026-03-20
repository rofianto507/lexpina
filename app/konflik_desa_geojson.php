<?php
session_start();
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
require_once("../config/configuration.php");
header("Content-Type: application/json");
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
    SELECT d.id, d.nama, d.geom,d.jenis,
      (SELECT COUNT(*) FROM konfliks WHERE desa_id=d.id) as total_konflik
    FROM desas d
    WHERE d.kecamatan_id=? AND d.status=1
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
      "total_konflik" => $row['total_konflik'],
      "jenis" => $row['jenis']
    ],
    "geometry" => $geometry
  ];
}
$sumberArr = [];
$stmtSumber = $pdo->prepare(
    "SELECT DISTINCT s.nama
     FROM konfliks k
     LEFT JOIN sumbers s ON k.sumber_id = s.id
     LEFT JOIN desas d ON k.desa_id = d.id
     WHERE d.kecamatan_id = ? AND k.status=1 AND k.sumber_id IS NOT NULL"
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