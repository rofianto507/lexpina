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
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : 0;
$whereTahun = "";
if($tahun) $whereTahun = " AND s.tahun = $tahun ";
$whereBulan = "";
if($bulan) $whereBulan = " AND MONTH(kriminals.tanggal) = $bulan and YEAR(kriminals.tanggal) = $tahun "; 

$kategoriFilter = [];
if(!empty($_GET['kategori'])) {
    if (is_array($_GET['kategori'])) $kategoriFilter = array_map('intval', $_GET['kategori']);
    else $kategoriFilter = array_map('intval', explode(',', $_GET['kategori']));
}

$whereKategori = "";
if(count($kategoriFilter) > 0){
    $placeholders = implode(',', array_fill(0, count($kategoriFilter), '?'));
    $qSK = $pdo->prepare("SELECT id FROM kriminal_sub_kategoris WHERE kategori_id IN ($placeholders)");
    $qSK->execute($kategoriFilter);
    $subKategoriIds = $qSK->fetchAll(PDO::FETCH_COLUMN);
    if(count($subKategoriIds) > 0){
        $subSK = implode(',', array_map('intval',$subKategoriIds));
        $whereKategori = " AND kriminals.sub_kategori_id IN ($subSK)";
    } else {
        $whereKategori = " AND 1=0 ";
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
    $whereSubKategori = " AND kriminals.sub_kategori_id IN ($subKSK) ";
    $whereKategori = ''; // Override filter kategori jika filter sub kategori dipilih, karena sub kategori sudah pasti punya kategori
}

$params = $kategoriFilter; // Untuk bind param di subquery, jika pakai bind param di SQL
if($akses=='POLDA'){
  $qKab = $pdo->query("
    SELECT k.id, k.nama, k.geom,
      (SELECT sum(kriminals.poin) FROM kriminals
        LEFT JOIN desas d ON kriminals.desa_id=d.id
        LEFT JOIN kecamatans kc ON d.kecamatan_id=kc.id
        LEFT JOIN sumbers s ON kriminals.sumber_id=s.id
        WHERE kc.kabupaten_id=k.id AND kriminals.status=1 and kriminals.state!='SELESAI' $whereKategori $whereSubKategori $whereTahun $whereBulan
      ) AS total_kriminalitas,
      (SELECT sum(kriminals.poin) FROM kriminals
        LEFT JOIN desas d ON kriminals.desa_id=d.id
        LEFT JOIN kecamatans kc ON d.kecamatan_id=kc.id
        LEFT JOIN sumbers s ON kriminals.sumber_id=s.id
        WHERE kc.kabupaten_id=k.id AND kriminals.status=1 and kriminals.state='SELESAI' $whereKategori $whereSubKategori $whereTahun $whereBulan
      ) AS total_kriminalitas_selesai
    FROM kabupatens k
    WHERE k.status=1
  ");
}else{
   $qKab = $pdo->query("
    SELECT k.id, k.nama, k.geom,
      (SELECT sum(kriminals.poin) FROM kriminals
        LEFT JOIN desas d ON kriminals.desa_id=d.id
        LEFT JOIN kecamatans kc ON d.kecamatan_id=kc.id
        LEFT JOIN sumbers s ON kriminals.sumber_id=s.id
        WHERE kc.kabupaten_id=k.id AND kriminals.status=1 and kriminals.state!='SELESAI' AND kriminals.tujuan='$akses' $whereKategori $whereSubKategori $whereTahun $whereBulan
      ) AS total_kriminalitas,
      (SELECT sum(kriminals.poin) FROM kriminals
        LEFT JOIN desas d ON kriminals.desa_id=d.id
        LEFT JOIN kecamatans kc ON d.kecamatan_id=kc.id
        LEFT JOIN sumbers s ON kriminals.sumber_id=s.id
        WHERE kc.kabupaten_id=k.id AND kriminals.status=1 and kriminals.state='SELESAI' AND kriminals.tujuan='$akses' $whereKategori $whereSubKategori $whereTahun $whereBulan
      ) AS total_kriminalitas_selesai
    FROM kabupatens k
    WHERE k.status=1
  ");
}
$features = [];
while($row = $qKab->fetch(PDO::FETCH_ASSOC)){
  $geometry = json_decode($row['geom'], true);
 
  $features[] = [
    "type" => "Feature",
    "properties" => [
      "id" => $row['id'],
      "nama" => $row['nama'],
      "total_kriminalitas" => $row['total_kriminalitas']?intval($row['total_kriminalitas']):0,
      "total_kriminalitas_selesai" => $row['total_kriminalitas_selesai']?intval($row['total_kriminalitas_selesai']):0
    ],
    "geometry" => $geometry
  ];
}
if($akses=='POLDA'){
  $sumberSql = "SELECT DISTINCT s.nama
                FROM kriminals
                LEFT JOIN sumbers s ON kriminals.sumber_id=s.id
                WHERE kriminals.status=1 and kriminals.sumber_id is not null $whereKategori $whereSubKategori $whereBulan";
}else{
  $sumberSql = "SELECT DISTINCT s.nama
                FROM kriminals
                LEFT JOIN sumbers s ON kriminals.sumber_id=s.id
                WHERE kriminals.status=1 and kriminals.sumber_id is not null AND kriminals.tujuan='$akses' $whereKategori $whereSubKategori $whereBulan";
}
  if($tahun) $sumberSql .= " AND s.tahun = $tahun";

  $sumberArr = [];
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