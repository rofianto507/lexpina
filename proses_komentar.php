<?php
// 1. Mulai sesi jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Panggil koneksi database
require_once 'config/configuration.php';

// 3. Pastikan request datang dari metode POST (bukan diketik langsung di URL)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 4. Lapis Keamanan 1: Cek apakah user benar-benar sudah login
    if (!isset($_SESSION['user_id'])) {
        // Jika ketahuan mencoba bypass, kembalikan ke beranda
        header("Location: index.php");
        exit();
    }

    // 5. Tangkap dan bersihkan data dari form
    $berita_id    = isset($_POST['berita_id']) ? (int)$_POST['berita_id'] : 0;
    $slug         = isset($_POST['slug']) ? trim($_POST['slug']) : '';
    $isi_komentar = isset($_POST['isi_komentar']) ? trim($_POST['isi_komentar']) : '';
    $user_id      = $_SESSION['user_id'];

    // 6. Lapis Keamanan 2: Pastikan data tidak kosong
    if ($berita_id > 0 && !empty($slug) && !empty($isi_komentar)) {
        
        try {
            // 7. Simpan ke database menggunakan Prepared Statement (Aman dari SQL Injection)
            // Status diset 1 agar langsung tampil. Jika ingin moderasi, ubah menjadi 0.
            $stmt = $pdo->prepare("INSERT INTO komentars (berita_id, user_id, isi_komentar, status) VALUES (:berita_id, :user_id, :isi_komentar, 1)");
            
            $stmt->bindValue(':berita_id', $berita_id, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':isi_komentar', htmlspecialchars($isi_komentar), PDO::PARAM_STR); // htmlspecialchars untuk mencegah script XSS tersimpan
            
            $stmt->execute();

            // 8. Berhasil! Redirect kembali ke halaman berita dan otomatis scroll ke kolom komentar
            header("Location: berita_detail.php?slug=" . urlencode($slug) . "#kolom-komentar");
            exit();

        } catch (PDOException $e) {
            // Jika database error, hentikan dan tampilkan pesan (bisa diganti dengan redirect ke halaman error khusus nantinya)
            die("Gagal menyimpan komentar: " . $e->getMessage());
        }

    } else {
        // Jika komentar kosong tapi dipaksa dikirim, kembalikan saja ke halaman beritanya
        header("Location: berita_detail.php?slug=" . urlencode($slug) . "#kolom-komentar");
        exit();
    }

} else {
    // Jika ada yang mencoba mengakses file ini langsung dari URL (proses_komentar.php), tendang ke beranda
    header("Location: index.php");
    exit();
}
?>