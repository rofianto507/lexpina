<?php
session_start();
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
require_once("../config/configuration.php");
header("Content-Type: application/json");

  $sumberArr = [];
  $stmtSumber = $pdo->prepare("SELECT DISTINCT s.nama FROM konfliks k LEFT JOIN sumbers s ON k.sumber_id=s.id WHERE k.status=1 and k.sumber_id is not null");
  $stmtSumber->execute();
  while($src = $stmtSumber->fetch(PDO::FETCH_ASSOC)) {
    if($src['nama']) $sumberArr[] = $src['nama'];
  }
  
$dataKonflik = [];
$q = $pdo->query("
  SELECT k.id as kab_id, COUNT(co.id) as total_konflik
  FROM kabupatens k
    LEFT JOIN kecamatans kc ON kc.kabupaten_id = k.id
    LEFT JOIN desas d ON d.kecamatan_id = kc.id
    LEFT JOIN konfliks co ON co.desa_id = d.id
  GROUP BY k.id
");
while($row = $q->fetch(PDO::FETCH_ASSOC)) {
  $dataKonflik[$row['kab_id']] = $row['total_konflik'];
}

// Build geojson
$features = [];
$qKab = $pdo->query("SELECT id, nama, geom FROM kabupatens WHERE status=1");
while($row = $qKab->fetch(PDO::FETCH_ASSOC)){
  $geometry = json_decode($row['geom'], true);
  // Query Kategori Konflik
  $kategori = [];
  $qKet = $pdo->prepare("
    SELECT kk.nama, COUNT(kf.id) as total
    FROM konflik_kategoris kk
      LEFT JOIN konfliks kf ON kf.kategori_id = kk.id
      LEFT JOIN desas d ON kf.desa_id = d.id
      LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
    WHERE kc.kabupaten_id = ?
    GROUP BY kk.id
    ORDER BY kk.nama
  ");
  $qKet->execute([$row['id']]);
  while($kat = $qKet->fetch(PDO::FETCH_ASSOC)){
    if($kat['total'] > 0) $kategori[] = ['label'=>$kat['nama'], 'total'=>$kat['total']];
  }

   $features[] = [
    "type" => "Feature",
    "properties" => [
      "id" => $row['id'],
      "nama" => $row['nama'],
      "total_konflik" => $dataKonflik[$row['id']] ?? 0,
       "kategori_konflik" => $kategori 
    ],
    "geometry" => $geometry
  ];
}
echo json_encode([
  "type"=>"FeatureCollection",
  "features"=>$features,
  "sumber_dokumen" => $sumberArr
]);