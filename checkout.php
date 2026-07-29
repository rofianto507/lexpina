<?php 
session_start();
if(!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    header("Location: langganan");
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
        die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>Paket tidak ditemukan.</h2><a href='langganan'>Kembali</a></div>");
    }

    // Generate Kode Unik
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

                    <!-- Kode Promo -->
                    <div class="summary-item promo-input-row" style="flex-direction: column; align-items: flex-start; gap: 8px;">
                        <span class="summary-label">Kode Promo</span>
                        <div style="display:flex; gap:8px; width:100%;">
                            <input type="text" id="input_kode_promo" placeholder="Masukkan kode promo" class="form-input" style="text-transform:uppercase;">
                            <button type="button" id="btn_terapkan_promo" class="btn-promo" 
                            style="padding: 10px 16px; font-size:13px; font-weight:600; white-space:nowrap; cursor:pointer; border-radius:6px;transition: background 0.3s; border:none;background-color:black;color:white;">
                                <i class="fa-solid fa-tag"></i> Terapkan
                            </button>
                        </div>
                        <div id="promo_feedback" style="font-size:13px;"></div>
                    </div>

                    <!-- Baris diskon (tersembunyi sampai promo valid) -->
                    <div class="summary-item" id="row_diskon" style="display:none;">
                        <span class="summary-label" style="color:#27ae60;"><i class="fa-solid fa-circle-check"></i> Diskon Promo</span>
                        <span class="summary-value" id="label_diskon" style="color:#27ae60; font-weight:bold;"></span>
                    </div>

                    <div class="summary-total">
                        <span class="total-label">Total Transfer</span>
                        <span class="total-value">
                            Rp <span class="digit-highlight" id="label_total_transfer"><?php echo number_format($total_transfer, 0, ',', '.'); ?></span>
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
                            <span class="bank-account">5230288854</span>
                            <span class="bank-owner">a.n. RENDY CLAUDIO KRISNA IROTH</span>
                        </div>
                    </div>

                    <hr class="checkout-divider">

                    <h3>Konfirmasi Pembayaran</h3>
                    <p class="form-instruction">Sudah melakukan transfer? Unggah bukti pembayaran Anda di bawah ini.</p>
                    
                    <form action="proses_checkout.php" method="POST" enctype="multipart/form-data" class="form-checkout">
                        <input type="hidden" name="id_produk" value="<?php echo $paket['id']; ?>">
                        <input type="hidden" name="total_transfer" id="hidden_total_transfer" value="<?php echo $total_transfer; ?>">
                        <input type="hidden" name="kode_promo" id="hidden_kode_promo" value="">
                        <input type="hidden" name="diskon_nominal" id="hidden_diskon_nominal" value="0">

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

<script>
(function() {
    const hargaPaket  = <?php echo intval($paket['total_bayar']); ?>;
    const kodeUnik    = <?php echo $kode_unik; ?>;
    let diskonNominal = 0;

    function formatRupiah(angka) {
        return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function hitungTotal(diskon) {
        return (hargaPaket - diskon) + kodeUnik;
    }

    document.getElementById('btn_terapkan_promo').addEventListener('click', function() {
        const kode = document.getElementById('input_kode_promo').value.trim().toUpperCase();
        const feedback = document.getElementById('promo_feedback');

        if (!kode) {
            feedback.innerHTML = '<span style="color:#e74c3c;">Masukkan kode promo terlebih dahulu.</span>';
            return;
        }

        feedback.innerHTML = '<span style="color:#999;"><i class="fa-solid fa-spinner fa-spin"></i> Memvalidasi...</span>';

        fetch('cek_promo.php?kode=' + encodeURIComponent(kode))
            .then(res => res.json())
            .then(data => {
                if (data.status === 'valid') {
                    // Hitung diskon: gabungkan nominal + persentase jika keduanya ada
                    let diskonNominalVal = data.nominal > 0 ? data.nominal : 0;
                    let diskonPersenVal  = data.persentase > 0 ? Math.round(hargaPaket * data.persentase / 100) : 0;
                    diskonNominal = diskonNominalVal + diskonPersenVal;
                    // Pastikan diskon tidak melebihi harga paket
                    diskonNominal = Math.min(diskonNominal, hargaPaket);
                    const total = hitungTotal(diskonNominal);

                    // Update tampilan diskon
                    document.getElementById('row_diskon').style.display = 'flex';
                    document.getElementById('label_diskon').textContent = '- Rp ' + formatRupiah(diskonNominal);

                    // Update total transfer
                    document.getElementById('label_total_transfer').textContent = formatRupiah(total);

                    // Update hidden fields
                    document.getElementById('hidden_total_transfer').value = total;
                    document.getElementById('hidden_kode_promo').value = kode;
                    document.getElementById('hidden_diskon_nominal').value = diskonNominal;

                    feedback.innerHTML = '<span style="color:#27ae60;"><i class="fa-solid fa-circle-check"></i> ' + data.message + '</span>';
                } else {
                    // Reset diskon
                    diskonNominal = 0;
                    document.getElementById('row_diskon').style.display = 'none';
                    document.getElementById('label_total_transfer').textContent = formatRupiah(hitungTotal(0));
                    document.getElementById('hidden_total_transfer').value = hitungTotal(0);
                    document.getElementById('hidden_kode_promo').value = '';
                    document.getElementById('hidden_diskon_nominal').value = 0;

                    feedback.innerHTML = '<span style="color:#e74c3c;"><i class="fa-solid fa-circle-xmark"></i> ' + data.message + '</span>';
                }
            })
            .catch(() => {
                feedback.innerHTML = '<span style="color:#e74c3c;">Terjadi kesalahan. Coba lagi.</span>';
            });
    });

    // Terapkan juga saat tekan Enter
    document.getElementById('input_kode_promo').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('btn_terapkan_promo').click();
        }
    });
})();
</script>