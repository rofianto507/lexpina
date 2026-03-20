<?php
session_start();
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

$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$whereTahun = "";
if ($tahun) $whereTahun = "AND s.tahun = $tahun";
$polsek_id = intval($_GET['polsek_id'] ?? 0);
 
$dataKriminalitas = [];
$q = $pdo->prepare("
  SELECT kc.id as kec_id, COUNT(co.id) as total_kriminalitas 
  FROM kecamatans kc
    LEFT JOIN desas d ON d.kecamatan_id = kc.id
    LEFT JOIN kriminals co ON co.desa_id = d.id
      LEFT JOIN sumbers s ON co.sumber_id = s.id
  WHERE co.polsek_id = ? AND co.status=1 AND co.state!='SELESAI' $whereKategori $whereTahun
  GROUP BY kc.id
");
$q->execute([$polsek_id]);
while($row = $q->fetch(PDO::FETCH_ASSOC)) {
  $dataKriminalitas[$row['kec_id']] = $row['total_kriminalitas'];
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
      "nama" => $row['nama'],
      "total_kriminalitas" => $dataKriminalitas[$row['id']] ?? 0
    ],
    "geometry" => $geometry
  ];
}
$sumberArr = [];
$sumberSql = "SELECT DISTINCT s.nama 
 FROM kriminals co
 LEFT JOIN desas d ON co.desa_id = d.id 
 LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
 LEFT JOIN sumbers s ON co.sumber_id = s.id 
 WHERE co.polsek_id = ? AND co.status=1 AND co.sumber_id IS NOT NULL AND co.state!='SELESAI' $whereKategori $whereTahun";
$stmtSumber = $pdo->prepare($sumberSql);
$stmtSumber->execute([$polsek_id]);
while($src = $stmtSumber->fetch(PDO::FETCH_ASSOC)) {
  if ($src['nama']) $sumberArr[] = $src['nama'];
}
echo json_encode([
  "type" => "FeatureCollection",
  "features" => $features,
  "sumber_dokumen" => $sumberArr
]);