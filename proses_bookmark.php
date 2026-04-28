<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/configuration.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id    = $_SESSION['user_id'];
$dokumen_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action     = isset($_GET['action']) ? $_GET['action'] : '';
$ref        = isset($_GET['ref']) ? $_GET['ref'] : 'detail'; // Untuk tahu user menekan tombol dari halaman mana

if ($dokumen_id > 0) {
    try {
        if ($action == 'add') {
            // Tambahkan ke bookmark (Gunakan INSERT IGNORE untuk menghindari error jika data sudah ada)
            $stmt = $pdo->prepare("INSERT IGNORE INTO bookmarks (user_id, dokumen_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $dokumen_id]);
        } elseif ($action == 'remove') {
            // Hapus dari bookmark
            $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ? AND dokumen_id = ?");
            $stmt->execute([$user_id, $dokumen_id]);
        }
    } catch (PDOException $e) {
        die("Error proses bookmark: " . $e->getMessage());
    }
}

// Redirect kembali ke halaman yang tepat
if ($ref == 'profil') {
    // Jika dihapus dari halaman profil
    header("Location: profil.php?tab=bookmark");
} else {
    // Jika ditambahkan/dihapus dari halaman detail dokumen (ambil kategori dokumennya dulu untuk URL)
    $stmt_cat = $pdo->prepare("SELECT kategori FROM `databases` WHERE id = ?");
    $stmt_cat->execute([$dokumen_id]);
    $doc = $stmt_cat->fetch();
    $kategori = $doc ? $doc['kategori'] : 'peraturan';
    
    header("Location: database_detail.php?id=" . $dokumen_id . "&kategori=" . $kategori);
}
exit();
?>