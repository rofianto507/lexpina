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
if (!empty($_GET['kategori'])) {
    if (is_array($_GET['kategori'])) $kategoriFilter = array_map('intval', $_GET['kategori']);
    else $kategoriFilter = array_map('intval', explode(',', $_GET['kategori']));
}
$whereKategori = "";
if(count($kategoriFilter)) {
    $kategoriIn = implode(',', array_map('intval', $kategoriFilter));
    $whereKategori = " AND bencanas.kategori_id IN ($kategoriIn)";
}
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$whereTahun = "";
if ($tahun) $whereTahun = " AND s.tahun = $tahun";
$mode = $_GET['mode'] ?? 'kabupaten';
$parent_id = intval($_GET['parent_id'] ?? 0);
$data = [];

if($mode == 'kabupaten') {
   $q = $pdo->query("
    SELECT k.id, k.nama,
      (SELECT COUNT(*) FROM bencanas
        LEFT JOIN desas d ON bencanas.desa_id=d.id
        LEFT JOIN kecamatans kc ON d.kecamatan_id=kc.id
        LEFT JOIN sumbers s ON bencanas.sumber_id = s.id
        WHERE kc.kabupaten_id=k.id AND bencanas.status=1
          $whereKategori $whereTahun
      ) as total
    FROM kabupatens k
    ORDER BY k.nama
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
      (SELECT COUNT(*) FROM bencanas
        LEFT JOIN desas d ON bencanas.desa_id=d.id
        LEFT JOIN sumbers s ON bencanas.sumber_id = s.id
        WHERE d.kecamatan_id=kc.id AND bencanas.status=1
          $whereKategori $whereTahun
      ) as total
    FROM kecamatans kc
    WHERE kc.kabupaten_id = ?
    ORDER BY kc.nama
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
      (SELECT COUNT(*) FROM bencanas
      LEFT JOIN sumbers s ON bencanas.sumber_id = s.id
      WHERE bencanas.desa_id = d.id AND bencanas.status=1
        $whereKategori $whereTahun
      ) as total
    FROM desas d
    WHERE d.kecamatan_id = ?
    ORDER BY d.nama
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