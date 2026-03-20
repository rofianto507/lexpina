<?php
require_once("../config/configuration.php");
header("Content-Type: application/json");

// Ambil semua kabupaten dengan status=1
$query = $pdo->query("SELECT id, nama, kode, geom FROM kabupatens WHERE status=1");

$features = [];
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    // Pastikan kolom geom sudah terisi dan valid JSON
    $geom = json_decode($row['geom'], true); 
    if ($geom) {
        $features[] = [
            "type" => "Feature",
            "geometry" => $geom,
            "properties" => [
                "id" => $row['id'],
                "nama" => $row['nama'],
                "kode" => $row['kode']
            ]
        ];
    }
}

$geojson = [
    "type" => "FeatureCollection",
    "features" => $features
];

echo json_encode($geojson);