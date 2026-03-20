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
if(!empty($_GET['kategori'])) {
    if(is_array($_GET['kategori'])) $kategoriFilter = array_map('intval', $_GET['kategori']);
    else $kategoriFilter = array_map('intval', explode(',', $_GET['kategori']));
}
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$filter = "";
$params = [];
if (isset($_GET['kabupaten_id'])) {
    $filter = " AND kc.kabupaten_id = ?";
    $params[] = intval($_GET['kabupaten_id']);
} elseif (isset($_GET['kecamatan_id'])) {
    $filter = " AND d.kecamatan_id = ?";
    $params[] = intval($_GET['kecamatan_id']);
} elseif (isset($_GET['desa_id'])) {
    $filter = " AND l.desa_id = ?";
    $params[] = intval($_GET['desa_id']);
}
if(count($kategoriFilter) > 0){
    $kategoriIn = implode(',', array_map('intval', $kategoriFilter));
    $filter .= " AND l.kategori_id IN ($kategoriIn)";
}
if($tahun) {
    $filter .= " AND s.tahun = $tahun";
}

$sql = "
    SELECT l.id, l.nama, l.penyebab, l.foto, l.latitude, l.longitude,
           d.nama as desa_nama, kc.nama as kec_nama, k.nama as kab_nama, l.kategori_id, kat.nama as kategori_nama, kat.warna as kategori_warna
    FROM bencanas l
      LEFT JOIN desas d ON l.desa_id = d.id
      LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
      LEFT JOIN kabupatens k ON kc.kabupaten_id = k.id
      LEFT JOIN bencana_kategoris kat ON l.kategori_id = kat.id
      LEFT JOIN sumbers s ON l.sumber_id = s.id
    WHERE l.latitude IS NOT NULL AND l.longitude IS NOT NULL
          AND l.status = 1
          $filter
";
$q = $pdo->prepare($sql);
$q->execute($params);

$features = [];
while($row = $q->fetch(PDO::FETCH_ASSOC)){
    $features[] = [
        'type' => 'Feature',
        'geometry' => [
            'type' => 'Point',
            'coordinates' => [(float)$row['longitude'], (float)$row['latitude']]
        ],
        'properties' => [
            'id' => $row['id'],
            'nama' => $row['nama'],
            'penyebab' => $row['penyebab'],
            'foto' => $row['foto'],
            'desa_nama' => $row['desa_nama'],
            'kec_nama' => $row['kec_nama'],
            'kab_nama' => $row['kab_nama'],
            'kategori_id' => $row['kategori_id'],
            'kategori_nama' => $row['kategori_nama'],
            'kategori_warna' => $row['kategori_warna']
        ]
    ];
}
echo json_encode(['type' => 'FeatureCollection', 'features' => $features]);