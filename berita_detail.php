<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/configuration.php';

$active_page = 'berita';

// 1. Tangkap slug dari URL
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if(empty($slug)) {
    header("Location: berita");
    exit();
}

if (!function_exists('tgl_indo')) {
    function tgl_indo($tanggal){
        $bulan = array (1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
        if(empty($tanggal)) return '-';
        $pecahkan = explode('-', date('Y-m-d', strtotime($tanggal)));
        return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
    }
}

try {
    // A. Tarik Detail Berita + Nama Kategori
    $stmt = $pdo->prepare("SELECT b.*, k.nama_kategori, k.slug_kategori FROM beritas b JOIN kategoris k ON b.kategori_id = k.id WHERE b.slug = ? AND b.status = 1");
    $stmt->execute([$slug]);
    $berita = $stmt->fetch();

    if (!$berita) {
        echo "<script>alert('Berita tidak ditemukan!'); window.location.href='berita';</script>";
        exit();
    }

    // B. UPDATE VIEWS
    $pdo->prepare("UPDATE beritas SET views = views + 1 WHERE id = ?")->execute([$berita['id']]);
    $berita['views'] += 1; 

    // C. Hitung Total Komentar
    $stmt_komen = $pdo->prepare("SELECT COUNT(*) FROM komentars WHERE berita_id = ? AND status = 1");
    $stmt_komen->execute([$berita['id']]);
    $total_komentar = $stmt_komen->fetchColumn();

    // ==========================================
    // D. LOGIKA PAGINATION KOMENTAR
    // ==========================================
    $batas_komentar = 5; // Tampilkan 5 komentar per halaman
    $halaman_komentar = isset($_GET['cp']) ? (int)$_GET['cp'] : 1;
    if ($halaman_komentar < 1) $halaman_komentar = 1;
    $offset_komentar = ($halaman_komentar - 1) * $batas_komentar;
    $total_halaman_komentar = ceil($total_komentar / $batas_komentar);

    // E. Tarik Daftar Komentar (Sesuai Limit & Offset)
// E. Tarik Daftar Komentar (Sesuai Limit & Offset) + JOIN dengan tabel users
    $stmt_list_komen = $pdo->prepare("
        SELECT k.*, u.nama, u.foto 
        FROM komentars k
        JOIN users u ON k.user_id = u.id
        WHERE k.berita_id = :berita_id AND k.status = 1 
        ORDER BY k.created_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt_list_komen->bindValue(':berita_id', $berita['id'], PDO::PARAM_INT);
    $stmt_list_komen->bindValue(':limit', $batas_komentar, PDO::PARAM_INT);
    $stmt_list_komen->bindValue(':offset', $offset_komentar, PDO::PARAM_INT);
    $stmt_list_komen->execute();
    $daftar_komentar = $stmt_list_komen->fetchAll();

    // F. Tarik Sidebar
    $stmt_cats = $pdo->prepare("SELECT k.*, (SELECT COUNT(*) FROM beritas WHERE kategori_id = k.id AND status = 1) as jumlah_berita FROM kategoris k WHERE status = 1");
    $stmt_cats->execute();
    $kategoris = $stmt_cats->fetchAll();

    $stmt_pop = $pdo->prepare("SELECT judul, views, slug FROM beritas WHERE status = 1 ORDER BY views DESC LIMIT 3");
    $stmt_pop->execute();
    $populer = $stmt_pop->fetchAll();

} catch (PDOException $e) {
    die("Error memuat berita: " . $e->getMessage());
}

// URL selalu bentuk bersih (berita-detail), meski halaman diakses lewat URL lama berita_detail.php
$current_url = $path . 'berita-detail?slug=' . urlencode($slug);
$share_fb = "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($current_url);
$share_x  = "https://twitter.com/intent/tweet?url=" . urlencode($current_url) . "&text=" . urlencode($berita['judul']);
$share_wa = "https://api.whatsapp.com/send?text=" . urlencode($berita['judul'] . " - Baca selengkapnya di: " . $current_url);

// ==========================================
// SEO: title & meta description dinamis (dipakai oleh header.php)
// ==========================================
$page_title = $berita['judul'];
$meta_desc_raw = trim(strip_tags($berita['konten']));
$page_description = mb_strlen($meta_desc_raw) > 160 ? mb_substr($meta_desc_raw, 0, 157) . '...' : $meta_desc_raw;
$page_canonical = $current_url;
if (!empty($berita['gambar'])) {
    $page_og_image = $path . 'public/upload/news/' . $berita['gambar'];
}

include 'header.php';
include 'navbar.php';

// ==========================================
// SEO: Structured data (JSON-LD) - Article + Breadcrumb
// ==========================================
$ld_article = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $berita['judul'],
    'description' => $page_description,
    'url' => $current_url,
    'datePublished' => date('c', strtotime($berita['created_at'])),
    'dateModified' => date('c', strtotime($berita['updated_at'] ?: $berita['created_at'])),
    'author' => ['@type' => 'Organization', 'name' => $app_name],
    'publisher' => [
        '@type' => 'Organization',
        'name' => $app_name,
        'logo' => ['@type' => 'ImageObject', 'url' => $path . 'assets/img/logo.png'],
    ],
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $current_url],
];
if (!empty($berita['gambar'])) {
    $ld_article['image'] = $path . 'public/upload/news/' . $berita['gambar'];
}

