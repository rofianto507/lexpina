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
$kategoriFilter = [];
if(!empty($_GET['kategori'])) {
    if (is_array($_GET['kategori'])) $kategoriFilter = array_map('intval', $_GET['kategori']);
    else $kategoriFilter = array_map('intval', explode(',', $_GET['kategori']));
    $kategoriIn = implode(',', array_map('intval', $kategoriFilter));
}
$whereKategori = "";
if(!empty($kategoriIn)){
    $whereKategori = "AND kamtibmass.kategori_id IN ($kategoriIn)";
}
if($akses != 'POLDA') {
    $whereKategori .= " AND kamtibmass.tujuan='$akses'";
}
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$whereTahun = '';
if($tahun) $whereTahun = "AND s.tahun = $tahun";
$bulan = $_GET['bulan'] ?? '';
$whereBulan = '';
if (!empty($bulan)) {
    $dates = explode(' to ', $bulan);
    $startDate = isset($dates[0]) && !empty($dates[0]) ? DateTime::createFromFormat('d/m/Y', trim($dates[0])) : null;
    $endDate   = isset($dates[1]) && !empty($dates[1]) ? DateTime::createFromFormat('d/m/Y', trim($dates[1])) : null;
    if ($startDate && $endDate) {
        $startStr = $startDate->format('Y-m-d');
        $endStr   = $endDate->format('Y-m-d');
        $whereBulan = " AND kamtibmass.tanggal >= '$startStr' AND kamtibmass.tanggal <= '$endStr' ";
    } elseif ($startDate) {
        $startStr = $startDate->format('Y-m-d');
        $whereBulan = " AND kamtibmass.tanggal = '$startStr' ";
    }
}
$mode = $_GET['mode'] ?? 'kabupaten';
$parent_id = intval($_GET['parent_id'] ?? 0);
$data = [];

if($mode == 'kabupaten') {
   $q = $pdo->query("
    SELECT k.id, k.nama,
      (SELECT COUNT(*) FROM kamtibmass
        LEFT JOIN desas d ON kamtibmass.desa_id=d.id
        LEFT JOIN kecamatans kc ON d.kecamatan_id=kc.id
        LEFT JOIN sumbers s ON kamtibmass.sumber_id=s.id
        WHERE kc.kabupaten_id=k.id and kamtibmass.state!='SELESAI' and kamtibmass.status=1
          $whereKategori $whereTahun $whereBulan
      ) as total
    FROM kabupatens k
    ORDER BY total DESC , k.nama
  ");
    while($row = $q->fetch(PDO::FETCH_ASSOC)) {
      $data[] = [
        'id'=>$row['id'],
        'label'=>$row['nama'],
        'total'=>$row['total']
      ];
    }
} else if($mode == 'kecamatan' && $parent_id) {
    $q = $pdo->prepare("
      SELECT kc.id, kc.nama,
        (SELECT COUNT(*) FROM kamtibmass
          LEFT JOIN desas d ON kamtibmass.desa_id=d.id
          LEFT JOIN sumbers s ON kamtibmass.sumber_id=s.id
          WHERE d.kecamatan_id=kc.id and kamtibmass.state!='SELESAI' and kamtibmass.status=1
            $whereKategori $whereTahun $whereBulan
        ) as total
      FROM kecamatans kc
      WHERE kc.kabupaten_id = ?
      ORDER BY total DESC, kc.nama
    ");
    $q->execute([$parent_id]);
    while($row = $q->fetch(PDO::FETCH_ASSOC)) {
      $data[] = [
        'id'=>$row['id'],
        'label'=>$row['nama'],
        'total'=>$row['total']
      ];
    }
} else if($mode == 'desa' && $parent_id) {
    $q = $pdo->prepare("
      SELECT d.id, d.nama,
        (SELECT COUNT(*) FROM kamtibmass
          LEFT JOIN sumbers s ON kamtibmass.sumber_id=s.id
          WHERE kamtibmass.desa_id = d.id and kamtibmass.state!='SELESAI' and kamtibmass.status=1
            $whereKategori $whereTahun $whereBulan
        ) as total
      FROM desas d
      WHERE d.kecamatan_id = ?
      ORDER BY total DESC, d.nama
    ");
    $q->execute([$parent_id]);
    while($row = $q->fetch(PDO::FETCH_ASSOC)) {
      $data[] = [
        'id'=>$row['id'],
        'label'=>$row['nama'],
        'total'=>$row['total']
      ];
    }
}
echo json_encode($data);