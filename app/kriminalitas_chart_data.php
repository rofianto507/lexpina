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
// --- Ambil filter kategori ---
$kategoriFilter = [];
if(!empty($_GET['kategori'])) {
    if (is_array($_GET['kategori'])) $kategoriFilter = array_map('intval', $_GET['kategori']);
    else $kategoriFilter = array_map('intval', explode(',', $_GET['kategori']));
    $kategoriIn = implode(',', array_map('intval', $kategoriFilter));
}
$whereKategori = "";
if(!empty($kategoriIn)) $whereKategori = "AND kriminals.sub_kategori_id IN (
    SELECT id FROM kriminal_sub_kategoris WHERE kategori_id IN ($kategoriIn)
)";
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

// --- Ambil filter tahun ---
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$whereTahun = '';
if ($tahun) $whereTahun = "AND s.tahun = $tahun";

$bulan = $_GET['bulan'] ?? '';
$whereBulan = '';
if (!empty($bulan)) {
    $dates = explode(' to ', $bulan);
    $startDate = isset($dates[0]) && !empty($dates[0]) ? DateTime::createFromFormat('d/m/Y', trim($dates[0])) : null;
    $endDate   = isset($dates[1]) && !empty($dates[1]) ? DateTime::createFromFormat('d/m/Y', trim($dates[1])) : null;
    if ($startDate && $endDate) {
        $startStr = $startDate->format('Y-m-d');
        $endStr   = $endDate->format('Y-m-d');
        $whereBulan = " AND kriminals.tanggal >= '$startStr' AND kriminals.tanggal <= '$endStr' ";
    } elseif ($startDate) {
        $startStr = $startDate->format('Y-m-d');
        $whereBulan = " AND kriminals.tanggal = '$startStr' ";
    }
}
$mode = $_GET['mode'] ?? 'kabupaten';
$parent_id = intval($_GET['parent_id'] ?? 0);
$data = [];
if($akses != 'POLDA') {
    $whereKategori .= " AND kriminals.tujuan='$akses'";
}
if($mode == 'kabupaten') {
    $q = $pdo->query("
  SELECT k.id, k.nama,
     (SELECT sum(kriminals.poin) FROM kriminals
      LEFT JOIN desas d ON kriminals.desa_id=d.id
      LEFT JOIN kecamatans kc ON d.kecamatan_id=kc.id
      LEFT JOIN sumbers s ON kriminals.sumber_id=s.id
      WHERE kc.kabupaten_id=k.id
        and kriminals.status=1
        and kriminals.state!='SELESAI'
        $whereKategori
        $whereSubKategori
        $whereTahun
        $whereBulan
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
     (SELECT sum(kriminals.poin) FROM kriminals
       LEFT JOIN desas d ON kriminals.desa_id=d.id
       LEFT JOIN sumbers s ON kriminals.sumber_id=s.id
       WHERE d.kecamatan_id=kc.id
         and kriminals.status=1
         and kriminals.state!='SELESAI'
         $whereKategori
         $whereSubKategori
         $whereTahun
          $whereBulan
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
    (SELECT sum(kriminals.poin) FROM kriminals
        LEFT JOIN sumbers s ON kriminals.sumber_id=s.id
        WHERE kriminals.desa_id = d.id
        and kriminals.status=1
        and kriminals.state!='SELESAI'
        $whereKategori
        $whereSubKategori
        $whereTahun
        $whereBulan
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