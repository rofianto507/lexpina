<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/configuration.php';

// 1. Tangkap parameter dari URL
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : 'peraturan';
$search   = isset($_GET['search']) ? trim($_GET['search']) : '';

// Trik UX: Jadikan kategori dari sidebar sebagai default dropdown filter, 
// KECUALI user datang dari pencarian beranda (maka defaultnya 'all')
if (isset($_GET['kategori_filter'])) {
    $filter_kategori = $_GET['kategori_filter'];
} else {
    $filter_kategori = isset($_GET['search']) ? 'all' : $kategori;
}

$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'terbaru';

// 2. Ambil daftar kategori unik dari database untuk mengisi dropdown secara dinamis
try {
    $stmt_cats = $pdo->query("SELECT DISTINCT kategori FROM `databases` WHERE status = 1");
    $kategori_list = $stmt_cats->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $kategori_list = ['peraturan', 'putusan', 'monografi', 'artikel']; // Fallback
}

// 3. Set menu active
$active_page = 'database';

// ==========================================
// FUNGSI BANTUAN: Format Tanggal Indonesia
// ==========================================
if (!function_exists('tgl_indo')) {
    function tgl_indo($tanggal){
        $bulan = array (1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
        if(empty($tanggal) || $tanggal == '0000-00-00') return '-';
        $pecahkan = explode('-', date('Y-m-d', strtotime($tanggal)));
        return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
    }
}

// ==========================================
// KONFIGURASI PAGINATION & URL PARAMS
// ==========================================
$batas_per_halaman = 5; 
$halaman_aktif = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($halaman_aktif < 1) $halaman_aktif = 1;
$offset = ($halaman_aktif - 1) * $batas_per_halaman;

// Siapkan parameter URL tambahan agar tidak hilang saat pindah halaman
$url_params = '';
if ($search !== '') $url_params .= '&search=' . urlencode($search);
if ($filter_kategori !== 'all') $url_params .= '&kategori_filter=' . urlencode($filter_kategori);
if ($sort_by !== 'terbaru') $url_params .= '&sort_by=' . urlencode($sort_by);
if ($search === '' && $filter_kategori === 'all') $url_params .= '&kategori=' . urlencode($kategori);

// ==========================================
// PENENTUAN URUTAN (ORDER BY)
// ==========================================
$sql_order = "ORDER BY tanggal_penetapan DESC"; // Default
if ($sort_by === 'abjad_asc') {
    $sql_order = "ORDER BY judul ASC";
} elseif ($sort_by === 'abjad_desc') {
    $sql_order = "ORDER BY judul DESC";
} elseif ($sort_by === 'terpopuler') {
    $sql_order = "ORDER BY views DESC";
}

// ==========================================
// QUERY DATABASE DINAMIS
// ==========================================
try {
    if ($search !== '') {
        $title_display = 'Hasil Pencarian';
        
        // Racik klausa WHERE
        $sql_where = "WHERE status = 1 AND (judul LIKE :search1 OR deskripsi LIKE :search2)";
        if ($filter_kategori !== 'all') {
            $sql_where .= " AND kategori = :filter_kat";
        }

        // A. Hitung total data
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM `databases` $sql_where");
        $stmt_count->bindValue(':search1', "%$search%", PDO::PARAM_STR);
        $stmt_count->bindValue(':search2', "%$search%", PDO::PARAM_STR);
        if ($filter_kategori !== 'all') $stmt_count->bindValue(':filter_kat', $filter_kategori, PDO::PARAM_STR);
        $stmt_count->execute();
        $total_data = $stmt_count->fetchColumn();

        // B. Tarik data utama
        $stmt_docs = $pdo->prepare("SELECT `databases`.*, (SELECT COUNT(*) FROM likes WHERE likes.dokumen_id = `databases`.id) as total_likes FROM `databases` $sql_where $sql_order LIMIT :limit OFFSET :offset");
        $stmt_docs->bindValue(':search1', "%$search%", PDO::PARAM_STR);
        $stmt_docs->bindValue(':search2', "%$search%", PDO::PARAM_STR);
        if ($filter_kategori !== 'all') $stmt_docs->bindValue(':filter_kat', $filter_kategori, PDO::PARAM_STR);
        
    } else {
        // Jika pencarian kosong tapi filter kategori dipilih, timpa kategori yang aktif
        if ($filter_kategori !== 'all') {
            $kategori = $filter_kategori;
        }
        
        $title_display = 'Database: ' . ucwords(str_replace('-', ' ', $kategori));
        
        // A. Hitung total data
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM `databases` WHERE kategori = :kategori AND status = 1");
        $stmt_count->execute([':kategori' => $kategori]);
        $total_data = $stmt_count->fetchColumn();

        // B. Tarik data utama
        $stmt_docs = $pdo->prepare("SELECT `databases`.*, (SELECT COUNT(*) FROM likes WHERE likes.dokumen_id = `databases`.id) as total_likes FROM `databases` WHERE kategori = :kategori AND status = 1 $sql_order LIMIT :limit OFFSET :offset");
        $stmt_docs->bindValue(':kategori', $kategori, PDO::PARAM_STR);
    }
    
    $stmt_docs->bindValue(':limit', $batas_per_halaman, PDO::PARAM_INT);
    $stmt_docs->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt_docs->execute();
    
    $dokumen_list = $stmt_docs->fetchAll();
    $total_halaman = ceil($total_data / $batas_per_halaman);

} catch (PDOException $e) {
    die("Error mengambil dokumen: " . $e->getMessage());
}

// Sidebar: Tarik 3 Dokumen Terpopuler & Rekomendasi
$stmt_pop = $pdo->prepare("SELECT id, judul, views, kategori FROM `databases` WHERE status = 1 ORDER BY views DESC LIMIT 3");
$stmt_pop->execute();
$populer_list = $stmt_pop->fetchAll();

$stmt_rec = $pdo->prepare("SELECT id, judul, deskripsi, kategori FROM `databases` WHERE status = 1 AND rekomendasi = 1 ORDER BY created_at DESC LIMIT 3");
$stmt_rec->execute();
$rekomendasi_list = $stmt_rec->fetchAll();

include 'header.php'; 
include 'navbar.php'; 
?>

    <main class="news-page">
        <div class="news-container">
            
            <div class="news-main-column">
                
                <form action="database.php" method="GET" id="searchFilterForm">
                    
                    <div class="database-search-wrapper">
                        
                        <div class="search-input-group">
                            <input type="text" name="search" class="search-input-custom" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari dokumen hukum, judul, atau kata kunci...">
                            <button type="submit" class="btn-search-custom">
                                <i class="fa-solid fa-magnifying-glass"></i> Cari
                            </button>
                        </div>

                        <div class="filter-sort-group">
                            <select name="kategori_filter" class="filter-select" onchange="this.form.submit()">
                                <option value="all">-- Semua Kategori Dokumen --</option>
                                <?php foreach($kategori_list as $kat): ?>
                                    <option value="<?php echo htmlspecialchars($kat); ?>" <?php if($filter_kategori == $kat) echo 'selected'; ?>>
                                        <?php echo ucwords(str_replace('-', ' ', $kat)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select name="sort_by" class="sort-select" onchange="this.form.submit()">
                                <option value="terbaru" <?php if($sort_by == 'terbaru') echo 'selected'; ?>>Urutkan: Terbaru</option>
                                <option value="abjad_asc" <?php if($sort_by == 'abjad_asc') echo 'selected'; ?>>Urutkan: Abjad (A-Z)</option>
                                <option value="abjad_desc" <?php if($sort_by == 'abjad_desc') echo 'selected'; ?>>Urutkan: Abjad (Z-A)</option>
                                <option value="terpopuler" <?php if($sort_by == 'terpopuler') echo 'selected'; ?>>Urutkan: Terpopuler</option>
                            </select>
                        </div>

                        <?php if($search !== '' || $filter_kategori !== 'all' || $sort_by !== 'terbaru'): ?>
                            <div class="search-status-text">
                                <span>Filter pencarian sedang aktif.</span>
                                <a href="database.php" class="btn-reset-filter">
                                    <i class="fa-solid fa-xmark"></i> Reset Semua
                                </a>
                            </div>
                        <?php endif; ?>

                    </div>

                    <h2 class="section-title"><?php echo $title_display; ?></h2>

                    <?php if (count($dokumen_list) > 0 && ($search !== '' || $filter_kategori !== 'all')): ?>
                        <p class="result-count-text">
                            Ditemukan <strong><?php echo $total_data; ?></strong> dokumen yang cocok.
                        </p>
                    <?php elseif (count($dokumen_list) > 0 && $search === '' && $filter_kategori === 'all'): ?>
                        <p class="result-count-text">
                            Menampilkan <strong><?php echo $total_data; ?></strong> dokumen.
                        </p>
                    <?php endif; ?>

                </form> <?php if (count($dokumen_list) > 0): ?>

                    <?php foreach($dokumen_list as $doc): 
                        $doc_cat_name = ucwords(str_replace('-', ' ', $doc['kategori']));
                    ?>
                        <div class="doc-card">
                            <div class="doc-info">
                                <div class="doc-meta-top">
                                    <span class="doc-date"><i class="fa-regular fa-calendar"></i> <?php echo tgl_indo($doc['tanggal_penetapan']); ?></span>
                                    <span class="sidebar-cat-badge" style="margin-left:10px;"><?php echo $doc_cat_name; ?></span>
                                </div>
                                
                                <h3 class="doc-title">
                                    <a href="database_detail.php?id=<?php echo $doc['id']; ?>&kategori=<?php echo $doc['kategori']; ?>">
                                        <?php echo htmlspecialchars($doc['judul']); ?>
                                    </a>
                                </h3>
                                
                                <p class="doc-source">Sumber : <?php echo htmlspecialchars($doc['sumber']); ?></p>
                                
                                <?php if($search !== '' && !empty($doc['deskripsi'])): ?>
                                    <p style="font-size: 13px; color: #555; margin-top: 8px; line-height: 1.5;">
                                        <?php echo substr(htmlspecialchars($doc['deskripsi']), 0, 150); ?>...
                                    </p>
                                <?php endif; ?>
                                
                                <div class="doc-meta-bottom">
                                    <span><i class="fa-solid fa-eye"></i> <?php echo number_format($doc['views'], 0, ',', '.'); ?> View</span>
                                    <span><i class="fa-solid fa-thumbs-up"></i> <?php echo number_format($doc['total_likes'], 0, ',', '.'); ?> Like</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($total_halaman > 1): ?>
                    <div class="pagination">
                        <?php if ($halaman_aktif > 1): ?>
                            <a href="database.php?page=<?php echo $halaman_aktif - 1; ?><?php echo $url_params; ?>" class="page-btn prev"><i class="fa-solid fa-chevron-left"></i> Prev</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                            <a href="database.php?page=<?php echo $i; ?><?php echo $url_params; ?>" class="page-num <?php echo ($i == $halaman_aktif) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($halaman_aktif < $total_halaman): ?>
                            <a href="database.php?page=<?php echo $halaman_aktif + 1; ?><?php echo $url_params; ?>" class="page-btn next">Next <i class="fa-solid fa-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="no-results">
                        <i class="fa-solid fa-magnifying-glass" style="font-size: 40px; margin-bottom: 15px; color: #ddd;"></i>
                        <?php if($search !== ''): ?>
                            <p>Tidak ditemukan hasil untuk kata kunci <strong>"<?php echo htmlspecialchars($search); ?>"</strong>.</p>
                            <p style="font-size:13px; margin-top:10px;">Coba gunakan kata kunci lain yang lebih umum atau ubah filter kategorinya.</p>
                        <?php else: ?>
                            <p>Belum ada dokumen yang sesuai dengan filter Anda.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>

            <aside class="news-sidebar">
                <div class="sidebar-widget">
                    <h3 class="widget-title">Terpopuler</h3>
                    <div class="popular-list">
                        <?php 
                        $rank = 1;
                        foreach($populer_list as $pop): 
                            $nama_kategori_pop = ucwords(str_replace('-', ' ', $pop['kategori']));
                        ?>
                        <div class="popular-item">
                            <div class="pop-number"><?php echo $rank++; ?></div>
                            <div class="pop-text">
                                <span class="sidebar-cat-badge"><?php echo $nama_kategori_pop; ?></span>
                                <h4><a href="database_detail.php?id=<?php echo $pop['id']; ?>&kategori=<?php echo $pop['kategori']; ?>"><?php echo htmlspecialchars($pop['judul']); ?></a></h4>
                                <span><i class="fa-solid fa-eye"></i> <?php echo number_format($pop['views'] / 1000, 1); ?>K View</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="sidebar-widget">
                    <h3 class="widget-title">Direkomendasikan</h3>
                    <ul class="recommend-list">
                        <?php 
                        foreach($rekomendasi_list as $rec): 
                            $nama_kategori_rec = ucwords(str_replace('-', ' ', $rec['kategori']));
                        ?>
                        <li>
                            <a href="database_detail.php?id=<?php echo $rec['id']; ?>&kategori=<?php echo $rec['kategori']; ?>">
                                <span class="sidebar-cat-badge"><?php echo $nama_kategori_rec; ?></span>
                                <strong><?php echo htmlspecialchars($rec['judul']); ?></strong>
                                <p><?php echo substr(htmlspecialchars($rec['deskripsi']), 0, 80); ?>...</p>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>
        </div>
    </main>

<?php include 'footer.php'; ?>