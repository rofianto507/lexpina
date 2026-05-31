<?php
header('Content-Type: application/json');
require_once 'config/configuration.php';

$kode = strtoupper(trim($_GET['kode'] ?? ''));

if (empty($kode)) {
    echo json_encode(['status' => 'invalid', 'message' => 'Kode tidak boleh kosong.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM promos WHERE kode = ? AND status = 1 LIMIT 1");
    $stmt->execute([$kode]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$promo) {
        echo json_encode(['status' => 'invalid', 'message' => 'Kode promo tidak ditemukan atau tidak aktif.']);
        exit;
    }

    // Cek expired
    if (!empty($promo['expired_at'])) {
        $now    = new DateTime();
        $exp_dt = new DateTime($promo['expired_at']);
        if ($now > $exp_dt) {
            echo json_encode(['status' => 'invalid', 'message' => 'Kode promo sudah kadaluarsa.']);
            exit;
        }
    }

    // Ambil nilai diskon (nominal lebih prioritas, jika tidak ada pakai persentase)
    // Catatan: kalkulasi persentase dilakukan di sisi server berdasarkan harga paket
    echo json_encode([
        'status'          => 'valid',
        'message'         => 'Promo "' . htmlspecialchars($promo['nama']) . '" berhasil diterapkan!',
        'nominal'         => (float)($promo['nominal'] ?? 0),
        'persentase'      => (float)($promo['persentase'] ?? 0),
        'keterangan'      => htmlspecialchars($promo['keterangan']),
        // diskon_nominal akan dihitung client-side setelah tahu harga paket
        'diskon_nominal'  => (float)($promo['nominal'] ?? 0),
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'invalid', 'message' => 'Terjadi kesalahan server.']);
}
