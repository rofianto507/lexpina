<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/configuration.php';

$active_page = 'berita';

// ==========================================
// FUNGSI BANTUAN: Format Tanggal Indonesia
// ==========================================
if (!function_exists('tgl_indo')) {
    function tgl_indo($tanggal){
        $bulan = array (1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
        if(empty($tanggal)) return '-';
        $pecahkan = explode('-', date('Y-m-d', strtotime($tanggal)));
        return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
    }
}

// ==========================================
// KONFIGURASI PAGINATION (NAVIGASI HALAMAN)
// ==========================================
$batas_per_halaman = 5; // Tentukan mau tampil berapa berita per halaman
$halaman_aktif = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($halaman_aktif < 1) $halaman_aktif = 1;
$offset = ($halaman_aktif - 1) * $batas_per_halaman;

// ==========================================
// QUERY DATABASE
// ==========================================
try {
    // 1. Hitung total seluruh berita aktif untuk Pagination
    $stmt_total = $pdo->prepare("SELECT COUNT(*) FROM beritas WHERE status = 1");
    $stmt_total->execute();
    $total_berita = $stmt_total->fetchColumn();
    $total_halaman = ceil($total_berita / $batas_per_halaman);

    // 2. Ambil Berita Terbaru (Dibatasi oleh LIMIT dan OFFSET)
    $stmt_news = $pdo->prepare("
        SELECT b.*, k.nama_kategori, k.slug_kategori,
        (SELECT COUNT(*) FROM komentars WHERE berita_id = b.id AND status = 1) as total_komentar
        FROM beritas b
        JOIN kategoris k ON b.kategori_id = k.id
        WHERE b.status = 1
        ORDER BY b.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    // Bind value dengan tipe INT (Wajib untuk klausa LIMIT di PDO)
    $stmt_news->bindValue(':limit', $batas_per_halaman, PDO::PARAM_INT);
    $stmt_news->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt_news->execute();
    $beritas = $stmt_news->fetchAll();

    // 3. Ambil Daftar Kategori untuk Sidebar
    $stmt_cats = $pdo->prepare("
        SELECT k.*, (SELECT COUNT(*) FROM beritas WHERE kategori_id = k.id AND status = 1) as jumlah_berita
        FROM kategoris k WHERE status = 1
    ");
    $stmt_cats->execute();
    $kategoris = $stmt_cats->fetchAll();

    // 4. Ambil Berita Terpopuler untuk Sidebar
    $stmt_pop = $pdo->prepare("SELECT judul, views, slug FROM beritas WHERE status = 1 ORDER BY views DESC LIMIT 3");
    $stmt_pop->execute();
    $populer = $stmt_pop->fetchAll();

} catch (PDOException $e) {
    die("Gagal memuat data: " . $e->getMessage());
}

include 'header.php'; 
include 'navbar.php'; 
?>

    <main class="news-page">
        <div class="news-container">
            
            <div class="news-main-column">
                <h2 class="section-title">Berita Terbaru</h2>

                <?php if (count($beritas) > 0): ?>
                    <?php foreach($beritas as $row): ?>
                    <article class="news-card">
                        <div class="news-img">
                            <img src="public/upload/news/<?php echo $row['gambar']; ?>" alt="<?php echo htmlspecialchars($row['judul']); ?>">
                        </div>
                        <div class="news-content">
                            <div class="news-meta">
                                <span><i class="fa-regular fa-calendar"></i> <?php echo tgl_indo($row['created_at']); ?></span>
                                <span><i class="fa-solid fa-eye"></i> <?php echo number_format($row['views'], 0, ',', '.'); ?> Views</span>
                                <span><i class="fa-regular fa-comments"></i> <?php echo $row['total_komentar']; ?> Komentar</span>
                            </div>
                            <h3 class="news-title">
                                <a href="berita_detail.php?slug=<?php echo $row['slug']; ?>">
                                    <?php echo htmlspecialchars($row['judul']); ?>
                                </a>
                            </h3>
                            <p class="news-excerpt">
                                <?php echo substr(strip_tags($row['konten']), 0, 160); ?>...
                            </p>
                            <a href="berita_detail.php?slug=<?php echo $row['slug']; ?>" class="read-more">Baca Selengkapnya &raquo;</a>
                        </div>
                    </article>
                    <?php endforeach; ?>

                    <?php if ($total_halaman > 1): ?>
                    <div class="pagination">
                        
                        <?php if ($halaman_aktif > 1): ?>
                            <a href="berita.php?page=<?php echo $halaman_aktif - 1; ?>" class="page-btn prev"><i class="fa-solid fa-chevron-left"></i> Prev</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                            <a href="berita.php?page=<?php echo $i; ?>" class="page-num <?php echo ($i == $halaman_aktif) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($halaman_aktif < $total_halaman): ?>
                            <a href="berita.php?page=<?php echo $halaman_aktif + 1; ?>" class="page-btn next">Next <i class="fa-solid fa-chevron-right"></i></a>
                        <?php endif; ?>
                        
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <p style="text-align:center; padding:50px; color:#888;">Belum ada berita yang diterbitkan.</p>
                <?php endif; ?>

            </div>

            <aside class="news-sidebar">
                
                <div class="sidebar-widget">
                    <h3 class="widget-title">Kategori</h3>
                    <ul class="category-list">
                        <?php foreach($kategoris as $cat): ?>
                        <li><a href="berita_kategori.php?slug=<?php echo $cat['slug_kategori']; ?>"><?php echo $cat['nama_kategori']; ?> <span>(<?php echo $cat['jumlah_berita']; ?>)</span></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="sidebar-widget">
                    <h3 class="widget-title">Terpopuler</h3>
                    <div class="popular-list">
                        <?php 
                        $no = 1;
                        foreach($populer as $pop): ?>
                        <div class="popular-item">
                            <div class="pop-number"><?php echo $no++; ?></div>
                            <div class="pop-text">
                                <h4><a href="berita_detail.php?slug=<?php echo $pop['slug']; ?>"><?php echo htmlspecialchars($pop['judul']); ?></a></h4>
                                <span><i class="fa-solid fa-eye"></i> <?php echo number_format($pop['views'], 0, ',', '.'); ?> Views</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </aside>
        </div>
    </main>

<?php include 'footer.php'; ?>