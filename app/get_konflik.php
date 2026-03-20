<?php
require_once("../config/configuration.php");
header('Content-Type: application/json');
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$q = $pdo->prepare("SELECT k.*,
  ke.kabupaten_id, 
  ke.id as kecamatan_id,
  d.kecamatan_id as desa_kecamatan_id
FROM konfliks k
LEFT JOIN desas d ON k.desa_id = d.id
LEFT JOIN kecamatans ke ON d.kecamatan_id = ke.id
WHERE k.id=?");
$q->execute([$id]);
$data = $q->fetch(PDO::FETCH_ASSOC);

if($data){
    // dapatkan list kecamatan untuk kabupaten terkait
    $stmt1 = $pdo->prepare("SELECT id, nama, kode FROM kecamatans WHERE kabupaten_id=? AND status=1 ORDER BY nama");
    $stmt1->execute([$data['kabupaten_id']]);
    $data['kecamatans'] = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    // dapatkan list desa untuk kecamatan terkait
    $stmt2 = $pdo->prepare("SELECT id, nama, kode FROM desas WHERE kecamatan_id=? AND status=1 ORDER BY nama");
    $stmt2->execute([$data['kecamatan_id']]);
    $data['desas'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
}
echo json_encode($data);