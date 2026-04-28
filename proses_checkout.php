<?php
// Pastikan sesi hanya dimulai jika belum ada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi: Hanya yang sudah login dan mengirim data POST yang boleh akses
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: index.php");
    exit();
}

require_once 'config/configuration.php';

// Tangkap data dari form
$user_id        = $_SESSION['user_id'];
$produk_id      = $_POST['id_produk'];
$total_transfer = $_POST['total_transfer'];
$nama_pengirim  = htmlspecialchars($_POST['nama_pengirim']); // Mencegah serangan XSS

// Proses Upload Bukti Transfer
$nama_file_asli = $_FILES['bukti_transfer']['name'];
$ukuran_file    = $_FILES['bukti_transfer']['size'];
$error_file     = $_FILES['bukti_transfer']['error'];
$tmp_name       = $_FILES['bukti_transfer']['tmp_name'];

// Ekstensi file yang diperbolehkan
$ekstensi_diperbolehkan = ['jpg', 'jpeg', 'png', 'pdf'];
$ekstensi_file          = explode('.', $nama_file_asli);
$ekstensi_file          = strtolower(end($ekstensi_file));

// Pengecekan 1: Apakah ada file yang diupload?
if ($error_file === 4) {
    die("<script>alert('Pilih gambar bukti transfer terlebih dahulu!'); window.history.back();</script>");
}

// Pengecekan 2: Apakah ekstensinya sesuai?
if (!in_array($ekstensi_file, $ekstensi_diperbolehkan)) {
    die("<script>alert('Ekstensi file tidak valid! Harap unggah JPG, PNG, atau PDF.'); window.history.back();</script>");
}

// Pengecekan 3: Apakah ukurannya terlalu besar? (Batas 5MB)
if ($ukuran_file > 5000000) {
    die("<script>alert('Ukuran file terlalu besar! Maksimal 5 MB.'); window.history.back();</script>");
}

// Rename nama file agar unik (Contoh: BUKTI_1_1694200000.jpg)
// Ini mencegah file tertimpa jika ada 2 user mengupload nama file yang sama (misal: screenshot.jpg)
$nama_file_baru = 'BUKTI_' . $user_id . '_' . time() . '.' . $ekstensi_file;
$direktori_tujuan = 'public/upload/bukti/' . $nama_file_baru;

try {
    // Pindahkan file dari memori sementara ke folder tujuan
    if (move_uploaded_file($tmp_name, $direktori_tujuan)) {
        
        // Jika file berhasil pindah, masukkan data ke tabel transaksis
        $stmt = $pdo->prepare("INSERT INTO transaksis (user_id, produk_id, total_transfer, nama_pengirim, bukti_transfer, status) VALUES (?, ?, ?, ?, ?, 'PENDING')");
        $stmt->execute([$user_id, $produk_id, $total_transfer, $nama_pengirim, $nama_file_baru]);

        // Berhasil! Lempar ke halaman sukses (atau profil)
        echo "<script>
                alert('Berhasil! Bukti pembayaran Anda sedang kami proses.');
                window.location.href = 'profil.php?tab=transaksi'; 
              </script>";
        exit();

    } else {
        die("<script>alert('Gagal mengunggah file. Pastikan folder public/upload/bukti/ sudah ada.'); window.history.back();</script>");
    }

} catch (PDOException $e) {
    die("Error Database: " . $e->getMessage());
}
?>