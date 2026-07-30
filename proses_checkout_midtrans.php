<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once 'config/configuration.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu.']);
    exit();
}

if (empty($midtrans_server_key)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Payment gateway belum dikonfigurasi. Hubungi admin.']);
    exit();
}

$user_id      = $_SESSION['user_id'];
$id_produk    = isset($_POST['id_produk']) ? (int)$_POST['id_produk'] : 0;
$kode_promo   = isset($_POST['kode_promo']) ? strtoupper(trim($_POST['kode_promo'])) : '';

try {
    // 1. Ambil data paket dari DB (jangan percaya harga dari client)
    $stmt = $pdo->prepare("SELECT * FROM produks WHERE id = ? AND status = 1");
    $stmt->execute([$id_produk]);
    $paket = $stmt->fetch();

    if (!$paket) {
        echo json_encode(['success' => false, 'message' => 'Paket langganan tidak ditemukan.']);
        exit();
    }

    $harga_paket = (int)$paket['total_bayar'];

    // 2. Validasi & hitung ulang diskon promo di SERVER (bukan dari input client)
    $diskon_nominal = 0;
    if ($kode_promo !== '') {
        $stmt_promo = $pdo->prepare("SELECT * FROM promos WHERE kode = ? AND status = 1 LIMIT 1");
        $stmt_promo->execute([$kode_promo]);
        $promo = $stmt_promo->fetch();

        if ($promo) {
            $valid_promo = true;
            if (!empty($promo['expired_at']) && new DateTime() > new DateTime($promo['expired_at'])) {
                $valid_promo = false;
            }
            if ($valid_promo) {
                $diskon_nominal += (float)($promo['nominal'] ?? 0);
                if ((float)($promo['persentase'] ?? 0) > 0) {
                    $diskon_nominal += round($harga_paket * (float)$promo['persentase'] / 100);
                }
                $diskon_nominal = min($diskon_nominal, $harga_paket);
            } else {
                $kode_promo = null; // promo kadaluarsa, abaikan
            }
        } else {
            $kode_promo = null; // promo tidak valid, abaikan
        }
    } else {
        $kode_promo = null;
    }

    $gross_amount = (int)($harga_paket - $diskon_nominal);
    if ($gross_amount < 1) $gross_amount = 1; // Midtrans mewajibkan gross_amount positif

    // 3. Simpan transaksi PENDING dulu (belum ada order_id Midtrans)
    $stmt_ins = $pdo->prepare("INSERT INTO transaksis (user_id, produk_id, metode_bayar, total_transfer, kode_promo, diskon_nominal, status) VALUES (?, ?, 'midtrans', ?, ?, ?, 'PENDING')");
    $stmt_ins->execute([$user_id, $id_produk, $gross_amount, $kode_promo, $diskon_nominal]);
    $trx_id = $pdo->lastInsertId();

    // 4. Buat order_id unik yang bisa dilacak balik ke baris transaksi ini
    $order_id = 'LEXPINA-' . $trx_id . '-' . time();
    $pdo->prepare("UPDATE transaksis SET midtrans_order_id = ? WHERE id = ?")->execute([$order_id, $trx_id]);

    // 5. Ambil data user untuk customer_details
    $stmt_user = $pdo->prepare("SELECT nama, username FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $u = $stmt_user->fetch();
    $nama_user  = !empty($u['nama']) ? $u['nama'] : 'Pengguna LexPina';
    $email_user = (!empty($u['username']) && filter_var($u['username'], FILTER_VALIDATE_EMAIL)) ? $u['username'] : 'noemail@lexpina.com';

    // 6. Bangun request ke Midtrans Snap API
    $payload = [
        'transaction_details' => [
            'order_id'     => $order_id,
            'gross_amount' => $gross_amount,
        ],
        'customer_details' => [
            'first_name' => $nama_user,
            'email'      => $email_user,
        ],
        'item_details' => [[
            'id'       => 'PRODUK-' . $paket['id'],
            'price'    => $gross_amount,
            'quantity' => 1,
            'name'     => substr('Langganan ' . $paket['nama_paket'], 0, 50),
        ]],
    ];

    $ch = curl_init($midtrans_snap_base_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($midtrans_server_key . ':'),
        ],
        CURLOPT_TIMEOUT => 20,
    ]);
    $response   = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    $result = $response ? json_decode($response, true) : null;

    if ($result && isset($result['token'])) {
        echo json_encode([
            'success' => true,
            'token'   => $result['token'],
        ]);
    } else {
        // Gagal buat transaksi Midtrans -> tandai transaksi supaya tidak menggantung sebagai PENDING
        $pesan_error = $curl_error ?: ($result['error_messages'][0] ?? 'Gagal menghubungi payment gateway.');
        $pdo->prepare("UPDATE transaksis SET status = 'DITOLAK', catatan_admin = ? WHERE id = ?")
            ->execute(['Gagal membuat transaksi Midtrans: ' . $pesan_error, $trx_id]);

        echo json_encode(['success' => false, 'message' => $pesan_error]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server.']);
}
