<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/configuration.php';

// 1. Tangkap parameter dari URL
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : 'peraturan';
$search   = isset($_GET['search']) ? trim($_GET['search']) : '';

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
// KONFIGURASI PAGINATION
// ==========================================
$batas_per_halaman = 5; // Jumlah dokumen per halaman
$halaman_aktif = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($halaman_aktif < 1) $halaman_aktif = 1;
$offset = ($halaman_aktif - 1) * $batas_per_halaman;

// Siapkan parameter URL tambahan agar tidak hilang saat pindah halaman
$url_params = '';
if ($search !== '') {
    $url_params = '&search=' . urlencode($search);
} else {
    $url_params = '&kategori=' . urlencode($kategori);
}

// ==========================================
// QUERY DATABASE DENGAN PAGINATION
// ==========================================
try {
    if ($search !== '') {
        $title_display = 'Hasil Pencarian: "' . htmlspecialchars($search) . '"';
        
        // A. Hitung total data pencarian
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM `databases` WHERE status = 1 AND (judul LIKE :search1 OR deskripsi LIKE :search2)");
        $stmt_count->execute([':search1' => "%$search%", ':search2' => "%$search%"]);
        $total_data = $stmt_count->fetchColumn();

        // B. Tarik data pencarian dengan Limit
        $stmt_docs = $pdo->prepare("SELECT `databases`.*,(select count(*) from likes where likes.dokumen_id = `databases`.id) as total_likes FROM `databases` WHERE status = 1 AND (judul LIKE :search1 OR deskripsi LIKE :search2) ORDER BY tanggal_penetapan DESC LIMIT :limit OFFSET :offset");
        $stmt_docs->bindValue(':search1', "%$search%", PDO::PARAM_STR);
        $stmt_docs->bindValue(':search2', "%$search%", PDO::PARAM_STR);
        
    } else {
        $title_display = 'Database: ' . ucwords(str_replace('-', ' ', $kategori));
        
        // A. Hitung total data kategori
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM `databases` WHERE kategori = :kategori AND status = 1");
        $stmt_count->execute([':kategori' => $kategori]);
        $total_data = $stmt_count->fetchColumn();

        // B. Tarik data kategori dengan Limit
        $stmt_docs = $pdo->prepare("SELECT `databases`.*,(select count(*) from likes where likes.dokumen_id = `databases`.id) as total_likes FROM `databases` WHERE kategori = :kategori AND status = 1 ORDER BY tanggal_penetapan DESC LIMIT :limit OFFSET :offset");
        $stmt_docs->bindValue(':kategori', $kategori, PDO::PARAM_STR);
    }
    
    // Binding limit & offset (harus integer)
    $stmt_docs->bindValue(':limit', $batas_per_halaman, PDO::PARAM_INT);
    $stmt_docs->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt_docs->execute();
    
    $dokumen_list = $stmt_docs->fetchAll();
    $total_halaman = ceil($total_data / $batas_per_halaman);

} catch (PDOException $e) {
    die("Error mengambil dokumen: " . $e->getMessage());
}

// Sidebar: Tarik 3 Dokumen Terpopuler
$stmt_pop = $pdo->prepare("SELECT id, judul, views, kategori FROM `databases` WHERE status = 1 ORDER BY views DESC LIMIT 3");
$stmt_pop->execute();
$populer_list = $stmt_pop->fetchAll();

// Sidebar: Tarik 3 Dokumen Rekomendasi
$stmt_rec = $pdo->prepare("SELECT id, judul, deskripsi, kategori FROM `databases` WHERE status = 1 AND rekomendasi = 1 ORDER BY created_at DESC LIMIT 3");
$stmt_rec->execute();
$rekomendasi_list = $stmt_rec->fetchAll();

include 'header.php'; 
include 'navbar.php'; 
?>

    <main class="news-page">
        <div class="news-container">
            
            <div class="news-main-column">
                <h2 class="section-title"><?php echo $title_display; ?></h2>

                <?php if (count($dokumen_list) > 0): ?>
                    
                    <?php if($search !== ''): ?>
                        <p style="margin-top:-15px; margin-bottom:20px; color:#666; font-size:14px;">
                            Ditemukan <strong><?php echo $total_data; ?></strong> dokumen yang cocok.
                        </p>
                    <?php endif; ?>

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
                            <p style="font-size:13px; margin-top:10px;">Coba gunakan kata kunci lain yang lebih umum.</p>
                        <?php else: ?>
                            <p>Belum ada dokumen di kategori ini.</p>
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