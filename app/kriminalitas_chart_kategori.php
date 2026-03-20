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
// Kategori
$kategoriFilter = [];
if(!empty($_GET['kategori'])) {
    if(is_array($_GET['kategori'])) $kategoriFilter = array_map('intval', $_GET['kategori']);
    else $kategoriFilter = array_map('intval', explode(',', $_GET['kategori']));
    $kategoriIn = implode(',', array_map('intval', $kategoriFilter));
}
$whereKategori = "";
if(!empty($kategoriIn)) $whereKategori = "AND km.sub_kategori_id IN (
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
    $whereSubKategori = " AND km.sub_kategori_id IN ($subKSK) ";
    $whereKategori = ''; // Override filter kategori jika filter sub kategori dipilih, karena sub kategori sudah pasti punya kategori
}

if($akses != 'POLDA') {
    $whereKategori .= " AND km.tujuan='$akses'";
}
// Tahun
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$whereTahun = "";
if ($tahun) $whereTahun = "AND s.tahun = $tahun";

$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : 0;
$whereBulan = '';
if ($bulan) {
    $whereBulan = "AND MONTH(km.tanggal) = $bulan AND YEAR(km.tanggal) = $tahun";
}

$mode = $_GET['mode'] ?? 'kabupaten';
$parent_id = intval($_GET['parent_id'] ?? 0);
$data = [];

if ($mode == 'kabupaten') {
    $q = $pdo->query("
        SELECT kk.nama, sum(km.poin) as total
        FROM kriminal_kategoris kk
        LEFT JOIN kriminal_sub_kategoris kks ON kks.kategori_id = kk.id
        LEFT JOIN kriminals km ON km.sub_kategori_id = kks.id
        LEFT JOIN sumbers s ON km.sumber_id = s.id
        LEFT JOIN desas d ON km.desa_id = d.id
        LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
        LEFT JOIN kabupatens k ON kc.kabupaten_id = k.id
        WHERE km.status=1 AND km.state!='SELESAI' $whereKategori $whereSubKategori $whereTahun $whereBulan
        GROUP BY kk.id
        ORDER BY kk.nama
    ");
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $data[] = ['label' => $row['nama'], 'total' => $row['total']];
    }
} else if ($mode == 'kecamatan' && $parent_id) {
    $q = $pdo->prepare("
        SELECT kk.nama, sum(km.poin) as total
        FROM kriminal_kategoris kk
        LEFT JOIN kriminal_sub_kategoris kks ON kks.kategori_id = kk.id
        LEFT JOIN kriminals km ON km.sub_kategori_id = kks.id
        LEFT JOIN sumbers s ON km.sumber_id = s.id
        LEFT JOIN desas d ON km.desa_id = d.id
        LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
        LEFT JOIN kabupatens k ON kc.kabupaten_id = k.id
        WHERE kc.kabupaten_id = ? AND km.status=1 AND km.state!='SELESAI' $whereKategori $whereSubKategori $whereTahun $whereBulan
        GROUP BY kk.id
        ORDER BY kk.nama
    ");
    $q->execute([$parent_id]);
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $data[] = ['label' => $row['nama'], 'total' => $row['total']];
    }
} else if ($mode == 'desa' && $parent_id) {
    $q = $pdo->prepare("
        SELECT kk.nama, sum(km.poin) as total
        FROM kriminal_kategoris kk
        LEFT JOIN kriminal_sub_kategoris kks ON kks.kategori_id = kk.id
        LEFT JOIN kriminals km ON km.sub_kategori_id = kks.id
        LEFT JOIN sumbers s ON km.sumber_id = s.id
        LEFT JOIN desas d ON km.desa_id = d.id
        LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
        LEFT JOIN kabupatens k ON kc.kabupaten_id = k.id
        WHERE d.kecamatan_id = ? AND km.status=1 AND km.state!='SELESAI' $whereKategori $whereSubKategori $whereTahun $whereBulan
        GROUP BY kk.id
        ORDER BY kk.nama
    ");
    $q->execute([$parent_id]);
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        $data[] = ['label' => $row['nama'], 'total' => $row['total']];
    }
}
echo json_encode($data);