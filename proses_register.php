<?php
require_once 'config/configuration.php';

// Pastikan merespons dengan JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // 1. Validasi Input Kosong
    if (empty($nama) || empty($email) || empty($password) || empty($password_confirm)) {
        echo json_encode(['status' => 'error', 'message' => 'Semua kolom wajib diisi.']);
        exit;
    }

    // 2. Validasi Format Email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Format alamat email tidak valid.']);
        exit;
    }

    // 3. Validasi Keamanan Password
    if (strlen($password) < 6) {
        echo json_encode(['status' => 'error', 'message' => 'Password harus memiliki minimal 6 karakter.']);
        exit;
    }
    if ($password !== $password_confirm) {
        echo json_encode(['status' => 'error', 'message' => 'Konfirmasi password tidak cocok dengan password yang diketik.']);
        exit;
    }

    try {
        // 4. Cek apakah Email/Username sudah ada di database
        $stmt_cek = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_cek->execute([$email]);
        
        if ($stmt_cek->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Email ini sudah terdaftar. Silakan gunakan email lain atau Sign In.']);
            exit;
        }

        // 5. Hash Password & Insert ke Tabel Users
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Catatan: akses default adalah 'PENGGUNA', dan status = 1 (aktif)
        $stmt_insert = $pdo->prepare("INSERT INTO users (nama, username, password, akses, status) VALUES (?, ?, ?, 'PENGGUNA', 1)");
        $stmt_insert->execute([$nama, $email, $hashed_password]);

        // Berikan respons sukses
        echo json_encode(['status' => 'success']);

    } catch (PDOException $e) {
        // Jangan tampilkan pesan error detail database ke end-user di production
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kendala pada server saat menyimpan data.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid.']);
}