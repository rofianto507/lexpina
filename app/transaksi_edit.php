<?php
session_start();
include("../config/configuration.php");
      $nama=$_SESSION["nama"];
  $id=$_SESSION["id"];
  $username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
  $foto=$_SESSION["foto"];
  $last_login=$_SESSION["last_login"];
// 1. CEK AUTENTIKASI
if(!isset($_SESSION["id"]) || !isset($_SESSION["nama"])) {
  header("Location: ../login/");
  exit;
}

if(empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_trx = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ==========================================
// PROSES PENANGANAN AKSI & UPDATE (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Akses ditolak! Token keamanan tidak valid.");
    }

    $action = $_POST['action']; // Bisa berisi: 'simpan', 'lunas', atau 'tolak'
    $catatan_admin = $_POST['catatan_admin'] ?? '';

    // Status awal untuk lemparan redirect
    $redirect_status = "PENDING";

    try {
      $stmt_info = $pdo->prepare("SELECT t.user_id, t.produk_id, p.nama_paket 
                                    FROM transaksis t 
                                    LEFT JOIN produks p ON t.produk_id = p.id 
                                    WHERE t.id = ?");
        $stmt_info->execute([$id_trx]);
        $trx_info = $stmt_info->fetch(PDO::FETCH_ASSOC);
        
        $user_id = $trx_info ? $trx_info['user_id'] : 0;
        $produk_id = $trx_info ? $trx_info['produk_id'] : 0;
        $nama_paket = $trx_info ? $trx_info['nama_paket'] : 'Paket Premium';
        if ($action == 'lunas') {
            // 1. Ubah status transaksi menjadi LUNAS
            $stmt = $pdo->prepare("UPDATE transaksis SET status = 'LUNAS', catatan_admin = ? WHERE id = ?");
            $stmt->execute([$catatan_admin, $id_trx]);
            $redirect_status = "LUNAS";
            
            // 2. Ambil informasi User ID dan Produk ID dari transaksi ini
            $stmt_trx_info = $pdo->prepare("SELECT user_id, produk_id FROM transaksis WHERE id = ?");
            $stmt_trx_info->execute([$id_trx]);
            $trx_info = $stmt_trx_info->fetch(PDO::FETCH_ASSOC);
            
            if ($trx_info) {
                $user_id = $trx_info['user_id'];
                $produk_id = $trx_info['produk_id'];
                
                // 3. Ambil durasi paket berlangganan (dalam bulan) dari tabel produks
                $stmt_produk = $pdo->prepare("SELECT durasi_bulan FROM produks WHERE id = ?");
                $stmt_produk->execute([$produk_id]);
                $durasi_bulan = (int)$stmt_produk->fetchColumn();
                
                if ($durasi_bulan <= 0) $durasi_bulan = 1; // Fallback aman: default 1 bulan jika terjadi error data
                
                // 4. Ambil batas_langganan user saat ini
                $stmt_user = $pdo->prepare("SELECT batas_langganan FROM users WHERE id = ?");
                $stmt_user->execute([$user_id]);
                $batas_lama = $stmt_user->fetchColumn();
                
                $sekarang = date('Y-m-d H:i:s');
                
                // 5. Logika Perhitungan Akumulasi Masa Aktif (Berdasarkan Bulan)
                if (!empty($batas_lama) && $batas_lama > $sekarang) {
                    // JIKA MASIH AKTIF: Tambahkan durasi bulan dari SISA masa aktif sebelumnya
                    $batas_baru = date('Y-m-d H:i:s', strtotime($batas_lama . ' + ' . $durasi_bulan . ' months'));
                } else {
                    // JIKA SUDAH KEDALUWARSA / MEMBER BARU: Hitung durasi bulan dimulai dari HARI INI
                    $batas_baru = date('Y-m-d H:i:s', strtotime($sekarang . ' + ' . $durasi_bulan . ' months'));
                }
                
                // 6. Terapkan pembaruan status MEMBER dan penambahan batas waktu ke akun pengguna
                $stmt_update_user = $pdo->prepare("UPDATE users SET akses = 'MEMBER', batas_langganan = ? WHERE id = ?");
                $stmt_update_user->execute([$batas_baru, $user_id]);
                // 7. KIRIM NOTIFIKASI BERHASIL
                $notif_judul = "Pembayaran Berhasil Divalidasi";
                $notif_konten = "Pembayaran Anda untuk **" . $nama_paket . "** telah kami terima. Akun Anda kini berstatus MEMBER dan batas waktu langganan telah ditambahkan. Selamat mengakses seluruh dokumen LexPina!";
                
                $stmt_notif = $pdo->prepare("INSERT INTO notifikasis (user_id, tipe, judul, konten, status) VALUES (?, 'pemberitahuan', ?, ?, 0)");
                $stmt_notif->execute([$user_id, $notif_judul, $notif_konten]);
            }
        } elseif ($action == 'tolak') {
            $stmt = $pdo->prepare("UPDATE transaksis SET status = 'DITOLAK', catatan_admin = ? WHERE id = ?");
            $stmt->execute([$catatan_admin, $id_trx]);
            $redirect_status = "DITOLAK";

            if ($user_id > 0) {
                // KIRIM NOTIFIKASI DITOLAK LENGKAP DENGAN CATATAN ADMIN (JIKA ADA)
                $notif_judul = "Validasi Pembayaran Gagal";
                $alasan = !empty(trim($catatan_admin)) ? " Alasan: " . trim($catatan_admin) : " Mohon pastikan bukti transfer yang diunggah valid dan terbaca dengan jelas.";
                $notif_konten = "Mohon maaf, pembayaran Anda untuk **" . $nama_paket . "** belum dapat kami validasi." . $alasan . " Silakan lakukan pemesanan ulang atau hubungi admin kami.";
                
                $stmt_notif = $pdo->prepare("INSERT INTO notifikasis (user_id, tipe, judul, konten, status) VALUES (?, 'pemberitahuan', ?, ?, 0)");
                $stmt_notif->execute([$user_id, $notif_judul, $notif_konten]);
            }

        } elseif ($action == 'simpan') {
            // Hanya menyimpan catatan tanpa mengubah status
            $stmt = $pdo->prepare("UPDATE transaksis SET catatan_admin = ? WHERE id = ?");
            $stmt->execute([$catatan_admin, $id_trx]);
            
            // Ambil status saat ini untuk redirect
            $stmt_stat = $pdo->prepare("SELECT status FROM transaksis WHERE id = ?");
            $stmt_stat->execute([$id_trx]);
            $redirect_status = $stmt_stat->fetchColumn();
        }

        header("Location: transaksi?status=" . strtolower($redirect_status) . "&msg=sukses_update");
        exit;
    } catch (PDOException $e) {
        die("Error memproses transaksi: " . $e->getMessage());
    }
}

