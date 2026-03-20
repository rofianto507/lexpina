<?php
session_start();
$akses = $_SESSION["akses"] ?? "";
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
require_once("../config/configuration.php");
header("Content-Type: application/json");

$mode = $_GET['mode'] ?? 'provinsi';
$parent_id = intval($_GET['parent_id'] ?? 0);
$whereTujuan = "";
if($akses != 'POLDA') {
    $whereTujuan .= " AND k.tujuan='$akses'";
}
$kategoriFilter = [];
if (!empty($_GET['kategori'])) {
    if (is_array($_GET['kategori'])) $kategoriFilter = array_map('intval', $_GET['kategori']);
    else $kategoriFilter = array_map('intval', explode(',', $_GET['kategori']));
    $kategoriIn = implode(',', array_map('intval', $kategoriFilter));
}
$whereKategori = "";
if(!empty($kategoriIn)){
    $whereKategori = "AND ks.kategori_id IN ($kategoriIn)";
}

$subKategoriFilter = [];
if (!empty($_GET['sub_kategori'])) {
    if (is_array($_GET['sub_kategori'])) $subKategoriFilter = array_map('intval', $_GET['sub_kategori']);
    else $subKategoriFilter = array_map('intval', explode(',', $_GET['sub_kategori']));
}
$whereSubKategori = '';
if (count($subKategoriFilter) > 0) {
    $subKSK = implode(',', array_map('intval', $subKategoriFilter));
    $whereSubKategori = " AND k.sub_kategori_id IN ($subKSK) ";
    $whereKategori = ''; // Override filter kategori jika filter sub kategori dipilih, karena sub kategori sudah pasti punya kategori
}
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$whereTahun = "";
if($tahun) $whereTahun = "AND s.tahun = $tahun";

$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : 0;
$whereBulan = "";
if($bulan) $whereBulan = "AND MONTH(k.tanggal) = $bulan AND YEAR(k.tanggal) = $tahun";

$data = [];

if($mode == 'provinsi'){
    $q = $pdo->query("
       SELECT ks.nama as label, SUM(k.poin) as total
       FROM kriminal_sub_kategoris ks
           LEFT JOIN kriminals k ON k.sub_kategori_id = ks.id
           LEFT JOIN sumbers s ON k.sumber_id = s.id
       WHERE k.status=1 AND k.state!='SELESAI' $whereKategori $whereSubKategori $whereTahun $whereBulan $whereTujuan
       GROUP BY ks.id
        ORDER BY total DESC LIMIT 10
    ");
    while($row = $q->fetch(PDO::FETCH_ASSOC)) $data[] = $row;
}else if($mode == 'kabupaten' && $parent_id){
    $q = $pdo->prepare("
       SELECT ks.nama as label, SUM(k.poin) as total
       FROM kriminal_sub_kategoris ks
           LEFT JOIN kriminals k ON k.sub_kategori_id = ks.id
           LEFT JOIN sumbers s ON k.sumber_id = s.id
           LEFT JOIN desas d ON k.desa_id = d.id
           LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
           LEFT JOIN kabupatens kb ON kc.kabupaten_id = kb.id
       WHERE kb.id = ? AND k.status=1 AND k.state!='SELESAI' $whereKategori $whereSubKategori $whereTahun $whereBulan $whereTujuan
       GROUP BY ks.id
        ORDER BY total DESC LIMIT 10
    ");
    $q->execute([$parent_id]);
    while($row = $q->fetch(PDO::FETCH_ASSOC)) $data[] = $row;
}else if($mode == 'kecamatan' && $parent_id){
    $q = $pdo->prepare("    
       SELECT ks.nama as label, SUM(k.poin) as total
       FROM kriminal_sub_kategoris ks
           LEFT JOIN kriminals k ON k.sub_kategori_id = ks.id
           LEFT JOIN sumbers s ON k.sumber_id = s.id
           LEFT JOIN desas d ON k.desa_id = d.id
           LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
       WHERE kc.id = ? AND k.status=1 AND k.state!='SELESAI' $whereKategori $whereSubKategori $whereTahun $whereBulan $whereTujuan
       GROUP BY ks.id
        ORDER BY total DESC LIMIT 10    
    ");
    $q->execute([$parent_id]);
    while($row = $q->fetch(PDO::FETCH_ASSOC)) $data[] = $row;
}
// Tambah mode lain (kabupaten/kecamatan/desa) jika perlu

echo json_encode($data);