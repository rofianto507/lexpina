<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/configuration.php';

// Pastikan hanya bisa diakses via metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Cek ulang apakah user benar-benar login
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $judul   = isset($_POST['judul']) ? trim($_POST['judul']) : '';
    $konten  = isset($_POST['konten']) ? trim($_POST['konten']) : '';

    // Validasi data tidak boleh kosong
    if (!empty($judul) && !empty($konten)) {
        
        try {
            // Simpan ke database
            $stmt = $pdo->prepare("INSERT INTO sarans (user_id, judul, konten, status) VALUES (:user_id, :judul, :konten, 0)");
            
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':judul', htmlspecialchars($judul), PDO::PARAM_STR);
            $stmt->bindValue(':konten', htmlspecialchars($konten), PDO::PARAM_STR);
            
            $stmt->execute();

            // Berhasil! Kembalikan ke form dengan parameter status=sukses
            header("Location: saran.php?status=sukses");
            exit();

        } catch (PDOException $e) {
            die("Gagal mengirim saran: " . $e->getMessage());
        }

    } else {
        // Jika ada field kosong, paksa kembali ke form
        header("Location: saran.php");
        exit();
    }

} else {
    // Tendang jika diakses langsung lewat URL
    header("Location: index.php");
    exit();
}
?>