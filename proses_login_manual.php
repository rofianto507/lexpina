<?php
session_start();
require_once 'config/configuration.php';

header('Content-Type: application/json');

// Brute force protection
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = time();
}
if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_attempt']) < 300) {
    echo json_encode(['status' => 'error', 'message' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam 5 menit.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_email = trim($_POST['user_email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi input sederhana
    if (empty($user_email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Username/Email dan Password wajib diisi.']);
        exit;
    }
    if (!preg_match('/^[a-zA-Z0-9._@-]{3,100}$/', $user_email)) {
        echo json_encode(['status' => 'error', 'message' => 'Format username/email tidak valid.']);
        exit;
    }

    try {
        // Cari user berdasarkan username ATAU email
        $stmt = $pdo->prepare("SELECT id, username, password, nama, akses,foto, status FROM users WHERE username = ? OR username = ? LIMIT 1");
        $stmt->execute([$user_email, $user_email]);
        $user = $stmt->fetch();

        if ($user) {
            // Verifikasi Password (Pastikan password di DB sudah di-hash menggunakan password_hash)
            if (password_verify($password, $user['password'])) {
                // Cek Status Akun
                if ($user['status'] == 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Akun Anda sedang diblokir atau belum aktif.']);
                    exit;
                }
                // Reset brute force counter
                $_SESSION['login_attempts'] = 0;
                // Regenerate session ID
                session_regenerate_id(true);
                // Set Session
                $_SESSION['id'] = $user['id'];
                $_SESSION['user_id'] = $user['id']; // Menyesuaikan pengecekan di checkout
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['user_nama'] = $user['nama'];
                $_SESSION['akses'] = $user['akses'];
                $_SESSION['foto'] = 'public/img/user/' . ($user['foto'] ?? 'avatar.png'); // Pastikan kolom foto ada di DB, jika tidak gunakan default
                // Logging IP dan User Agent (opsional, bisa disimpan ke DB)
                $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                echo json_encode(['status' => 'success']);
            } else {
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt'] = time();
                echo json_encode(['status' => 'error', 'message' => 'Username/email atau password salah.']);
            }
        } else {
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt'] = time();
            echo json_encode(['status' => 'error', 'message' => 'Username/email atau password salah.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
}