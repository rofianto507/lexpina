<?php
session_start();
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
require_once("../config/configuration.php");
header('Content-Type: application/json');

$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : 0;
$subKategoriFilter = [];
if (!empty($_GET['sub_kategori'])) {
    if (is_array($_GET['sub_kategori'])) $subKategoriFilter = array_map('intval', $_GET['sub_kategori']);
    else $subKategoriFilter = array_map('intval', explode(',', $_GET['sub_kategori']));
}
$whereSubKategori = '';
if (count($subKategoriFilter) > 0) {
    $subKSK = implode(',', array_map('intval', $subKategoriFilter));
    $whereSubKategori = " AND k.sub_kategori_id IN ($subKSK) ";
}
$kategoriFilter = [];
$whereKategori = "";
if (count($subKategoriFilter) == 0 && !empty($_GET['kategori'])) {
    if(is_array($_GET['kategori'])) $kategoriFilter = array_map('intval', $_GET['kategori']);
    else $kategoriFilter = array_map('intval', explode(',', $_GET['kategori']));

    if(count($kategoriFilter) > 0){
        $placeholders = implode(',', array_fill(0, count($kategoriFilter), '?'));
        $qSK = $pdo->prepare("SELECT id FROM kriminal_sub_kategoris WHERE kategori_id IN ($placeholders)");
        $qSK->execute($kategoriFilter);
        $subKategoriIds = $qSK->fetchAll(PDO::FETCH_COLUMN);
        if(count($subKategoriIds) > 0){
            $subSK = implode(',', array_map('intval', $subKategoriIds));
            $whereKategori = " AND k.sub_kategori_id IN ($subSK)";
        } else {
            $whereKategori = " AND 1=0 ";
        }
    }
}
if(!empty($_GET['polres_id'])) {
    $polres_id = intval($_GET['polres_id']);
    $whereKategori .= " AND kab.polres_id = $polres_id ";
}
if(!empty($_GET['polsek_id'])) {
    $polsek_id = intval($_GET['polsek_id']);
    $whereKategori .= " AND kec.polsek_id = $polsek_id ";
}


$whereTahun = "";
if($tahun) $whereTahun = " AND s.tahun = $tahun ";
$whereBulan = "";
if($bulan) $whereBulan = " AND MONTH(k.tanggal) = $bulan and YEAR(k.tanggal) = $tahun "; 

$kabupaten_id = isset($_GET['kabupaten_id']) ? intval($_GET['kabupaten_id']) : 0;
$whereKabupaten = "";
if($kabupaten_id) $whereKabupaten = " AND kab.id = $kabupaten_id ";
$kecamatan_id = isset($_GET['kecamatan_id']) ? intval($_GET['kecamatan_id']) : 0;
$whereKecamatan = "";
if($kecamatan_id) $whereKecamatan = " AND kec.id = $kecamatan_id ";

// Query data dengan filter
$sql = "SELECT 
            k.id, k.lokasi, k.no_lp, k.tanggal, k.keterangan,
            k.latitude, k.longitude, 
            sk.nama as sub_kategori_nama,
            ka.nama as kategori_nama,
            
            d.nama as desa_nama, kec.nama as kec_nama, kab.nama as kab_nama
        FROM kriminals k
        LEFT JOIN kriminal_sub_kategoris sk ON k.sub_kategori_id = sk.id
        LEFT JOIN kriminal_kategoris ka ON sk.kategori_id = ka.id
        LEFT JOIN desas d ON k.desa_id = d.id
        LEFT JOIN kecamatans kec ON d.kecamatan_id = kec.id
        LEFT JOIN kabupatens kab ON kec.kabupaten_id = kab.id
        LEFT JOIN sumbers s ON k.sumber_id = s.id
        WHERE k.status=1 AND k.latitude IS NOT NULL AND k.longitude IS NOT NULL
        $whereSubKategori
        $whereKategori
        $whereTahun
        $whereKabupaten
        $whereKecamatan
        $whereBulan
        ORDER BY k.id DESC
        LIMIT 1000"; // limit biar map tidak berat

$stmt = $pdo->query($sql);

$features = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if(!is_numeric($row['latitude']) || !is_numeric($row['longitude'])) continue; // skip invalid
    $properties = [
        "id" => $row['id'],
        "kategori_nama"   => $row['kategori_nama'],
        "sub_kategori_nama" => $row['sub_kategori_nama'],
        "lokasi" => $row['lokasi'],
        "desa_nama" => $row['desa_nama'],
        "kec_nama" => $row['kec_nama'],
        "kab_nama" => $row['kab_nama'],
        "tanggal" => $row['tanggal'],
        "keterangan" => $row['keterangan'],
        "no_lp" => $row['no_lp'],
        "warna_marker" => 'blue'
    ];
    $features[] = [
        "type" => "Feature",
        "geometry" => [
            "type" => "Point",
            "coordinates" => [ floatval($row['longitude']), floatval($row['latitude']) ]
        ],
        "properties" => $properties
    ];
}

echo json_encode([
    "type" => "FeatureCollection",
    "features" => $features
]);