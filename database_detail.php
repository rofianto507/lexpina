<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/configuration.php';

// 1. Tangkap ID dokumen dari URL
$id_dokumen = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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
// QUERY UTAMA
// ==========================================
try {
    // Tarik detail dokumen
    $stmt = $pdo->prepare("SELECT * FROM `databases` WHERE id = ? AND status = 1");
    $stmt->execute([$id_dokumen]);
    $doc = $stmt->fetch();

    // Jika dokumen tidak ditemukan, lempar kembali ke index
    if (!$doc) {
        echo "<script>alert('Dokumen tidak ditemukan atau telah dihapus.'); window.location.href='index.php';</script>";
        exit();
    }
    
    // CEK STATUS BOOKMARK (Hanya jika user login)
    $is_bookmarked = false;
    if (isset($_SESSION['user_id'])) {
        $stmt_bm = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND dokumen_id = ?");
        $stmt_bm->execute([$_SESSION['user_id'], $doc['id']]);
        if ($stmt_bm->fetch()) {
            $is_bookmarked = true;
        }
    }
    // CEK STATUS LIKE (Hanya jika user login)
    $is_liked = false;
    if (isset($_SESSION['user_id'])) {
        $stmt_like = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND dokumen_id = ?");
        $stmt_like->execute([$_SESSION['user_id'], $doc['id']]);
        if ($stmt_like->fetch()) {
            $is_liked = true;
        }
    }

    // UPDATE VIEWS: Tambah 1 ke kolom views setiap kali halaman ini dibuka
    $stmt_view = $pdo->prepare("UPDATE `databases` SET views = views + 1 WHERE id = ?");
    $stmt_view->execute([$id_dokumen]);
    
    // Perbarui nilai view agar langsung tampil dengan angka terbaru di layar
    $doc['views'] += 1; 

    // Format Kategori untuk tampilan
    $kategori_slug = $doc['kategori'];
    $kategori_display = ucwords(str_replace('-', ' ', $kategori_slug));

    // Tarik Sidebar: Terpopuler
    $stmt_pop = $pdo->prepare("SELECT id, judul, views, kategori FROM `databases` WHERE status = 1 ORDER BY views DESC LIMIT 3");
    $stmt_pop->execute();
    $populer_list = $stmt_pop->fetchAll();

    // Tarik Sidebar: Direkomendasikan
    $stmt_rec = $pdo->prepare("SELECT id, judul, deskripsi, kategori FROM `databases` WHERE status = 1 AND rekomendasi = 1 ORDER BY created_at DESC LIMIT 2");
    $stmt_rec->execute();
    $rekomendasi_list = $stmt_rec->fetchAll();

    // Tarik daftar Peraturan Konsolidasi terkait
    $stmt_kon = $pdo->prepare("
        SELECT d.id, d.judul, d.kategori 
        FROM relasi_konsolidasi r
        JOIN `databases` d ON r.konsolidasi_id = d.id
        WHERE r.parent_id = ?
    ");
    $stmt_kon->execute([$id_dokumen]);
    $list_konsolidasi = $stmt_kon->fetchAll();

} catch (PDOException $e) {
    die("Error mengambil dokumen: " . $e->getMessage());
}

// Set menu active
$active_page = 'database';
include 'header.php'; 
include 'navbar.php'; 
?>

    <main class="news-page">
        <div class="news-container">
            
            <div class="news-main-column">
                
                <nav class="breadcrumb">
                    <a href="index.php">Beranda</a> &raquo; 
                    <a href="database.php?kategori=<?php echo $kategori_slug; ?>">Database <?php echo $kategori_display; ?></a> &raquo; 
                    <span>Detail Dokumen</span>
                    <div style="float: right; margin-left: 15px; display: flex; gap: 10px;">
                    
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <?php if($is_liked): ?>
                                <a href="proses_like.php?id=<?php echo $doc['id']; ?>&action=remove" class="btn-like liked" title="Batal Suka">
                                    <i class="fa-solid fa-thumbs-up"></i>
                                </a>
                            <?php else: ?>
                                <a href="proses_like.php?id=<?php echo $doc['id']; ?>&action=add" class="btn-like" title="Suka Dokumen Ini">
                                    <i class="fa-regular fa-thumbs-up"></i>
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <button type="button" class="btn-like" onclick="document.getElementById('btnOpenLogin').click();" title="Login untuk Menyukai">
                                <i class="fa-regular fa-thumbs-up"></i>
                            </button>
                        <?php endif; ?>

                        <?php if(isset($_SESSION['user_id'])): ?>
                            <?php if($is_bookmarked): ?>
                                <a href="proses_bookmark.php?id=<?php echo $doc['id']; ?>&action=remove&ref=detail" class="btn-bookmark bookmarked" title="Hapus dari Tersimpan">
                                    <i class="fa-solid fa-bookmark"></i>
                                </a>
                            <?php else: ?>
                                <a href="proses_bookmark.php?id=<?php echo $doc['id']; ?>&action=add&ref=detail" class="btn-bookmark" title="Simpan Dokumen">
                                    <i class="fa-regular fa-bookmark"></i>
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <button type="button" class="btn-bookmark" onclick="document.getElementById('btnOpenLogin').click();" title="Login untuk Menyimpan">
                                <i class="fa-regular fa-bookmark"></i>
                            </button>
                        <?php endif; ?>

                    </div>
                </nav>

                <div class="doc-detail-header">
                    <h1 class="single-title"><?php echo htmlspecialchars($doc['judul']); ?></h1>
                    
                    <div class="single-meta">
                        <span><i class="fa-solid fa-cloud-arrow-up"></i> Diunggah: <?php echo tgl_indo($doc['created_at']); ?></span>
                        <span><i class="fa-solid fa-eye"></i> <?php echo number_format($doc['views'], 0, ',', '.'); ?> View</span>
                        <span><i class="fa-solid fa-thumbs-up"></i> <?php echo number_format($doc['likes'], 0, ',', '.'); ?> Like</span>
                    </div>

                    <div class="doc-info-box">
                        <div class="info-row full-width">
                            <span class="info-label">Sumber Dokumen</span>
                            <span class="info-value"><?php echo htmlspecialchars($doc['sumber']); ?></span>
                        </div>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Tanggal Penetapan</span>
                                <span class="info-value"><i class="fa-regular fa-calendar-check"></i> <?php echo tgl_indo($doc['tanggal_penetapan']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Tanggal Pengundangan</span>
                                <span class="info-value"><i class="fa-regular fa-calendar-plus"></i> <?php echo tgl_indo($doc['tanggal_pengundangan']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Tanggal Berlaku</span>
                                <span class="info-value"><i class="fa-solid fa-flag-checkered"></i> <?php echo tgl_indo($doc['tanggal_berlaku']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php if (count($list_konsolidasi) > 0): ?>
                        <div class="konsolidasi-alert" style="background: #fff4e5; border-left: 5px solid #ffa117; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <i class="fa-solid fa-circle-info" style="color: #ffa117; font-size: 20px;"></i>
                                <div>
                                    <strong style="display: block; margin-bottom: 5px; color: #663c00;">Tersedia Peraturan Konsolidasi</strong>
                                    <p style="font-size: 14px; margin: 0; color: #663c00;">Dokumen ini telah dilengkapi dengan naskah konsolidasi terbaru:</p>
                                    <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                                        <?php foreach ($list_konsolidasi as $kon): ?>
                                            <li style="margin-bottom: 5px;">
                                                <a href="database_detail.php?id=<?= $kon['id'] ?>&kategori=<?= $kon['kategori'] ?>" style="color: #e67e22; font-weight: bold; text-decoration: underline;">
                                                    <?= htmlspecialchars($kon['judul']) ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php 
                $user_akses = isset($_SESSION['akses']) ? $_SESSION['akses'] : 'PENGGUNA';
                $is_konsolidasi = ($doc['kategori'] == 'peraturan-konsolidasi');
                $boleh_lihat = true;

                // Jika kategori konsolidasi tapi bukan MEMBER, maka akses ditutup
                if ($is_konsolidasi && $user_akses !== 'MEMBER') {
                    $boleh_lihat = false;
                }
                ?>

                <?php if ($boleh_lihat): ?>
                    <div class="pdf-wrapper" id="pdfWrapper">
                        <div class="pdf-controls">
                            <button id="btnFullscreen" class="btn-control">
                                <i class="fa-solid fa-expand"></i> Layar Penuh
                            </button>
                        </div>
                        
                        <div class="pdf-container">
                            <iframe 
                                src="assets/pdfjs/web/viewer.html?file=../../../public/upload/documents/<?php echo urlencode($doc['file_pdf']); ?>" 
                                id="pdfIframe"
                                width="100%" 
                                height="800px" 
                                style="border: none;">
                            </iframe>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="premium-lock-box">
                        <div class="lock-content">
                            <div class="lock-icon">
                                <i class="fa-solid fa-crown"></i>
                            </div>
                            <h2>Konten Eksklusif Premium</h2>
                            <p>Mohon maaf, naskah <strong>Peraturan Konsolidasi</strong> hanya dapat diakses oleh Member Premium LexPina.</p>
                            <p class="lock-note">Dapatkan akses tanpa batas ke seluruh database, fitur analisis, dan dokumen premium lainnya.</p>
                            <div class="lock-actions">
                                <a href="langganan.php" class="btn-upgrade-now">
                                    <i class="fa-solid fa-rocket"></i> Upgrade ke Premium Sekarang
                                </a>
                                <p class="small-text">Mulai dari Rp 49.999/bulan</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="doc-description">
                    <h3>Keterangan Tambahan</h3>
                    <p><?php echo nl2br(htmlspecialchars($doc['deskripsi'])); ?></p>
                </div>

            </div>

            <aside class="news-sidebar">
                
                <?php if(!empty($doc['dicabut']) || !empty($doc['mencabut'])): ?>
                <div class="sidebar-widget status-widget">
                    <h3 class="widget-title">Status Peraturan</h3>
                    <div class="status-container">
                        
                        <?php if(!empty($doc['dicabut'])): ?>
                        <div class="status-box">
                            <span class="status-label"><i class="fa-solid fa-check"></i> Dicabut dengan : </span>
                            <div class="status-konten"><?php echo $doc['dicabut']; ?></div> 
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($doc['dicabut_sebagian'])): ?>
                        <div class="status-box">
                            <span class="status-label"><i class="fa-solid fa-check"></i> Dicabut sebagian dengan : </span>
                            <div class="status-konten"><?php echo $doc['dicabut_sebagian']; ?></div> 
                        </div>
                        <?php endif; ?>

                        <?php if(!empty($doc['mencabut'])): ?>
                        <div class="status-box">
                            <span class="status-label"><i class="fa-solid fa-check"></i> Mencabut dengan :</span>
                            <div class="status-konten"><?php echo $doc['mencabut']; ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($doc['mencabut_sebagian'])): ?>
                        <div class="status-box">
                            <span class="status-label"><i class="fa-solid fa-check"></i> Mencabut sebagian dengan :</span>
                            <div class="status-konten"><?php echo $doc['mencabut_sebagian']; ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($doc['diubah'])): ?>
                        <div class="status-box">
                            <span class="status-label"><i class="fa-solid fa-check"></i> Diubah dengan :</span>
                            <div class="status-konten"><?php echo $doc['diubah']; ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($doc['diubah_sebagian'])): ?>
                        <div class="status-box">
                            <span class="status-label"><i class="fa-solid fa-check"></i> Diubah sebagian dengan :</span>
                            <div class="status-konten"><?php echo $doc['diubah_sebagian']; ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($doc['mengubah'])): ?>
                        <div class="status-box">
                            <span class="status-label"><i class="fa-solid fa-check"></i> Mengubah dengan :</span>
                            <div class="status-konten"><?php echo $doc['mengubah']; ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($doc['mengubah_sebagian'])): ?>
                        <div class="status-box">
                            <span class="status-label"><i class="fa-solid fa-check"></i> Mengubah sebagian dengan :</span>
                            <div class="status-konten"><?php echo $doc['mengubah_sebagian']; ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($doc['uji_materi'])): ?>
                        <div class="status-box">
                            <span class="status-label"><i class="fa-solid fa-check"></i> Uji Materi dengan :</span>
                            <div class="status-konten"><?php echo $doc['uji_materi']; ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

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