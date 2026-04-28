<?php
// Pastikan tidak ada error yang tampil ke user jika tracking gagal
error_reporting(0);

// ==========================================
// PENCEGAH DUPLIKASI (UNIQUE VISITOR CHECK)
// ==========================================
// Jika sesi pengunjung ini sudah ditandai pernah direkam, hentikan skrip.
// Ini mengubah pencatatan dari "Pageviews" menjadi "Unique Sessions"
if (isset($_SESSION['lexpina_tracked_session']) && $_SESSION['lexpina_tracked_session'] === true) {
    return; // Berhenti mengeksekusi kode di bawah ini
}

// 1. Ambil Data Dasar
// Deteksi IP asli (mengatasi Cloudflare atau Proxy)
$ip_address = $_SERVER['REMOTE_ADDR'];
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip_address = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip_address = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
}

$session_id  = session_id(); 
$user_id     = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$url_visited = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
$referrer    = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
$user_agent  = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';

// 2. Fungsi Ringan untuk Ekstrak OS, Device, dan Browser
$os      = 'Unknown';
$browser = 'Unknown';
$device  = 'desktop'; // Set default ke desktop

// Ekstrak OS
if (preg_match('/windows|win32/i', $user_agent)) {
    $os = 'Windows';
} elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
    $os = 'Mac OS';
} elseif (preg_match('/android/i', $user_agent)) {
    $os = 'Android';
} elseif (preg_match('/linux/i', $user_agent)) {
    $os = 'Linux';
} elseif (preg_match('/iphone|ipad|ipod/i', $user_agent)) {
    $os = 'iOS';
}

// Ekstrak Device (Mobile, Tablet, Bot)
if (preg_match('/mobi|touch|mini|smartphone/i', $user_agent)) {
    $device = 'mobile';
}
if (preg_match('/tablet|ipad/i', $user_agent)) {
    $device = 'tablet';
}
if (preg_match('/bot|crawl|slurp|spider|mediapartners/i', $user_agent)) {
    $device = 'bot';
}

// Ekstrak Browser
if (preg_match('/MSIE/i', $user_agent) || preg_match('/Trident/i', $user_agent)) {
    $browser = 'Internet Explorer';
} elseif (preg_match('/Edge/i', $user_agent) || preg_match('/Edg/i', $user_agent)) {
    $browser = 'Edge';
} elseif (preg_match('/Firefox/i', $user_agent)) {
    $browser = 'Firefox';
} elseif (preg_match('/OPR/i', $user_agent) || preg_match('/Opera/i', $user_agent)) {
    $browser = 'Opera';
} elseif (preg_match('/Chrome/i', $user_agent)) {
    $browser = 'Chrome';
} elseif (preg_match('/Safari/i', $user_agent)) {
    $browser = 'Safari';
}

// 3. Simpan ke Database
try {
    $stmt_visitor = $pdo->prepare("
        INSERT INTO visitors 
        (ip_address, session_id, user_id, url_visited, referrer, user_agent, browser, os, device) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt_visitor->execute([
        $ip_address, 
        $session_id, 
        $user_id, 
        $url_visited, 
        $referrer, 
        $user_agent, 
        $browser, 
        $os, 
        $device
    ]);

    // Jika proses simpan ke database berhasil, berikan tanda pada sesi ini
    // Sehingga saat user pindah halaman, mereka tidak direkam lagi
    $_SESSION['lexpina_tracked_session'] = true;

} catch (PDOException $e) {
    // Abaikan jika error agar tidak merusak tampilan website
}
?>