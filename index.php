<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/configuration.php';

$active_page = 'beranda';

// ==========================================
// 1. QUERY UNTUK SLIDER DISARANKAN
// ==========================================
try {
    $stmt_rec = $pdo->prepare("SELECT id, judul, kategori FROM `databases` WHERE status = 1 AND rekomendasi = 1 ORDER BY created_at DESC LIMIT 10");
    $stmt_rec->execute();
    $slider_docs = $stmt_rec->fetchAll();
} catch (PDOException $e) {
    $slider_docs = [];
}

// ==========================================
// 2. QUERY UNTUK STAT-SECTION (PENGHITUNG OTOMATIS)
// ==========================================
function countKategori($pdo, $kategori) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `databases` WHERE kategori = ? AND status = 1");
    $stmt->execute([$kategori]);
    return $stmt->fetchColumn();
}

$count_peraturan = countKategori($pdo, 'peraturan');
$count_putusan    = countKategori($pdo, 'putusan');
// Untuk Perjanjian dan Karya Ilmiah, jika belum ada di DB, kita beri nilai default atau 0
$count_perjanjian = countKategori($pdo, 'perjanjian'); 
$count_ilmiah     = countKategori($pdo, 'karya-ilmiah');

include 'header.php'; 
include 'navbar.php'; 
?>

    <main>
        <section class="search-section">
            <h2 class="quote">“No man is above the law, and no man is below it”<br>- Theodore Roosevelt</h2>
            
            <form action="database.php" method="GET" class="search-container" style="position: relative;">
                
                <input type="text" name="search" id="searchInput" placeholder="Cari peraturan atau dokumen hukum..." autocomplete="off">
                
                <button type="button" id="clearSearchBtn" class="btn-clear" style="display: none;">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <button type="submit" class="btn-submit"><i class="fa-solid fa-magnifying-glass"></i></button>

                <div id="searchResults" class="live-search-results" style="display: none;"></div>
            </form>
        </section>

        <section class="recommended-section">
            <h3>Disarankan</h3>
            <div class="carousel-wrapper">
                <button class="nav-btn prev-btn"><i class="fa-solid fa-chevron-left"></i></button>
                <div class="card-container" id="cardContainer">
                    
                    <?php if (count($slider_docs) > 0): ?>
                        <?php foreach($slider_docs as $sdoc): 
                            $cat_name = ucwords(str_replace('-', ' ', $sdoc['kategori']));
                        ?>
                            <div class="card" onclick="location.href='database_detail.php?id=<?php echo $sdoc['id']; ?>&kategori=<?php echo $sdoc['kategori']; ?>'" style="cursor: pointer;">
                                <span class="card-category"><?php echo $cat_name; ?></span>
                                <p><?php echo htmlspecialchars($sdoc['judul']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card"><p>Belum ada dokumen disarankan.</p></div>
                    <?php endif; ?>

                </div>
                <button class="nav-btn next-btn"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </section>

        <section class="stats-section">
            <h2>SMARTER, MORE EFFICIENT,<br>AND ALWAYS POWERFUL</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <h3>Peraturan</h3>
                    <p><?php echo number_format($count_peraturan, 0, ',', '.'); ?></p>
                </div>
                <div class="stat-item">
                    <h3>Perjanjian</h3>
                    <p><?php echo number_format($count_perjanjian, 0, ',', '.'); ?></p>
                </div>
                <div class="stat-item">
                    <h3>Putusan</h3>
                    <p><?php echo number_format($count_putusan, 0, ',', '.'); ?></p>
                </div>
                <div class="stat-item">
                    <h3>Karya Ilmiah</h3>
                    <p><?php echo number_format($count_ilmiah, 0, ',', '.'); ?></p>
                </div>
            </div>
            <button class="btn-tentang" onclick="location.href='tentang.php'">TENTANG KAMI</button>
        </section>
        
        <?php
            $stmt_banner = $pdo->query("SELECT * FROM banners WHERE id = 1 LIMIT 1");
            $banner = $stmt_banner->fetch(PDO::FETCH_ASSOC);
            
            // Jika banner aktif dan gambar tersedia
            if($banner && $banner['is_active'] == 1 && !empty($banner['gambar'])):
        ?>
        
      <div id="promoBannerModal" class="custom-promo-overlay">
            <div class="custom-promo-content">
                <button id="closePromoBtn" class="custom-promo-close">&times;</button>
                
                <?php if(!empty($banner['link_url']) && $banner['link_url'] != '#'): ?>
                    <a href="<?php echo htmlspecialchars($banner['link_url']); ?>">
                        <img src="public/upload/banners/<?php echo htmlspecialchars($banner['gambar']); ?>" class="custom-promo-img" alt="<?php echo htmlspecialchars($banner['judul']); ?>">
                    </a>
                <?php else: ?>
                    <img src="public/upload/banners/<?php echo htmlspecialchars($banner['gambar']); ?>" class="custom-promo-img" alt="<?php echo htmlspecialchars($banner['judul']); ?>">
                <?php endif; ?>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
        var bannerVersion = <?php echo $banner['versi']; ?>;
    var modal = document.getElementById('promoBannerModal');
    var closeBtn = document.getElementById('closePromoBtn');
    
    // Cek apakah user sudah melihat versi banner ini
    if(localStorage.getItem('lexpina_banner_seen') != bannerVersion) {
        
        // Tunda 1 detik sebelum memunculkan popup agar lebih natural
        setTimeout(function(){
            modal.classList.add('show-modal');
        }, 1000); 
        
        // Fungsi untuk menutup modal dan menyimpan cookie/localStorage
        function closeModal() {
            modal.classList.remove('show-modal');
            setTimeout(function() { modal.style.display = 'none'; }, 400); // Tunggu animasi pudar selesai
            localStorage.setItem('lexpina_banner_seen', bannerVersion);
        }

        // Tutup saat tombol X ditekan
        closeBtn.addEventListener('click', closeModal);

        // Tutup saat area hitam di luar gambar ditekan
        modal.addEventListener('click', function(e) {
            if(e.target === modal) {
                closeModal();
            }
        });
    }
});
        </script>

        <?php endif; ?>
        
    </main>

<?php 
include 'footer.php'; 
?>