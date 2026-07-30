<?php
// ==========================================
// Webhook Midtrans (Payment Notification URL)
// Dipanggil server-to-server oleh Midtrans, BUKAN oleh browser user.
// Jangan pakai session/CSRF di sini - keamanannya lewat signature_key.
// ==========================================
require_once 'config/configuration.php';

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$notif = json_decode($raw, true);

if (!$notif || !isset($notif['order_id'], $notif['status_code'], $notif['gross_amount'], $notif['signature_key'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Payload tidak valid.']);
    exit();
}

$order_id        = $notif['order_id'];
$status_code     = $notif['status_code'];
$gross_amount    = $notif['gross_amount'];
$signature_key   = $notif['signature_key'];
$transaction_status = $notif['transaction_status'] ?? '';
$fraud_status    = $notif['fraud_status'] ?? '';
$payment_type    = $notif['payment_type'] ?? null;

// 1. WAJIB: verifikasi signature supaya notifikasi tidak bisa dipalsukan
$signature_valid = hash('sha512', $order_id . $status_code . $gross_amount . $midtrans_server_key);
if (!hash_equals($signature_valid, $signature_key)) {
    http_response_code(403);
    echo json_encode(['message' => 'Signature tidak valid.']);
    exit();
}

try {
    // 2. Cari transaksi berdasarkan order_id Midtrans
    $stmt = $pdo->prepare("SELECT t.*, p.durasi_bulan, p.nama_paket FROM transaksis t JOIN produks p ON t.produk_id = p.id WHERE t.midtrans_order_id = ?");
    $stmt->execute([$order_id]);
    $trx = $stmt->fetch();

    if (!$trx) {
        // order_id tidak dikenali - tetap balas 200 supaya Midtrans berhenti retry
        http_response_code(200);
        echo json_encode(['message' => 'Order ID tidak ditemukan, diabaikan.']);
        exit();
    }

    // 3. Idempotensi - kalau transaksi sudah final (LUNAS/DITOLAK), jangan diproses ulang
    // (Midtrans bisa mengirim notifikasi berkali-kali untuk status yang sama)
    if ($trx['status'] === 'LUNAS' || $trx['status'] === 'DITOLAK') {
        http_response_code(200);
        echo json_encode(['message' => 'Transaksi sudah final, diabaikan.']);
        exit();
    }

    $is_paid  = ($transaction_status === 'settlement') || ($transaction_status === 'capture' && $fraud_status === 'accept');
    $is_gagal = in_array($transaction_status, ['deny', 'cancel', 'expire'], true);

    if ($is_paid) {
        // Samakan persis dengan alur approve manual di app/transaksi_edit.php
        $pdo->prepare("UPDATE transaksis SET status = 'LUNAS', payment_type = ?, catatan_admin = 'Dibayar otomatis via Midtrans' WHERE id = ?")
            ->execute([$payment_type, $trx['id']]);

        $durasi_bulan = (int)$trx['durasi_bulan'];
        if ($durasi_bulan <= 0) $durasi_bulan = 1;

        $stmt_user = $pdo->prepare("SELECT batas_langganan FROM users WHERE id = ?");
        $stmt_user->execute([$trx['user_id']]);
        $batas_lama = $stmt_user->fetchColumn();

        $batas_baru = hitung_batas_langganan_baru($batas_lama, $durasi_bulan);

        $pdo->prepare("UPDATE users SET akses = 'MEMBER', batas_langganan = ? WHERE id = ?")
            ->execute([$batas_baru, $trx['user_id']]);

        $notif_judul  = "Pembayaran Berhasil Divalidasi";
        $notif_konten = "Pembayaran Anda untuk **" . $trx['nama_paket'] . "** via Midtrans telah kami terima. Akun Anda kini berstatus MEMBER dan batas waktu langganan telah ditambahkan. Selamat mengakses seluruh dokumen LexPina!";
        $pdo->prepare("INSERT INTO notifikasis (user_id, tipe, judul, konten, status) VALUES (?, 'pemberitahuan', ?, ?, 0)")
            ->execute([$trx['user_id'], $notif_judul, $notif_konten]);

    } elseif ($is_gagal) {
        $pdo->prepare("UPDATE transaksis SET status = 'DITOLAK', payment_type = ?, catatan_admin = ? WHERE id = ?")
            ->execute([$payment_type, 'Pembayaran Midtrans: ' . $transaction_status, $trx['id']]);

        $notif_judul  = "Pembayaran Tidak Berhasil";
        $notif_konten = "Pembayaran Anda untuk **" . $trx['nama_paket'] . "** via Midtrans tidak berhasil diselesaikan (" . $transaction_status . "). Silakan lakukan pemesanan ulang.";
        $pdo->prepare("INSERT INTO notifikasis (user_id, tipe, judul, konten, status) VALUES (?, 'pemberitahuan', ?, ?, 0)")
            ->execute([$trx['user_id'], $notif_judul, $notif_konten]);

    } else {
        // transaction_status = 'pending' atau status lain -> cukup catat payment_type, tetap PENDING
        $pdo->prepare("UPDATE transaksis SET payment_type = ? WHERE id = ?")
            ->execute([$payment_type, $trx['id']]);
    }

    http_response_code(200);
    echo json_encode(['message' => 'OK']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Server error.']);
}
