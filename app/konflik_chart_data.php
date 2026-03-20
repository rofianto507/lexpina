<?php
session_start();
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
require_once("../config/configuration.php");
header("Content-Type: application/json");

$mode = $_GET['mode'] ?? 'kabupaten';
$parent_id = intval($_GET['parent_id'] ?? 0);

$data = [];

if($mode == 'kabupaten') {
    $q = $pdo->query("SELECT k.id, k.nama,
      (SELECT COUNT(*) FROM konfliks ko
        LEFT JOIN desas d ON ko.desa_id = d.id
        LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
        WHERE kc.kabupaten_id = k.id) AS total
     FROM kabupatens k
     ORDER BY total DESC, k.nama");
    while($row = $q->fetch(PDO::FETCH_ASSOC)) {
      $data[] = [
          'id'=>$row['id'],
          'label'=>$row['nama'],
          'total'=>$row['total']
      ];
    }
} else if($mode == 'kecamatan' && $parent_id) {
    $q = $pdo->prepare("SELECT kc.id, kc.nama,
      (SELECT COUNT(*) FROM konfliks ko
        LEFT JOIN desas d ON ko.desa_id = d.id
        WHERE d.kecamatan_id = kc.id) AS total
     FROM kecamatans kc
     WHERE kc.kabupaten_id = ?
     ORDER BY total DESC, kc.nama");
    $q->execute([$parent_id]);
    while($row = $q->fetch(PDO::FETCH_ASSOC)) {
      $data[] = [
          'id'=>$row['id'],
          'label'=>$row['nama'],
          'total'=>$row['total']
      ];
    }
} else if($mode == 'desa' && $parent_id) {
    $q = $pdo->prepare("SELECT d.id, d.nama,
      (SELECT COUNT(*) FROM konfliks ko
        WHERE ko.desa_id = d.id) AS total
     FROM desas d
     WHERE d.kecamatan_id = ?
     ORDER BY total DESC, d.nama");
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