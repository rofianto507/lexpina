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
        $filter = " AND kriminals.polsek_id = $get_polsek_id ";
    } elseif ($get_polres_id) {
        $filter = " AND kriminals.polres_id = $get_polres_id ";
    } else {
        $filter = "";
    }
} elseif ($akses == 'POLRES') {
    // Hanya boleh filter di polres kamu & polsek bawahannya
    if ($get_polsek_id) {
        $filter = " AND kriminals.polsek_id = $get_polsek_id AND kriminals.polres_id = $polres_id ";
    } else {
        $filter = " AND kriminals.polres_id = $polres_id ";
    }
} elseif ($akses == 'POLSEK') {
    $filter = " AND kriminals.polsek_id = $polsek_id ";
} else {
    $filter = " AND kriminals.tujuan='$akses'";
}
$sql_total_lp = "SELECT 
  CASE 
    WHEN UPPER(REPLACE(no_lp, ' ', '')) LIKE '%LP/B%/%' THEN 'LP B'
    WHEN UPPER(REPLACE(no_lp, ' ', '')) LIKE '%LP/A%/%' THEN 'LP A'
    ELSE 'Lainnya'
  END AS jenis_lp,
  SUM(poin) AS total
FROM kriminals
WHERE STATUS = 1 $filter
GROUP BY jenis_lp
ORDER BY jenis_lp";
$q_total_lp = $pdo->query($sql_total_lp);
$total_lpa = 0;
$total_lpb = 0;
$total_lplainnya = 0;
while($row = $q_total_lp->fetch(PDO::FETCH_ASSOC)) {
    if ($row['jenis_lp'] === 'LP A') {
        $total_lpa = $row['total'];
    } elseif ($row['jenis_lp'] === 'LP B') {
        $total_lpb = $row['total'];
    } else {
        $total_lplainnya += $row['total']; // Akumulasi untuk jenis lainnya
    }
}
echo json_encode([
    "total_lp_a" => (int)($total_lpa?:0),
    "total_lp_b" => (int)($total_lpb?:0),
    "total_lp_plainnya" => (int)($total_lplainnya?:0) ,
    "qry"=>$sql_total_lp
]);