// ==========================================
// AMBIL DATA TRANSAKSI
// ==========================================
try {
    $stmt = $pdo->prepare("SELECT t.*, u.nama AS nama_user, u.username as email, p.nama_paket 
                           FROM transaksis t 
                           LEFT JOIN users u ON t.user_id = u.id 
                           LEFT JOIN produks p ON t.produk_id = p.id 
                           WHERE t.id = ?");
    $stmt->execute([$id_trx]);
    $trx = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trx) {
        die("Data transaksi tidak ditemukan!");
    }

    // Fallback jika field belum ada di database untuk mencegah error
    $nama_pengirim = isset($trx['nama_pengirim']) ? $trx['nama_pengirim'] : '-';
    $catatan_admin = isset($trx['catatan_admin']) ? $trx['catatan_admin'] : '';
    $bukti_transfer = isset($trx['bukti_transfer']) ? $trx['bukti_transfer'] : '';

} catch (PDOException $e) {
    die("Error mengambil data: " . $e->getMessage());
}

$_SESSION["menu"] = "transaksi-" . strtolower($trx['status']);
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Transaksi | LexPina</title>
 <link rel="apple-touch-icon" sizes="180x180" href="../assets/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/img/favicons/favicon-16x16.png">
    <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicons/favicon.ico">
    <link rel="manifest" href="../assets/img/favicons/manifest.json">
    <meta name="msapplication-TileImage" content="../assets/img/favicons/mstile-150x150.png">
    <meta name="theme-color" content="#ffffff">
    <script src="../assets/js/config.js"></script>
    <script src="../vendors/overlayscrollbars/OverlayScrollbars.min.js"></script>
    <link rel="stylesheet" type="text/css" href="../assets/icon/font-awesome/css/font-awesome.min.css">
    <link href="../vendors/prism/prism-okaidia.css" rel="stylesheet">
     <link href="../vendors/overlayscrollbars/OverlayScrollbars.min.css" rel="stylesheet">
    <link href="../assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
    <link href="../assets/css/theme.min.css" rel="stylesheet" id="style-default">
    <link href="../assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
    <link href="../assets/css/user.min.css" rel="stylesheet" id="user-style-default">
        <script>
    var isRTL = JSON.parse(localStorage.getItem('isRTL'));
    if (isRTL) {
      var linkDefault = document.getElementById('style-default');
      var userLinkDefault = document.getElementById('user-style-default');
      linkDefault.setAttribute('disabled', true);
      userLinkDefault.setAttribute('disabled', true);
      document.querySelector('html').setAttribute('dir', 'rtl');
    } else {
      var linkRTL = document.getElementById('style-rtl');
      var userLinkRTL = document.getElementById('user-style-rtl');
      linkRTL.setAttribute('disabled', true);
      userLinkRTL.setAttribute('disabled', true);
    }
    </script>
  </head>
  <body>
   <main class="main" id="top">
      <div class="container-fluid" data-layout="container">
        <?php include_once("navbar.php") ?>
        <div class="content">
          <?php include_once("header.php") ?>
          
          <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
            <div class="d-flex">
                <span class="fa fa-shopping-cart me-2 fs-3 text-primary"></span>
                <div>
                <h4 class="mb-0">Detail Transaksi #TRX-<?php echo $id_trx; ?></h4>
                <span class="text-muted">Dibuat pada: <?php echo date('d/m/Y H:i', strtotime($trx['created_at'])); ?></span>
                </div>
            </div>
            <div>
                <?php 
                if($trx['status'] == 'LUNAS') echo '<span class="badge bg-success fs-0">LUNAS</span>';
                elseif($trx['status'] == 'DITOLAK') echo '<span class="badge bg-danger fs-0">DITOLAK</span>';
                else echo '<span class="badge bg-warning fs-0">PENDING</span>';
                ?>
            </div>
          </div>

          <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="row g-3">
              <div class="col-lg-8">
                <div class="card mb-3">
                  <div class="card-header bg-light">
                    <h6 class="mb-0">Informasi Pemesanan</h6>
                  </div>
                  <div class="card-body">
                    <div class="row g-3">
                      
                      <div class="col-md-6">
                        <label class="form-label text-muted">Nama Pengguna</label>
                        <input type="text" class="form-control bg-white" value="<?php echo htmlspecialchars($trx['nama_user']); ?>" readonly>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label text-muted">Email</label>
                        <input type="text" class="form-control bg-white" value="<?php echo htmlspecialchars($trx['email']); ?>" readonly>
                      </div>

                      <div class="col-md-6">
                        <label class="form-label text-muted">Paket Berlangganan</label>
                        <input type="text" class="form-control bg-white fw-bold text-primary" value="<?php echo htmlspecialchars($trx['nama_paket']); ?>" readonly>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label text-muted">Total Tagihan (Transfer)</label>
                        <input type="text" class="form-control bg-white fw-bold" value="Rp <?php echo number_format($trx['total_transfer'], 0, ',', '.'); ?>" readonly>
                      </div>

                      <div class="col-md-12 mt-4">
                        <label class="form-label fw-bold"><i class="fa fa-sticky-note-o text-warning"></i> Catatan Internal Admin</label>
                        <textarea class="form-control" name="catatan_admin" rows="4" placeholder="Tambahkan catatan khusus untuk transaksi ini (misal: Alasan penolakan, info kontak, dll)"><?php echo htmlspecialchars($catatan_admin); ?></textarea>
                        <small class="text-muted">Catatan ini hanya dapat dilihat oleh admin.</small>
                      </div>

                    </div>
                  </div>
                </div>
              </div>

              <div class="col-lg-4">
                
                <div class="card mb-3">
                  <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fa fa-money text-success me-2"></i>Bukti Pembayaran</h6>
                  </div>
                  <div class="card-body text-center">
                    
                    <?php if(!empty($bukti_transfer)): ?>
                        <a href="../public/upload/bukti/<?php echo $bukti_transfer; ?>" target="_blank">
                            <img src="../public/upload/bukti/<?php echo $bukti_transfer; ?>" class="img-fluid rounded border mb-3" style="max-height: 250px; object-fit: contain;" alt="Bukti Transfer">
                        </a>
                        <small class="d-block text-muted mb-3">(Klik gambar untuk memperbesar)</small>
                    <?php else: ?>
                        <div class="p-4 bg-light border border-dashed rounded mb-3 text-muted">
                            <i class="fa fa-image fs-3 mb-2"></i><br>
                            Belum ada bukti transfer yang diunggah.
                        </div>
                    <?php endif; ?>

                    <div class="text-start bg-light p-3 rounded">
                        
                        <div>
                            <span class="text-muted d-block" style="font-size:12px;">Nama Rekening Pengirim:</span>
                            <strong class="text-dark"><?php echo htmlspecialchars($nama_pengirim); ?></strong>
                        </div>
                    </div>

                  </div>
                </div>

                <div class="card mb-3">
                  <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fa fa-cogs text-secondary me-2"></i>Aksi Transaksi</h6>
                  </div>
                  <div class="card-body">
                    
                    <div class="alert alert-info py-2 fs--1 mb-4" role="alert">
                      <i class="fa fa-info-circle me-1"></i> Cocokkan nama pengirim dengan mutasi rekening sebelum melakukan validasi.
                    </div>

                    <div class="d-grid gap-2">
                        <?php if($trx['status'] == 'PENDING'): ?>
                            <button class="btn btn-success" type="submit" name="action" value="lunas">
                                <i class="fa fa-check-circle me-1"></i> Validasi (LUNAS)
                            </button>
                            <button class="btn btn-danger" type="submit" name="action" value="tolak" onclick="return confirm('Yakin ingin menolak transaksi ini?');">
                                <i class="fa fa-times-circle me-1"></i> Tolak Transaksi
                            </button>
                        <?php endif; ?>

                        <button class="btn btn-outline-primary mt-2" type="submit" name="action" value="simpan">
                            <i class="fa fa-save me-1"></i> Simpan Catatan
                        </button>
                        
                        <a href="transaksi?status=<?php echo strtolower($trx['status']); ?>" class="btn btn-outline-secondary mt-1">
                            <i class="fa fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </form>
          <?php include_once("footer.php") ?>
        </div>
      </div>
    </main>
    <script src="../vendors/jquery/jquery-3.7.0.min.js"></script>
    <script src="../vendors/popper/popper.min.js"></script>
    <script src="../vendors/bootstrap/bootstrap.min.js"></script>
    <script src="../vendors/anchorjs/anchor.min.js"></script>
    <script src="../vendors/is/is.min.js"></script>
    <script src="../vendors/prism/prism.js"></script>
    <script src="../vendors/lodash/lodash.min.js"></script>
    <script src="../vendors/list.js/list.min.js"></script>
    <script src="../assets/js/theme.js"></script>
  </body>
</html>