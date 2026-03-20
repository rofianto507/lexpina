<?php
session_start();
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
require_once("../config/configuration.php");
header("Content-Type: application/json");
$kab_id = intval($_GET['kabupaten_id'] ?? 0);

$dataKonflik = [];
$q = $pdo->prepare("
  SELECT kc.id as kec_id, COUNT(co.id) as total_konflik 
  FROM kecamatans kc
    LEFT JOIN desas d ON d.kecamatan_id = kc.id
    LEFT JOIN konfliks co ON co.desa_id = d.id
  WHERE kc.kabupaten_id = ?
  GROUP BY kc.id
");
$q->execute([$kab_id]);
while($row = $q->fetch(PDO::FETCH_ASSOC)) {
  $dataKonflik[$row['kec_id']] = $row['total_konflik'];
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
  $features[] = [
    "type" => "Feature",
    "properties" => [
      "id" => $row['id'],
      "nama" => $row['nama'],
      "kabupaten_nama" => $kabupaten_nama,
      "total_konflik" => $dataKonflik[$row['id']] ?? 0
    ],
    "geometry" => $geometry
  ];
}
$sumberArr = [];
$stmtSumber = $pdo->prepare(
    "SELECT DISTINCT s.nama 
     FROM konfliks k 
     LEFT JOIN desas d ON k.desa_id = d.id 
     LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
     LEFT JOIN sumbers s ON k.sumber_id = s.id 
     WHERE kc.kabupaten_id = ? AND k.status=1 AND k.sumber_id IS NOT NULL"
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