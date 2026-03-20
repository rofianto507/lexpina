<?php
// ================================
// KONFIGURASI DATABASE
// ================================
$host = "localhost";
$db   = "petadigi_db";
$user = "root";
$pass = "";

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// ================================
// LOAD FILE GEOJSON
// ================================
$file = 'kabupaten_sumsel.geojson';

if (!file_exists($file)) {
    die("File GeoJSON tidak ditemukan");
}

$data = json_decode(file_get_contents($file), true);

if (!$data || !isset($data['features'])) {
    die("GeoJSON tidak valid");
}

// ================================
// PREPARE STATEMENT
// ================================
$sql = "
    UPDATE kabupatens
    SET 
        geom = :geom,
        updated_at = NOW()
    WHERE kode = :kode
";

$stmt = $pdo->prepare($sql);

// ================================
// LOOP FEATURES
// ================================
foreach ($data['features'] as $f) {

    // --- AMBIL KODE KABUPATEN ---
    // sesuaikan field ini dengan GeoJSON kamu
    $kode = str_replace('.', '', $f['properties']['KODE_KK']); 
    // contoh: 16.08 -> 1608

    // --- GEOMETRY ---
    $g = $f['geometry'];

    // NORMALISASI KE MULTIPOLYGON
    if ($g['type'] === 'Polygon') {
        $g = [
            'type' => 'MultiPolygon',
            'coordinates' => [ $g['coordinates'] ]
        ];
    }

    // VALIDASI JSON
    $geom = json_encode($g);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ Geometry error kode $kode <br>";
        continue;
    }

    // --- EXECUTE UPDATE ---
    $stmt->execute([
        ':geom' => $geom,
        ':kode' => $kode
    ]);

    echo "✅ Updated kabupaten kode {$kode}<br>";
}

echo "<hr>🎉 Import selesai";
