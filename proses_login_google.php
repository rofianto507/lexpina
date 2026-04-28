<?php
session_start();
require_once 'config/configuration.php';

// Set header agar output dibaca sebagai JSON oleh JavaScript
header('Content-Type: application/json');

// Tangkap data JSON dari proses Fetch JavaScript
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (isset($data['google_id']) && isset($data['email'])) {
    $google_id = $data['google_id'];
    $nama      = $data['nama'];
    $email     = $data['email'];
    $foto      = $data['foto'];

    try {
        // 1. Cek apakah user sudah ada di database (berdasarkan google_id ATAU email)
        // Kita cek email juga agar jika user pernah daftar manual, akunnya bisa tersambung otomatis
        $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR username = ?");
        $stmt->execute([$google_id, $email]);
        $user = $stmt->fetch();

        if ($user) {
            // SKENARIO A: USER SUDAH TERDAFTAR (Proses Login)
            
            // Jika user pernah daftar manual (google_id masih kosong), kita update google_id dan fotonya
            if (empty($user['google_id'])) {
                $update = $pdo->prepare("UPDATE users SET google_id = ?, foto = ? WHERE id = ?");
                $update->execute([$google_id, $foto, $user['id']]);
            }

            // Set Session Login
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['akses']     = $user['akses'];
            $_SESSION['foto']      = $foto; // Simpan foto ke session

            echo json_encode(['status' => 'success', 'message' => 'Login berhasil']);

        } else {
            // SKENARIO B: USER BELUM TERDAFTAR (Proses Auto-Register)
            
            $akses = 'PENGGUNA';
            $status = 1; // 1 = Aktif (Bisa langsung login)
            
            $insert = $pdo->prepare("INSERT INTO users (username, nama, akses, foto, status, google_id) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->execute([$email, $nama, $akses, $foto, $status, $google_id]);

            // Set Session Login untuk User Baru
            $_SESSION['user_id']   = $pdo->lastInsertId();
            $_SESSION['user_nama'] = $nama;
            $_SESSION['akses']     = $akses;
            $_SESSION['foto']      = $foto;

            echo json_encode(['status' => 'success', 'message' => 'Registrasi dan Login berhasil']);
        }

    } catch (PDOException $e) {
        // Tangkap error database
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Data dari Google tidak lengkap']);
}
?>