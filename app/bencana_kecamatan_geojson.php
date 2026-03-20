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
$whereKategori = '';
if(!empty($kategoriIn)){
    $whereKategori = "AND bencanas.kategori_id IN ($kategoriIn)";
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
    (SELECT COUNT(*) FROM bencanas
      LEFT JOIN desas d ON bencanas.desa_id = d.id
      LEFT JOIN sumbers s ON bencanas.sumber_id = s.id
      WHERE d.kecamatan_id = kc.id
        AND bencanas.status=1
        $whereKategori $whereTahun
    ) AS total_bencana
  FROM kecamatans kc
  WHERE kc.kabupaten_id = ? AND kc.status=1
");
$qKec->execute([$kab_id]);
while($row = $qKec->fetch(PDO::FETCH_ASSOC)){
  $geometry = json_decode($row['geom'], true);
  $kategori = [];
$qKat = $pdo->prepare("
    SELECT lk.nama, COUNT(bencanas.id) as total
    FROM bencana_kategoris lk
    LEFT JOIN bencanas ON bencanas.kategori_id = lk.id AND bencanas.status = 1
    LEFT JOIN desas d ON bencanas.desa_id = d.id
    LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
    LEFT JOIN sumbers s ON bencanas.sumber_id = s.id
    WHERE kc.id = ? ".
    (!empty($kategoriIn) ? " AND bencanas.kategori_id IN ($kategoriIn) " : "").
    ($tahun ? " AND s.tahun = $tahun " : "") . "
    GROUP BY lk.id
    ORDER BY lk.nama
");
$qKat->execute([$row['id']]);
  while($kat = $qKat->fetch(PDO::FETCH_ASSOC)){
    if($kat['total'] > 0) $kategori[] = ['label'=>$kat['nama'], 'total'=>$kat['total']];
  }
  $features[] = [
    "type" => "Feature",
    "properties" => [
      "id" => $row['id'],
      "nama" => $row['nama'],
      "kabupaten_nama" => $kabupaten_nama,
      "total_bencana" => $row['total_bencana'],
      "kategori_bencana" => $kategori
    ],
    "geometry" => $geometry
  ];
}
$sumberArr = [];
$stmtSumber = $pdo->prepare("
    SELECT DISTINCT s.nama
    FROM bencanas
    LEFT JOIN desas d ON bencanas.desa_id = d.id
    LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
    LEFT JOIN sumbers s ON bencanas.sumber_id = s.id
    WHERE kc.kabupaten_id = ? AND bencanas.status=1 AND bencanas.sumber_id IS NOT NULL
    $whereKategori $whereTahun
");
$stmtSumber->execute([$kab_id]);
while($src = $stmtSumber->fetch(PDO::FETCH_ASSOC)) {
  if ($src['nama']) $sumberArr[] = $src['nama'];
}
echo json_encode([
  "type" => "FeatureCollection",
  "features" => $features,
  "sumber_dokumen" => $sumberArr
]);