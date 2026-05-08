<?php
/**
 * PDF Proxy - Serve file PDF secara aman tanpa expose path asli
 * Mencegah direct download & memastikan kompatibilitas mobile
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/configuration.php';

// Validasi ID
$id_dokumen = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_dokumen <= 0) {
    http_response_code(400);
    exit('Invalid request.');
}

try {
    // Ambil nama file dari DB (jangan percaya input user untuk path file)
    $stmt = $pdo->prepare("SELECT file_pdf, kategori FROM `databases` WHERE id = ? AND status = 1");
    $stmt->execute([$id_dokumen]);
    $doc = $stmt->fetch();

    if (!$doc || empty($doc['file_pdf'])) {
        http_response_code(404);
        exit('Dokumen tidak ditemukan.');
    }

    // Cek akses konsolidasi
    $user_akses = isset($_SESSION['akses']) ? $_SESSION['akses'] : 'PENGGUNA';
    if ($doc['kategori'] == 'peraturan-konsolidasi' && $user_akses !== 'MEMBER') {
        http_response_code(403);
        exit('Akses ditolak.');
    }

    // Bangun path file secara server-side (tidak dari input user)
    $base_server_path = dirname(__FILE__); // Root folder project di server
    $file_path = $base_server_path . '/public/upload/documents/' . basename($doc['file_pdf']);

    if (!file_exists($file_path)) {
        http_response_code(404);
        exit('File tidak ditemukan.');
    }

    // Kirim file sebagai response
    // Content-Disposition: inline → browser menampilkan, bukan download
    // Cache-Control → agar tidak disimpan
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="document.pdf"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    // Blokir embedding di luar domain ini
    header('X-Frame-Options: SAMEORIGIN');

    // Bersihkan buffer sebelum kirim binary
    if (ob_get_level()) ob_end_clean();

    readfile($file_path);
    exit();

} catch (PDOException $e) {
    http_response_code(500);
    exit('Server error.');
}