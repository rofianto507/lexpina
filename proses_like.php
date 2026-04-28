<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/configuration.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id    = $_SESSION['user_id'];
$dokumen_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action     = isset($_GET['action']) ? $_GET['action'] : '';

if ($dokumen_id > 0) {
    try {
        if ($action == 'add') {
            // 1. Tambahkan ke tabel likes
            $stmt = $pdo->prepare("INSERT IGNORE INTO likes (user_id, dokumen_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $dokumen_id]);
            
            // 2. Jika berhasil insert (baris bertambah), update total like di tabel databases
            if ($stmt->rowCount() > 0) {
                $pdo->prepare("UPDATE `databases` SET likes = likes + 1 WHERE id = ?")->execute([$dokumen_id]);
            }
            
        } elseif ($action == 'remove') {
            // 1. Hapus dari tabel likes
            $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND dokumen_id = ?");
            $stmt->execute([$user_id, $dokumen_id]);
            
            // 2. Jika berhasil dihapus, kurangi total like di tabel databases
            if ($stmt->rowCount() > 0) {
                $pdo->prepare("UPDATE `databases` SET likes = likes - 1 WHERE id = ?")->execute([$dokumen_id]);
            }
        }
    } catch (PDOException $e) {
        die("Error proses like: " . $e->getMessage());
    }
}

// Redirect kembali ke halaman detail dokumen
$stmt_cat = $pdo->prepare("SELECT kategori FROM `databases` WHERE id = ?");
$stmt_cat->execute([$dokumen_id]);
$doc = $stmt_cat->fetch();
$kategori = $doc ? $doc['kategori'] : 'peraturan';

header("Location: database_detail.php?id=" . $dokumen_id . "&kategori=" . $kategori);
exit();
?>