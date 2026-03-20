<?php
session_start();
$akses = $_SESSION["akses"] ?? "";
$polres_id = $_SESSION["polres_id"] ?? 0;
$polsek_id = $_SESSION["polsek_id"] ?? 0;
$get_polres_id = isset($_GET['polres_id']) && $_GET['polres_id'] !== '' ? intval($_GET['polres_id']) : $polres_id;
$get_polsek_id = isset($_GET['polsek_id']) && $_GET['polsek_id'] !== '' ? intval($_GET['polsek_id']) : $polsek_id;

if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
require_once("../config/configuration.php");
if ($akses == 'POLDA') {
    if ($get_polsek_id) {
        $filter = " AND kecamatans.polsek_id = $get_polsek_id ";
    } elseif ($get_polres_id) {
        $filter = " AND kabupatens.polres_id = $get_polres_id ";
    } else {
        $filter = "";
    }
} elseif ($akses == 'POLRES') {
    // Hanya boleh filter di polres kamu & polsek bawahannya
    if ($get_polsek_id) {
        $filter = " AND kecamatans.polsek_id = $get_polsek_id AND kabupatens.polres_id = $polres_id ";
    } else {
        $filter = " AND kabupatens.polres_id = $polres_id ";
    }
} elseif ($akses == 'POLSEK') {
    $filter = " AND kecamatans.polsek_id = $polsek_id ";
}  
$sql_total_state = "SELECT 
  CASE 
    WHEN lalins.state='PROSES' THEN 'PROSES'
    WHEN lalins.state='SELESAI' THEN 'SELESAI'
  END AS state,
  count(lalins.id) AS total
FROM lalins
left join desas on lalins.desa_id = desas.id
left join kecamatans on desas.kecamatan_id = kecamatans.id
left join kabupatens on kecamatans.kabupaten_id = kabupatens.id
WHERE lalins.status = 1 $filter
GROUP BY lalins.state
ORDER BY lalins.state";
$q_total_state = $pdo->query($sql_total_state);
$total_proses = 0;
$total_selesai = 0;
while($row = $q_total_state->fetch(PDO::FETCH_ASSOC)) {
    if ($row['state'] === 'PROSES') {
        $total_proses = $row['total'];
    } elseif ($row['state'] === 'SELESAI') {
        $total_selesai = $row['total'];
    }
}
echo json_encode([
    "total_proses" => (int)($total_proses ?: 0),
    "total_selesai" => (int)($total_selesai ?: 0),
    "qry" => $sql_total_state
]);