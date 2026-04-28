<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/configuration.php';

// Set menu active
$active_page = 'berita';

// ==========================================
// 1. TANGKAP & VALIDASI SLUG KATEGORI
// ==========================================
$slug_kategori = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if(empty($slug_kategori)) {
    header("Location: berita.php");
    exit();
}

try {
    // Cek apakah kategori dengan slug ini benar-benar ada di database
    $stmt_cat_info = $pdo->prepare("SELECT id, nama_kategori FROM kategoris WHERE slug_kategori = ? AND status = 1");
    $stmt_cat_info->execute([$slug_kategori]);
    $info_kategori = $stmt_cat_info->fetch();

    // Jika user iseng mengetik URL kategori yang salah/tidak ada
    if(!$info_kategori) {
        echo "<script>alert('Kategori tidak ditemukan!'); window.location.href='berita.php';</script>";
        exit();
    }

    $id_kategori = $info_kategori['id'];
    $nama_kategori_aktif = $info_kategori['nama_kategori'];

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
    // KONFIGURASI PAGINATION
    // ==========================================
    $batas_per_halaman = 5;
    $halaman_aktif = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($halaman_aktif < 1) $halaman_aktif = 1;
    $offset = ($halaman_aktif - 1) * $batas_per_halaman;

    // Hitung total berita KHUSUS untuk kategori ini
    $stmt_total = $pdo->prepare("SELECT COUNT(*) FROM beritas WHERE kategori_id = ? AND status = 1");
    $stmt_total->execute([$id_kategori]);
    $total_berita = $stmt_total->fetchColumn();
    $total_halaman = ceil($total_berita / $batas_per_halaman);

    // ==========================================
    // QUERY DATABASE UTAMA
    // ==========================================
    
    // A. Tarik Berita Sesuai Kategori
    $stmt_news = $pdo->prepare("
        SELECT b.*, k.nama_kategori, k.slug_kategori,
        (SELECT COUNT(*) FROM komentars WHERE berita_id = b.id AND status = 1) as total_komentar
        FROM beritas b
        JOIN kategoris k ON b.kategori_id = k.id
        WHERE b.kategori_id = :cat_id AND b.status = 1
        ORDER BY b.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt_news->bindValue(':cat_id', $id_kategori, PDO::PARAM_INT);
    $stmt_news->bindValue(':limit', $batas_per_halaman, PDO::PARAM_INT);
    $stmt_news->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt_news->execute();
    $beritas = $stmt_news->fetchAll();

    // B. Tarik Daftar Kategori untuk Sidebar
    $stmt_cats = $pdo->prepare("
        SELECT k.*, (SELECT COUNT(*) FROM beritas WHERE kategori_id = k.id AND status = 1) as jumlah_berita
        FROM kategoris k WHERE status = 1
    ");
    $stmt_cats->execute();
    $kategoris = $stmt_cats->fetchAll();

    // C. Tarik Berita Terpopuler untuk Sidebar
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
                
                <nav class="breadcrumb" style="margin-bottom: 20px;">
                    <a href="index.php">Beranda</a> &raquo; 
                    <a href="berita.php">Berita</a> &raquo; 
                    <span>Kategori: <?php echo htmlspecialchars($nama_kategori_aktif); ?></span>
                </nav>

                <h2 class="section-title">Kategori: <?php echo htmlspecialchars($nama_kategori_aktif); ?></h2>

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
                            <a href="berita_kategori.php?slug=<?php echo urlencode($slug_kategori); ?>&page=<?php echo $halaman_aktif - 1; ?>" class="page-btn prev"><i class="fa-solid fa-chevron-left"></i> Prev</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                            <a href="berita_kategori.php?slug=<?php echo urlencode($slug_kategori); ?>&page=<?php echo $i; ?>" class="page-num <?php echo ($i == $halaman_aktif) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($halaman_aktif < $total_halaman): ?>
                            <a href="berita_kategori.php?slug=<?php echo urlencode($slug_kategori); ?>&page=<?php echo $halaman_aktif + 1; ?>" class="page-btn next">Next <i class="fa-solid fa-chevron-right"></i></a>
                        <?php endif; ?>
                        
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div style="padding: 50px; text-align: center; background: #fff; border-radius: 8px; border: 1px dashed #ccc;">
                        <i class="fa-regular fa-folder-open" style="font-size: 40px; color: #ddd; margin-bottom: 15px;"></i>
                        <p style="color: #666;">Belum ada berita yang diterbitkan di kategori <strong><?php echo htmlspecialchars($nama_kategori_aktif); ?></strong>.</p>
                        <a href="berita.php" style="display: inline-block; margin-top: 15px; color: #d4ac0d; text-decoration: underline;">Kembali ke Semua Berita</a>
                    </div>
                <?php endif; ?>

            </div>

            <aside class="news-sidebar">
                
                <div class="sidebar-widget">
                    <h3 class="widget-title">Kategori</h3>
                    <ul class="category-list">
                        <?php foreach($kategoris as $cat): ?>
                        <li>
                            <a href="berita_kategori.php?slug=<?php echo $cat['slug_kategori']; ?>" style="<?php echo ($cat['slug_kategori'] == $slug_kategori) ? 'font-weight: bold; color: #d4ac0d;' : ''; ?>">
                                <?php echo $cat['nama_kategori']; ?> <span>(<?php echo $cat['jumlah_berita']; ?>)</span>
                            </a>
                        </li>
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