<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *"); // agar request bisa dari web PWA (atau sesuaikan origin)
header("Access-Control-Allow-Headers: *");

include("../config/configuration.php");
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Metode tidak diizinkan."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$username = trim($data["username"] ?? '');
$password = trim($data["password"] ?? '');

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(["error" => "Parameter kosong."]);
    exit;
}

// Cari user by username
$stmt = $pdo->prepare(' SELECT a.id, a.username, a.password, a.nama, a.polres_id, a.polsek_id, a.foto,
         p.nama as polres_nama, s.nama as polsek_nama
  FROM anggotas a
  LEFT JOIN polress p ON a.polres_id = p.id
  LEFT JOIN polseks s ON a.polsek_id = s.id
  WHERE a.username=? AND a.status=1 LIMIT 1');
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
    // Sukses login
    echo json_encode([
        "success" => true,
        "user" => [
            "id" => $user['id'],
            "username" => $user['username'],
            "nama" => $user['nama'],
            "polres_nama" => $user['polres_nama']??'-',
            "polsek_nama" => $user['polsek_nama']??'-',
            "foto" => $user['foto']??'user.png'
        ]
    ]);
} else {
    // Salah username/password
    http_response_code(401);
    echo json_encode(["error" => "Username atau password salah."]);
}