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
// Misal ingin semua lokasi, atau bisa ditambah filter (kategori_id, status, dsb)
$sql = "
    SELECT l.*,
           lk.nama AS kategori_nama, lk.warna AS kategori_warna,lk.icon,
           d.nama AS desa_nama, k.nama AS kecamatan_nama, kb.nama AS kabupaten_nama
    FROM lokasis l
    LEFT JOIN lokasi_kategoris lk ON l.kategori_id = lk.id
    LEFT JOIN desas d ON l.desa_id=d.id
    LEFT JOIN kecamatans k ON d.kecamatan_id = k.id
    LEFT JOIN kabupatens kb ON k.kabupaten_id = kb.id
    WHERE l.latitude IS NOT NULL AND l.longitude IS NOT NULL AND l.status = 1
    $filter
";
$q = $pdo->query($sql);
$q->execute($params);
$features = [];
while($row = $q->fetch(PDO::FETCH_ASSOC)) {
    $features[] = [
        'type' => 'Feature',
        'geometry' => [
            'type' => 'Point',
            'coordinates' => [(float)$row['longitude'], (float)$row['latitude']]
        ],
        'properties' => [
            'id'            => $row['id'],
            'nama'          => $row['nama'],
            'alamat'        => $row['alamat'],
            'hp'            => $row['hp'],
            'foto'          => $row['foto'],
            'keterangan'    => $row['keterangan'],
            'kategori_id'   => $row['kategori_id'],
            'kategori_nama' => $row['kategori_nama'],
            'kategori_warna'=> $row['kategori_warna'],
            'kategori_icon' => $row['icon'],
            'desa_nama'     => $row['desa_nama'],
            'kecamatan_nama'=> $row['kecamatan_nama'],
            'kabupaten_nama'=> $row['kabupaten_nama']
        ]
    ];
}
$sumberArr = [];
  $stmtSumber = $pdo->prepare("SELECT DISTINCT s.nama FROM lokasis k LEFT JOIN sumbers s ON k.sumber_id=s.id WHERE k.status=1 and k.sumber_id is not null");
  $stmtSumber->execute();
  while($src = $stmtSumber->fetch(PDO::FETCH_ASSOC)) {
    if($src['nama']) $sumberArr[] = $src['nama'];
  }
echo json_encode(['type' => 'FeatureCollection', 'features' => $features, 'sumber_dokumen' => $sumberArr]);