<?php
// Pastikan browser & search engine tahu ini benar-benar halaman error, bukan halaman valid
http_response_code(404);

$active_page = '';
$page_title = 'Halaman Tidak Ditemukan';
$page_description = 'Halaman yang Anda cari tidak ditemukan di LexPina.';

include 'header.php';
include 'navbar.php';
?>

    <main>
        <section class="about-section">
            <div class="about-container">
                <div class="empty-state-box" style="padding: 80px 20px;">
                    <i class="fa-solid fa-magnifying-glass empty-state-icon" style="font-size: 60px;"></i>
                    <h1 style="font-size: 48px; margin-bottom: 10px; color: #222;">404</h1>
                    <p class="empty-state-text" style="font-size: 18px;">Halaman yang Anda cari tidak ditemukan atau sudah dipindahkan.</p>
                    <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                        <a href="index" class="btn-save-profile btn-link-action">
                            <i class="fa-solid fa-house"></i> Kembali ke Beranda
                        </a>
                        <a href="database" class="btn-save-profile btn-link-action">
                            <i class="fa-solid fa-book"></i> Cari Dokumen Hukum
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

<?php include 'footer.php'; ?>
