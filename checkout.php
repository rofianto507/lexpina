<?php 
session_start();
if(!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    header("Location: langganan.php");
    exit();
}

require_once 'config/configuration.php';

// Tangkap ID produk dari URL
$id_produk = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // Tarik data paket yang dipilih
    $stmt = $pdo->prepare("SELECT * FROM produks WHERE id = ? AND status = 1");
    $stmt->execute([$id_produk]);
    $paket = $stmt->fetch();

    // Jika user iseng memasukkan ID abal-abal di URL
    if(!$paket) {
        die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>Paket tidak ditemukan.</h2><a href='langganan.php'>Kembali</a></div>");
    }

    // Generate Kode Unik (contoh: 123)
    // Catatan: Di tahap produksi nanti, kode ini akan disimpan ke tabel transaksi agar tidak berubah saat di-refresh
    $kode_unik = rand(111, 999);
    $total_transfer = intval($paket['total_bayar']) + $kode_unik;

} catch (PDOException $e) {
    die("Error Database: " . $e->getMessage());
}

$active_page = ''; 
include 'header.php'; 
include 'navbar.php'; 
?>

    <main class="checkout-page">
        <div class="checkout-container">
            
            <div class="checkout-header">
                <h2>Selesaikan Pembayaran Anda</h2>
                <p>Selangkah lagi untuk menjadi Member Premium LexPina.</p>
            </div>

            <div class="checkout-grid">
                
                <div class="checkout-summary">
                    <h3>Ringkasan Pesanan</h3>
                    
                    <div class="summary-item">
                        <span class="summary-label">Paket Langganan</span>
                        <span class="summary-value">LexPina Premium - <?php echo $paket['nama_paket']; ?></span>
                    </div>
                    
                    <div class="summary-item">
                        <span class="summary-label">Harga Paket</span>
                        <span class="summary-value">Rp <?php echo number_format($paket['total_bayar'], 0, ',', '.'); ?></span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Kode Unik <i class="fa-solid fa-circle-info" title="Untuk validasi otomatis"></i></span>
                        <span class="summary-value code-highlight">+ Rp <?php echo $kode_unik; ?></span>
                    </div>

                    <div class="summary-total">
                        <span class="total-label">Total Transfer</span>
                        <span class="total-value">
                            Rp <span class="digit-highlight"><?php echo number_format($total_transfer, 0, ',', '.'); ?></span>
                        </span>
                    </div>
                    <div class="transfer-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i> 
                        Pastikan nominal transfer <strong>TEPAT HINGGA 3 DIGIT TERAKHIR</strong>.
                    </div>
                </div>

                <div class="checkout-payment">
                    <h3>Metode Pembayaran</h3>
                    
                    <div class="bank-card">
                        <img src="assets/img/bca.png" alt="Logo BCA" class="bank-logo">
                        <div class="bank-details">
                            <span class="bank-name">Bank Central Asia (BCA)</span>
                            <span class="bank-account">8213 5840 605</span>
                            <span class="bank-owner">a.n. PT LexPina Hukum Indonesia</span>
                        </div>
                    </div>

                    <hr class="checkout-divider">

                    <h3>Konfirmasi Pembayaran</h3>
                    <p class="form-instruction">Sudah melakukan transfer? Unggah bukti pembayaran Anda di bawah ini.</p>
                    
                    <form action="proses_checkout.php" method="POST" enctype="multipart/form-data" class="form-checkout">
                        <input type="hidden" name="id_produk" value="<?php echo $paket['id']; ?>">
                        <input type="hidden" name="total_transfer" value="<?php echo $total_transfer; ?>">

                        <div class="form-group">
                            <label>Nama Pengirim (Pemilik Rekening)</label>
                            <input type="text" name="nama_pengirim" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label>Bukti Transfer (JPG/PNG)</label>
                            <div class="file-upload-wrapper">
                                <input type="file" name="bukti_transfer" class="file-input" accept=".jpg, .jpeg, .png" required>
                            </div>
                        </div>

                        <button type="submit" class="btn-confirm-payment"><i class="fa-solid fa-shield-check"></i> Konfirmasi & Kirim Bukti</button>
                    </form>
                </div>

            </div>

        </div>
    </main>

<?php include 'footer.php'; ?>