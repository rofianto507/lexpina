<?php
session_start();
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
require_once("../config/configuration.php");
header("Content-Type: application/json");
$polsek_id = intval($_GET['polsek_id'] ?? 0);


$kecamatan_nama = '';
$qKab = $pdo->prepare("SELECT nama FROM kecamatans WHERE polsek_id=? LIMIT 1");
$qKab->execute([$polsek_id]);
if($rowKab = $qKab->fetch(PDO::FETCH_ASSOC)){
    $kecamatan_nama = $rowKab['nama'];
}

$features = [];
$qKec = $pdo->prepare("SELECT id, nama, geom FROM kecamatans WHERE polsek_id=? AND status=1");
$qKec->execute([$polsek_id]);
while($row = $qKec->fetch(PDO::FETCH_ASSOC)){
  $geometry = json_decode($row['geom'], true);
  $features[] = [
    "type" => "Feature",
    "properties" => [
      "id" => $row['id'],
      "nama" => $row['nama'] 
    ],
    "geometry" => $geometry
  ];
}
 
echo json_encode([
  "type" => "FeatureCollection",
  "features" => $features 
]);