$ld_breadcrumb = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => $path . 'index'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Berita', 'item' => $path . 'berita'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $berita['nama_kategori'], 'item' => $path . 'berita-kategori?slug=' . $berita['slug_kategori']],
        ['@type' => 'ListItem', 'position' => 4, 'name' => $berita['judul'], 'item' => $current_url],
    ],
];
?>
<script type="application/ld+json"><?php echo json_encode($ld_article, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
<script type="application/ld+json"><?php echo json_encode($ld_breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>

    <main class="news-page">
        <div class="news-container">
            <div class="news-main-column">
                
                <nav class="breadcrumb">
                    <a href="index">Beranda</a> &raquo;
                    <a href="berita">Berita</a> &raquo;
                    <a href="berita-kategori?slug=<?php echo $berita['slug_kategori']; ?>"><?php echo htmlspecialchars($berita['nama_kategori']); ?></a> &raquo; 
                    <span>Detail</span>
                </nav>

                <article class="single-article">
                    <h1 class="single-title"><?php echo htmlspecialchars($berita['judul']); ?></h1>
                    
                    <div class="single-meta">
                        <span><i class="fa-regular fa-calendar"></i> <?php echo tgl_indo($berita['created_at']); ?></span>
                        <span><i class="fa-solid fa-eye"></i> <?php echo number_format($berita['views'], 0, ',', '.'); ?> Views</span>
                        <span><i class="fa-solid fa-comments"></i> <?php echo $total_komentar; ?> Komentar</span>
                    </div>

                    <?php if(!empty($berita['gambar'])): ?>
                        <img src="public/upload/news/<?php echo htmlspecialchars($berita['gambar']); ?>" alt="<?php echo htmlspecialchars($berita['judul']); ?>" class="single-img">
                    <?php endif; ?>

                    <div class="single-content">
                        <p><?php echo nl2br(htmlspecialchars($berita['konten'])); ?></p>
                    </div>

                    <div class="single-footer">
                        <div class="article-share">
                            <strong>Bagikan:</strong>
                            <a href="<?php echo $share_fb; ?>" target="_blank" class="share-btn fb"><i class="fa-brands fa-facebook-f"></i></a>
                            <a href="<?php echo $share_x; ?>" target="_blank" class="share-btn x"><i class="fa-brands fa-x"></i></a>
                            <a href="<?php echo $share_wa; ?>" target="_blank" class="share-btn wa"><i class="fa-brands fa-whatsapp"></i></a>
                        </div>
                    </div>
                </article>
                <div class="comment-form-container">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <h3>Tinggalkan Komentar</h3>
                        <form action="proses_komentar.php" method="POST" class="comment-form">
                            <input type="hidden" name="berita_id" value="<?php echo $berita['id']; ?>">
                            <input type="hidden" name="slug" value="<?php echo $slug; ?>">

                            <div class="user-typing">
                                <div class="avatar-small">
                                    <?php if(!empty($_SESSION['foto'])): ?>
                                        <img src="<?php echo $_SESSION['foto']; ?>" referrerpolicy="no-referrer" alt="Foto Profil">
                                    <?php else: ?>
                                        <span><?php echo strtoupper(substr($_SESSION['user_nama'], 0, 1)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="user-info-text">
                                    Berkomentar sebagai <strong><?php echo htmlspecialchars($_SESSION['user_nama']); ?></strong>
                                </div>
                            </div>

                            <textarea name="isi_komentar" placeholder="Tulis pendapat Anda di sini..." required></textarea>
                            <button type="submit" class="btn-submit-comment">
                                <i class="fa-solid fa-paper-plane"></i> Kirim Komentar
                            </button>
                        </form>
                    <?php else: ?>
                       <div class="login-to-comment">
                            <i class="fa-solid fa-lock"></i>
                            <p>Anda harus masuk terlebih dahulu untuk dapat memberikan komentar.</p>
                            
                            <button type="button" class="btn-login-redirect" onclick="document.getElementById('btnOpenLogin').click();" style="border:none; cursor:pointer;">
                                Login Sekarang
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="comments-section" id="kolom-komentar">
                    <h3>Komentar (<?php echo $total_komentar; ?>)</h3>

                    <?php if($total_komentar > 0): ?>
                        <div class="comment-list">
                            <?php foreach($daftar_komentar as $komen): ?>
                            <div class="comment-item">
                                
                                <div class="comment-avatar">
                                    <?php if(!empty($komen['foto'])): ?>
                                        <img src="<?php echo htmlspecialchars($komen['foto']); ?>" alt="Foto" referrerpolicy="no-referrer" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr(trim($komen['nama']), 0, 1)); ?>
                                    <?php endif; ?>
                                </div>

                                <div class="comment-body">
                                    <div class="comment-meta">
                                        <strong><?php echo htmlspecialchars($komen['nama']); ?></strong>
                                        <span><i class="fa-regular fa-clock"></i> <?php echo tgl_indo($komen['created_at']); ?></span>
                                    </div>
                                    <p><?php echo nl2br(htmlspecialchars($komen['isi_komentar'])); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($total_halaman_komentar > 1): ?>
                        <div class="pagination comment-pagination">
                            <?php if ($halaman_komentar > 1): ?>
                                <a href="berita-detail?slug=<?php echo urlencode($slug); ?>&cp=<?php echo $halaman_komentar - 1; ?>#kolom-komentar" class="page-btn prev"><i class="fa-solid fa-chevron-left"></i> Prev</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_halaman_komentar; $i++): ?>
                                <a href="berita-detail?slug=<?php echo urlencode($slug); ?>&cp=<?php echo $i; ?>#kolom-komentar" class="page-num <?php echo ($i == $halaman_komentar) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($halaman_komentar < $total_halaman_komentar): ?>
                                <a href="berita-detail?slug=<?php echo urlencode($slug); ?>&cp=<?php echo $halaman_komentar + 1; ?>#kolom-komentar" class="page-btn next">Next <i class="fa-solid fa-chevron-right"></i></a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="empty-comment">
                            <i class="fa-regular fa-comments"></i>
                            <p>Belum ada komentar. Jadilah yang pertama memberikan tanggapan!</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <aside class="news-sidebar">
                <div class="sidebar-widget">
                    <h3 class="widget-title">Kategori</h3>
                    <ul class="category-list">
                        <?php foreach($kategoris as $cat): ?>
                        <li><a href="berita-kategori?slug=<?php echo $cat['slug_kategori']; ?>"><?php echo $cat['nama_kategori']; ?> <span>(<?php echo $cat['jumlah_berita']; ?>)</span></a></li>
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
                                <h4><a href="berita-detail?slug=<?php echo $pop['slug']; ?>"><?php echo htmlspecialchars($pop['judul']); ?></a></h4>
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