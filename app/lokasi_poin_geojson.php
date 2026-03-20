<?php
session_start();
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
require_once("../config/configuration.php");
header("Content-Type: application/json");

// Misal ingin semua lokasi, atau bisa ditambah filter (kategori_id, status, dsb)
$sql = "
    SELECT l.id, l.nama, l.alamat, l.hp, l.foto, l.keterangan, 
           l.latitude, l.longitude,
           lk.nama AS kategori_nama, lk.warna AS kategori_warna
    FROM lokasis l
    LEFT JOIN lokasi_kategoris lk ON l.kategori_id = lk.id
    WHERE l.latitude IS NOT NULL AND l.longitude IS NOT NULL AND l.status = 1
";
$q = $pdo->query($sql);

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
            'kategori_nama' => $row['kategori_nama'],
            'kategori_warna'=> $row['kategori_warna']
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