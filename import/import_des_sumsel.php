<?php
// ================================
// KONFIGURASI DATABASE
// ================================
$host = "localhost";
$db   = "petadigi_db";
$user = "root";
$pass = "";

$pdo = new PDO(
  "mysql:host=localhost;dbname=petadigi_db;charset=utf8mb4",
  "root",
  "",
  [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$data = json_decode(file_get_contents('desa_sumsel.geojson'), true);

// prepare lookup kecamatan
$getKec = $pdo->prepare(
  "SELECT id FROM kecamatans WHERE kode = ? LIMIT 1"
);

// prepare insert desa
$insert = $pdo->prepare("
  INSERT INTO desas
  (kode, nama, kecamatan_id, jenis, geom, lat, lng, created_at)
  VALUES
  (:kode, :nama, :kecamatan_id, :jenis, :geom, :lat, :lng, NOW())
");

foreach ($data['features'] as $f) {

  $prop = $f['properties'];

  // ----------------------------
  // KODE DESA
  // 16.71.02.1002 -> 1671021002
  // ----------------------------
  $kode_desa = str_replace('.', '', $prop['KODE_KD']);

  // ----------------------------
  // KODE KECAMATAN
  // 16.71.02 -> 167102
  // ----------------------------
  $kode_kec = str_replace('.', '', $prop['KODE_KEC']);

  // ----------------------------
  // NAMA DESA
  // ----------------------------
  $nama = $prop['KEL_DESA'];

  // ----------------------------
  // JENIS DESA
  // ----------------------------
  $jenis = strtoupper($prop['JENIS_KD']) === 'KELURAHAN'
    ? 'KELURAHAN'
    : 'DESA';

  // ----------------------------
  // CARI kecamatan_id
  // ----------------------------
  $getKec->execute([$kode_kec]);
  $kec_id = $getKec->fetchColumn();

  if (!$kec_id) {
    echo "⚠ Kecamatan tidak ditemukan: {$kode_kec}<br>";
    continue;
  }

  // ----------------------------
  // GEOMETRY
  // ----------------------------
  $g = $f['geometry'];

  if ($g['type'] === 'Polygon') {
    $g = [
      'type' => 'MultiPolygon',
      'coordinates' => [ $g['coordinates'] ]
    ];
  }

  $geom = json_encode($g);

  // ----------------------------
  // CENTROID
  // ----------------------------
  $lng = $prop['centroidX'] ?? null;
  $lat = $prop['centroidY'] ?? null;

  // ----------------------------
  // INSERT
  // ----------------------------
  try {
    $insert->execute([
      ':kode' => $kode_desa,
      ':nama' => $nama,
      ':kecamatan_id' => $kec_id,
      ':jenis' => $jenis,
      ':geom' => $geom,
      ':lat'  => $lat,
      ':lng'  => $lng
    ]);

    echo "✅ Insert desa: {$nama}<br>";

  } catch (Exception $e) {
    echo "❌ Gagal insert {$nama}: {$e->getMessage()}<br>";
  }
}

echo "<hr>🎉 Import desa selesai";
