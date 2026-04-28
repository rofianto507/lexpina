<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/configuration.php';

if (!isset($_SESSION['user_id'])) {
    exit(); // Hentikan eksekusi diam-diam jika tidak login
}

$user_id = $_SESSION['user_id'];
$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action  = isset($_GET['action']) ? $_GET['action'] : '';
$is_ajax = isset($_GET['ajax']) ? true : false; // Deteksi apakah ini permintaan dari background (AJAX)

try {
    if ($action == 'read_all') {
        $stmt = $pdo->prepare("UPDATE notifikasis SET status = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
    } elseif ($id > 0) {
        $stmt = $pdo->prepare("UPDATE notifikasis SET status = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
    }
} catch (PDOException $e) {
    // Abaikan jika error
}

// Jika permintaan dari JS/AJAX, cukup kembalikan status sukses tanpa redirect
if ($is_ajax) {
    echo json_encode(['status' => 'success']);
    exit();
}

// Jika bukan AJAX (misal klik "Tandai Semua Dibaca"), lakukan redirect normal
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
header("Location: " . $referer);
exit();
?>