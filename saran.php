<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/configuration.php';

$active_page = ''; 

include 'header.php'; 
include 'navbar.php'; 
$req_judul = isset($_GET['req']) ? 'Request Dokumen: ' . htmlspecialchars($_GET['req']) : '';
?>

    <main class="saran-page">
        <div class="saran-wrapper">
            
            <div class="saran-header">
                <i class="fa-solid fa-envelope-open-text saran-icon"></i>
                <h1 class="saran-title">Layanan Pengaduan & Saran</h1>
                <p class="saran-subtitle">Bantu kami menjadikan LexPina lebih baik. Kami sangat menghargai setiap ide, masukan, maupun laporan kendala dari Anda.</p>
            </div>

            <?php if(isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
                <div class="saran-alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <div>
                        <strong>Terima kasih!</strong> Saran dan masukan Anda berhasil dikirim dan akan segera ditinjau oleh tim kami.
                    </div>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['user_id'])): ?>
                <form action="proses_saran.php" method="POST" class="saran-form">
                    
                    <div class="form-group">
                        <label>Judul / Topik Pembahasan</label>
                        <input type="text" name="judul" class="form-control" required placeholder="Contoh: Request penambahan UU Hak Cipta terbaru" value="<?php echo $req_judul; ?>">
                    </div>

                    <div class="form-group form-group-textarea">
                        <label>Detail Saran atau Masukan</label>
                        <textarea name="konten" class="form-control" required rows="8" placeholder="Tuliskan detail masukan, ide fitur, atau laporan masalah yang Anda temukan di sini..."></textarea>
                    </div>

                    <button type="submit" class="btn-saran-submit">
                        <i class="fa-solid fa-paper-plane"></i> Kirim Pesan Sekarang
                    </button>
                    
                </form>
            <?php else: ?>
                <div class="saran-login-box">
                    <i class="fa-solid fa-lock login-lock-icon"></i>
                    <h3>Autentikasi Diperlukan</h3>
                    <p>Untuk menjaga keamanan dan kualitas masukan, Anda harus masuk ke akun LexPina terlebih dahulu sebelum dapat mengisi formulir ini.</p>
                    
                    <button type="button" class="btn-saran-login" onclick="document.getElementById('btnOpenLogin').click();">
                        <i class="fa-solid fa-right-to-bracket"></i> Login Sekarang
                    </button>
                </div>
            <?php endif; ?>

        </div>
    </main>

<?php include 'footer.php'; ?>