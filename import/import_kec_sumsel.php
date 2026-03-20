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

// ======================================
// LOAD GEOJSON
// ======================================
$file = 'kecamatan_sumsel.geojson';

if (!file_exists($file)) {
    die("❌ File GeoJSON tidak ditemukan");
}

$data = json_decode(file_get_contents($file), true);
if (!$data || !isset($data['features'])) {
    die("❌ GeoJSON tidak valid");
}

// ======================================
// PREPARE STATEMENT
// ======================================
$sql = "
    INSERT INTO kecamatans
    (kode, nama, kabupaten_id, geom, lat, lng, created_at)
    VALUES
    (:kode, :nama, :kabupaten_id, :geom, :lat, :lng, NOW())
";

$stmt = $pdo->prepare($sql);

// ======================================
// LOOP FEATURES
// ======================================
foreach ($data['features'] as $f) {

    // ----------------------------
    // KODE KECAMATAN
    // ----------------------------
    // 16.10.13 -> 161013
    $kode_kec = str_replace('.', '', $f['properties']['KODE_KEC']);

    // ----------------------------
    // NAMA KECAMATAN
    // ----------------------------
    $nama = $f['properties']['KECAMATAN'];

    // ----------------------------
    // KODE KABUPATEN
    // ----------------------------
    // 16.10 -> 1610
    $kode_kab = str_replace('.', '', $f['properties']['KODE_KK']);

    // ----------------------------
    // CARI kabupaten_id
    // ----------------------------
    $kabupaten_id = $pdo->prepare(
        "SELECT id FROM kabupatens WHERE kode = ? LIMIT 1"
    );
    $kabupaten_id->execute([$kode_kab]);
    $kab_id = $kabupaten_id->fetchColumn();

    if (!$kab_id) {
        echo "⚠ Kabupaten tidak ditemukan: {$kode_kab}<br>";
        continue;
    }

    // ----------------------------
    // GEOMETRY
    // ----------------------------
    $g = $f['geometry'];

    // NORMALISASI KE MULTIPOLYGON
    if ($g['type'] === 'Polygon') {
        $g = [
            'type' => 'MultiPolygon',
            'coordinates' => [ $g['coordinates'] ]
        ];
    }

    $geom = json_encode($g);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ Geometry error kecamatan {$kode_kec}<br>";
        continue;
    }

    // ----------------------------
    // CENTROID
    // ----------------------------
    $lng = $f['properties']['centroidX'] ?? null;
    $lat = $f['properties']['centroidY'] ?? null;

    // ----------------------------
    // INSERT DATA
    // ----------------------------
    try {
        $stmt->execute([
            ':kode' => $kode_kec,
            ':nama' => $nama,
            ':kabupaten_id' => $kab_id,
            ':geom' => $geom,
            ':lat'  => $lat,
            ':lng'  => $lng
        ]);

        echo "✅ Insert kecamatan: {$nama}<br>";

    } catch (Exception $e) {
        echo "❌ Gagal insert {$nama}: " . $e->getMessage() . "<br>";
    }
}

echo "<hr>🎉 Import kecamatan selesai";
