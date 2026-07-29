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
        echo "<script>alert('Dokumen tidak ditemukan atau telah dihapus.'); window.location.href='index';</script>";
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
    $stmt_pop = $pdo->prepare("SELECT id, judul, slug, views, kategori FROM `databases` WHERE status = 1 ORDER BY views DESC LIMIT 3");
    $stmt_pop->execute();
    $populer_list = $stmt_pop->fetchAll();

    // Tarik Sidebar: Direkomendasikan
    $stmt_rec = $pdo->prepare("SELECT id, judul, slug, deskripsi, kategori FROM `databases` WHERE status = 1 AND rekomendasi = 1 ORDER BY created_at DESC LIMIT 2");
    $stmt_rec->execute();
    $rekomendasi_list = $stmt_rec->fetchAll();

    // Tarik daftar Peraturan Konsolidasi terkait
    $stmt_kon = $pdo->prepare("
        SELECT d.id, d.judul, d.slug, d.kategori
        FROM relasi_konsolidasi r
        JOIN `databases` d ON r.konsolidasi_id = d.id
        WHERE r.parent_id = ?
    ");
    $stmt_kon->execute([$id_dokumen]);
    $list_konsolidasi = $stmt_kon->fetchAll();

    // ==========================================
    // PERBAIKAN PATH UNTUK PDF MOBILE (ROOT-RELATIVE)
    // ==========================================
    // Cari tahu posisi root folder LexPina Anda
    $base_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); 
    if ($base_dir == '/') $base_dir = '';
    
    // 1. Buat path ke file PDF (cth: /lexpina/public/upload/documents/file.pdf)
    $pdf_file_path = $base_dir . "/public/upload/documents/" . $doc['file_pdf'];
    
    // 2. Buat path ke file viewer.html PDF.js
    $viewer_path = $base_dir . "/assets/pdfjs/web/viewer.html";

} catch (PDOException $e) {
    die("Error mengambil dokumen: " . $e->getMessage());
}

// ==========================================
// SEO: title & meta description dinamis (dipakai oleh header.php)
// ==========================================
$page_title = $doc['judul'];
$meta_desc_raw = trim(strip_tags($doc['deskripsi']));
if ($meta_desc_raw === '') {
    $meta_desc_raw = $doc['judul'] . ' - ' . $kategori_display . '. Baca selengkapnya di database hukum LexPina.';
}
$page_description = mb_strlen($meta_desc_raw) > 160 ? mb_substr($meta_desc_raw, 0, 157) . '...' : $meta_desc_raw;
// Canonical selalu ke versi ber-slug, meski halaman diakses lewat URL lama tanpa slug
$page_canonical = $path . 'database-detail?id=' . $doc['id'] . '&slug=' . $doc['slug'] . '&kategori=' . $doc['kategori'];

// Set menu active
$active_page = 'database';
include 'header.php';
include 'navbar.php';

// ==========================================
// SEO: Structured data (JSON-LD) - Legislation + Breadcrumb
// ==========================================
function seo_iso_date($tanggal) {
    if (empty($tanggal) || $tanggal === '0000-00-00') return null;
    return date('Y-m-d', strtotime($tanggal));
}

$ld_legislation = [
    '@context' => 'https://schema.org',
    '@type' => 'Legislation',
    'name' => $doc['judul'],
    'description' => $page_description,
    'url' => $page_canonical,
    'legislationIdentifier' => $doc['sumber'],
    'legislationType' => $kategori_display,
    'legislationJurisdiction' => 'Indonesia',
    'inLanguage' => 'id',
];
if (seo_iso_date($doc['tanggal_penetapan'])) $ld_legislation['datePublished'] = seo_iso_date($doc['tanggal_penetapan']);
if (seo_iso_date($doc['tanggal_berlaku']))   $ld_legislation['legislationDate'] = seo_iso_date($doc['tanggal_berlaku']);

$ld_breadcrumb = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => $path . 'index'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Database ' . $kategori_display, 'item' => $path . 'database?kategori=' . $kategori_slug],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $doc['judul'], 'item' => $page_canonical],
    ],
];
?>
<script type="application/ld+json"><?php echo json_encode($ld_legislation, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
<script type="application/ld+json"><?php echo json_encode($ld_breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>

    <main class="news-page">
        <div class="news-container">
            
            <div class="news-main-column">
                
                <nav class="breadcrumb">
                    <a href="index">Beranda</a> &raquo;
                    <a href="database?kategori=<?php echo $kategori_slug; ?>">Database <?php echo $kategori_display; ?></a> &raquo;
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
                            <div class="info-value-with-badge">
                                <span class="info-value"><?php echo htmlspecialchars($doc['sumber']); ?></span>
                                <?php if (($doc['status_berlaku'] ?? 'berlaku') === 'tidak_berlaku'): ?>
                                    <span class="status-badge badge-ditolak"><i class="fa-solid fa-circle-xmark"></i> Tidak Berlaku</span>
                                <?php else: ?>
                                    <span class="status-badge badge-lunas"><i class="fa-solid fa-circle-check"></i> Berlaku</span>
                                <?php endif; ?>
                            </div>
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
                        <div class="konsolidasi-alert" style="background: #f8fff9; border: 1px solid #c3e6cb; border-left: 5px solid #2ecc71; padding: 15px; margin-bottom: 20px; border-radius: 6px;">
                            <div style="display: flex; align-items: flex-start; gap: 12px;">
                                <i class="fa-solid fa-circle-info" style="color: #27ae60; font-size: 20px; margin-top: 3px;"></i>
                                <div style="flex: 1;">
                                    <strong style="display: block; margin-bottom: 5px; color: #1e8449; font-size: 16px;">Tersedia Peraturan Konsolidasi</strong>
                                    <p style="font-size: 14px; margin: 0 0 12px 0; color: #2e4053;">Dokumen ini telah dilengkapi dengan naskah konsolidasi yang menggabungkan seluruh perubahannya:</p>
                                    
                                    <div style="display: flex; flex-direction: column; gap: 8px;">
                                        <?php foreach ($list_konsolidasi as $kon): ?>
                                            <div style="display: flex; align-items: center; gap: 10px; background: #fff; padding: 10px 15px; border-radius: 6px; border: 1px solid #e8f8f5; box-shadow: 0 2px 4px rgba(46, 204, 113, 0.05);">
                                                <i class="fa-solid fa-circle-check" style="color: #2ecc71; font-size: 18px;"></i>
                                                <a href="database-detail?id=<?= $kon['id'] ?>&slug=<?= $kon['slug'] ?>&kategori=<?= $kon['kategori'] ?>" style="color: #2c3e50; font-weight: 700; text-decoration: none; font-size: 14px;">
                                                    <?= htmlspecialchars($kon['judul']) ?>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
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

<style>
#pdf-main-wrapper {
    width: 100%;
    background: #525659;
    border-radius: 8px;
    padding: 10px;
    box-sizing: border-box;
    user-select: none;
    -webkit-user-select: none;
}
#pdf-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    background: #3a3d40;
    padding: 8px 12px;
    border-radius: 6px 6px 0 0;
    color: #fff;
}
#pdf-toolbar .toolbar-group {
    display: flex;
    align-items: center;
    gap: 6px;
}
#pdf-toolbar button {
    background: #555;
    color: #fff;
    border: none;
    padding: 7px 13px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    transition: background 0.2s;
    -webkit-tap-highlight-color: transparent;
}
#pdf-toolbar button:hover    { background: #777; }
#pdf-toolbar button:disabled { opacity: 0.4; cursor: not-allowed; }
#pdf-page-info  { font-size: 13px; color: #ccc; white-space: nowrap; }
#zoom-level     { font-size: 13px; color: #ccc; min-width: 45px; text-align: center; }
#pdf-canvas-container {
    width: 100%;
    max-height: 80vh;
    overflow-y: auto;
    overflow-x: hidden;
    background: #525659;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 12px 0;
    box-sizing: border-box;
    -webkit-overflow-scrolling: touch;
}
#pdf-canvas-container canvas {
    display: block;
    box-shadow: 0 4px 15px rgba(0,0,0,0.5);
    background: #fff;
    max-width: 100%;
    height: auto !important;
}
#pdf-status {
    color: #ccc;
    text-align: center;
    padding: 60px 20px;
    font-size: 15px;
    width: 100%;
}
#pdf-status i {
    font-size: 36px;
    display: block;
    margin-bottom: 10px;
}
.spin { animation: spin 1s linear infinite; }
@keyframes spin { 100% { transform: rotate(360deg); } }

#pdf-main-wrapper:-webkit-full-screen #pdf-canvas-container { max-height: 95vh; }
#pdf-main-wrapper:fullscreen          #pdf-canvas-container { max-height: 95vh; }

/* ── Pencarian teks dalam dokumen ─────────────────────────── */
#pdf-search-bar {
    display: none;
    align-items: center;
    gap: 8px;
    background: #3a3d40;
    color: #ccc;
    padding: 8px 12px;
    border-top: 1px solid #555;
}
#pdf-search-bar i.fa-magnifying-glass { color: #999; font-size: 13px; }
#pdf-search-input {
    flex: 1;
    min-width: 120px;
    background: #2c2f31;
    border: 1px solid #555;
    color: #fff;
    padding: 6px 10px;
    border-radius: 4px;
    font-size: 13px;
    outline: none;
}
#pdf-search-input:focus { border-color: #f1c40f; }
#pdf-search-count {
    font-size: 12px;
    color: #ccc;
    white-space: nowrap;
    min-width: 90px;
    text-align: center;
}
#pdf-search-bar button {
    background: #555;
    color: #fff;
    border: none;
    width: 30px;
    height: 30px;
    flex-shrink: 0;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}
#pdf-search-bar button:hover    { background: #777; }
#pdf-search-bar button:disabled { opacity: 0.4; cursor: not-allowed; }

/* Text layer transparan di atas canvas, hanya untuk pencarian (bukan seleksi/copy,
   konsisten dengan proteksi user-select:none & anti klik-kanan pada wrapper) */
#pdf-page-wrapper { position: relative; }
.textLayer {
    position: absolute;
    inset: 0;
    overflow: hidden;
    line-height: 1;
    pointer-events: none;
}
.textLayer span {
    color: transparent;
    position: absolute;
    white-space: pre;
    transform-origin: 0% 0%;
}
.textLayer .pdf-search-highlight {
    background: rgba(255, 213, 0, 0.4);
    border-radius: 2px;
}
.textLayer .pdf-search-highlight.current {
    background: rgba(255, 130, 0, 0.8);
}
</style>

<!-- =====================================================
     PENTING: Gunakan legacy build (NON-module) agar
     kompatibel di Android lama / Samsung Browser / UC Browser
     ===================================================== -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<div id="pdf-main-wrapper" oncontextmenu="return false;">

    <div id="pdf-toolbar">
        <div class="toolbar-group">
            <button id="btn-first"  title="Pertama"><i class="fa-solid fa-backward-step"></i></button>
            <button id="btn-prev"   title="Sebelumnya"><i class="fa-solid fa-chevron-left"></i></button>
            <span id="pdf-page-info">-</span>
            <button id="btn-next"   title="Selanjutnya"><i class="fa-solid fa-chevron-right"></i></button>
            <button id="btn-last"   title="Terakhir"><i class="fa-solid fa-forward-step"></i></button>
            <button id="btn-search-toggle" title="Cari Teks"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
        <div class="toolbar-group">
            <button id="btn-zoom-out"  title="Perkecil"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
            <span id="zoom-level">100%</span>
            <button id="btn-zoom-in"   title="Perbesar"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
            <button id="btn-zoom-fit"  title="Fit Lebar">Fit</button>
            <button id="btn-fullscreen" title="Layar Penuh"><i class="fa-solid fa-expand"></i></button>
        </div>
    </div>

    <div id="pdf-search-bar">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="pdf-search-input" placeholder="Cari teks dalam dokumen..." autocomplete="off">
        <span id="pdf-search-count"></span>
        <button id="btn-search-prev" title="Sebelumnya" disabled><i class="fa-solid fa-chevron-up"></i></button>
        <button id="btn-search-next" title="Berikutnya" disabled><i class="fa-solid fa-chevron-down"></i></button>
        <button id="btn-search-close" title="Tutup"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div id="pdf-canvas-container">
        <div id="pdf-status">
            <i class="fa-solid fa-spinner spin"></i>
            Memuat dokumen, mohon tunggu...
        </div>
    </div>

</div>

<script>
(function () {
    'use strict';

    // ── Konfigurasi ──────────────────────────────────────────
    // Gunakan PHP Proxy agar tidak ada CORS & path file tidak terekspos
    var PDF_URL    = 'pdf_proxy.php?id=<?php echo (int)$doc['id']; ?>';
 var WORKER_SRC = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    var BASE_DIR   = '<?php echo $base_dir; ?>';
    // ─────────────────────────────────────────────────────────

    // Tunggu pdfjsLib siap (legacy build attach ke window)
    if (typeof pdfjsLib === 'undefined') {
        document.getElementById('pdf-status').innerHTML =
            '<i class="fa-solid fa-triangle-exclamation" style="color:#e74c3c"></i>' +
            '<p style="color:#e74c3c">Gagal memuat library PDF.js.<br>Coba refresh halaman.</p>';
        return;
    }

    pdfjsLib.GlobalWorkerOptions.workerSrc = WORKER_SRC;

    var container   = document.getElementById('pdf-canvas-container');
    var statusEl    = document.getElementById('pdf-status');
    var pageInfoEl  = document.getElementById('pdf-page-info');
    var zoomEl      = document.getElementById('zoom-level');

    var pdfDoc      = null;
    var currentPage = 1;
    var totalPages  = 0;
    var scale       = 1.0;
    var isRendering = false;
    var renderQueue = null;

    // ── State pencarian teks ──────────────────────────────────
    var searchToggleBtn   = document.getElementById('btn-search-toggle');
    var searchBar         = document.getElementById('pdf-search-bar');
    var searchInput       = document.getElementById('pdf-search-input');
    var searchCountEl     = document.getElementById('pdf-search-count');
    var btnSearchPrev     = document.getElementById('btn-search-prev');
    var btnSearchNext     = document.getElementById('btn-search-next');
    var btnSearchClose    = document.getElementById('btn-search-close');

    var pagesText        = {};  // pageNum -> { fullTextLower, offsets:[{start,end}], itemCount }
    var searchMatches     = []; // [{ pageNum, start, end }] urut per halaman & posisi
    var currentMatchIdx   = -1;
    var currentSearchTerm = '';

    // ── Helper: tampilkan status ──────────────────────────────
    function showStatus(html) {
        statusEl.style.display = 'block';
        statusEl.innerHTML = html;
    }
    function hideStatus() {
        statusEl.style.display = 'none';
    }

    // ── Load PDF via proxy ────────────────────────────────────
    var loadingTask = pdfjsLib.getDocument(PDF_URL);

    loadingTask.onProgress = function (data) {
        if (data.total > 0) {
            var pct = Math.round((data.loaded / data.total) * 100);
            showStatus(
                '<i class="fa-solid fa-spinner spin"></i>' +
                'Memuat dokumen... ' + pct + '%'
            );
        }
    };

    loadingTask.promise.then(function (pdf) {
        pdfDoc     = pdf;
        totalPages = pdf.numPages;
        hideStatus();
        fitWidth();

    }).catch(function (err) {
        console.error('PDF.js error:', err);
        showStatus(
            '<i class="fa-solid fa-triangle-exclamation" style="color:#e74c3c"></i>' +
            '<p style="color:#e74c3c">Gagal memuat dokumen.<br>' +
            '<small>' + (err.message || 'Unknown error') + '</small></p>' +
            '<button onclick="location.reload()" style="margin-top:10px;padding:8px 16px;' +
            'background:#e74c3c;color:#fff;border:none;border-radius:4px;cursor:pointer;">' +
            'Coba Lagi</button>'
        );
    });

    // ── Fit width ke lebar kontainer ─────────────────────────
    function fitWidth() {
        // Pakai halaman yang sedang tampil (bukan selalu halaman 1) karena
        // ukuran/orientasi tiap halaman dalam satu dokumen bisa berbeda-beda
        pdfDoc.getPage(currentPage).then(function (page) {
            var viewport    = page.getViewport({ scale: 1.0 });
            var containerW  = container.clientWidth - 24;
            scale = containerW / viewport.width;
            scale = Math.max(0.4, Math.min(scale, 3.0));
            zoomEl.textContent = Math.round(scale * 100) + '%';
            renderPage(currentPage);
        });
    }

    // ── Render halaman ke canvas ──────────────────────────────
    function renderPage(pageNum) {
        if (isRendering) {
            renderQueue = pageNum;
            return;
        }
        isRendering = true;

        pdfDoc.getPage(pageNum).then(function (page) {
            var viewport = page.getViewport({ scale: scale });
            var dpr      = window.devicePixelRatio || 1;

            // Hapus halaman lama, buat wrapper baru (canvas + text layer)
            container.innerHTML = '';
            var pageWrapper = document.createElement('div');
            pageWrapper.id  = 'pdf-page-wrapper';
            pageWrapper.style.width  = Math.floor(viewport.width)  + 'px';
            pageWrapper.style.height = Math.floor(viewport.height) + 'px';

            var canvas    = document.createElement('canvas');
            var ctx       = canvas.getContext('2d');
            canvas.width  = Math.floor(viewport.width  * dpr);
            canvas.height = Math.floor(viewport.height * dpr);
            canvas.style.width  = Math.floor(viewport.width)  + 'px';
            canvas.style.height = Math.floor(viewport.height) + 'px';
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

            var textLayerDiv = document.createElement('div');
            textLayerDiv.className    = 'textLayer';
            textLayerDiv.style.width  = Math.floor(viewport.width)  + 'px';
            textLayerDiv.style.height = Math.floor(viewport.height) + 'px';
            textLayerDiv.style.setProperty('--scale-factor', viewport.scale);

            pageWrapper.appendChild(canvas);
            pageWrapper.appendChild(textLayerDiv);
            container.appendChild(pageWrapper);

            return page.render({ canvasContext: ctx, viewport: viewport }).promise.then(function () {
                currentPage            = pageNum;
                pageInfoEl.textContent = 'Hal ' + currentPage + ' / ' + totalPages;
                updateButtons();

                // Render text layer (dipakai untuk fitur pencarian teks)
                return page.getTextContent().then(function (textContent) {
                    cachePageText(pageNum, textContent);
                    return pdfjsLib.renderTextLayer({
                        textContentSource: textContent,
                        container: textLayerDiv,
                        viewport: viewport
                    }).promise;
                }).then(function () {
                    applyHighlightsOnPage(textLayerDiv, pageNum);
                }).catch(function (err) {
                    console.error('Text layer error:', err);
                });
            });

        }).then(function () {
            isRendering = false;
            if (renderQueue !== null) {
                var next    = renderQueue;
                renderQueue = null;
                renderPage(next);
            }
        }).catch(function (err) {
            console.error('Render error:', err);
            isRendering = false;
        });
    }

    // ── Update state tombol navigasi ─────────────────────────
    function updateButtons() {
        document.getElementById('btn-first').disabled = (currentPage <= 1);
        document.getElementById('btn-prev').disabled  = (currentPage <= 1);
        document.getElementById('btn-next').disabled  = (currentPage >= totalPages);
        document.getElementById('btn-last').disabled  = (currentPage >= totalPages);
    }

    // ── Event: Navigasi ──────────────────────────────────────
    document.getElementById('btn-first').addEventListener('click', function () {
        if (currentPage > 1) renderPage(1);
    });
    document.getElementById('btn-prev').addEventListener('click', function () {
        if (currentPage > 1) renderPage(currentPage - 1);
    });
    document.getElementById('btn-next').addEventListener('click', function () {
        if (currentPage < totalPages) renderPage(currentPage + 1);
    });
    document.getElementById('btn-last').addEventListener('click', function () {
        if (currentPage < totalPages) renderPage(totalPages);
    });

    // ── Event: Zoom ──────────────────────────────────────────
    document.getElementById('btn-zoom-in').addEventListener('click', function () {
        scale = Math.min(scale + 0.2, 3.0);
        zoomEl.textContent = Math.round(scale * 100) + '%';
        renderPage(currentPage);
    });
    document.getElementById('btn-zoom-out').addEventListener('click', function () {
        scale = Math.max(scale - 0.2, 0.4);
        zoomEl.textContent = Math.round(scale * 100) + '%';
        renderPage(currentPage);
    });
    document.getElementById('btn-zoom-fit').addEventListener('click', function () {
        fitWidth();
    });

    // ── Event: Fullscreen ────────────────────────────────────
    var fullscreenBtn = document.getElementById('btn-fullscreen');

    fullscreenBtn.addEventListener('click', function () {
        var wrapper = document.getElementById('pdf-main-wrapper');
        if (!document.fullscreenElement && !document.webkitFullscreenElement) {
            (wrapper.requestFullscreen || wrapper.webkitRequestFullscreen).call(wrapper);
        } else {
            (document.exitFullscreen || document.webkitExitFullscreen).call(document);
        }
    });

    // Ikon & title disinkronkan lewat event ini (bukan di dalam click handler)
    // supaya tetap update walau fullscreen ditutup lewat tombol Esc bawaan browser
    function updateFullscreenBtn() {
        var isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement);
        fullscreenBtn.innerHTML = isFullscreen
            ? '<i class="fa-solid fa-compress"></i>'
            : '<i class="fa-solid fa-expand"></i>';
        fullscreenBtn.title = isFullscreen ? 'Keluar Layar Penuh' : 'Layar Penuh';

        // Hitung ulang fit-width setelah transisi fullscreen benar-benar selesai.
        // Tidak cukup mengandalkan event 'resize' saja karena timing-nya bisa
        // mendahului browser selesai reflow ukuran container, sehingga canvas
        // sempat dirender dengan skala lama yang meluber dari kontainer.
        if (pdfDoc) {
            requestAnimationFrame(function () {
                requestAnimationFrame(fitWidth);
            });
        }
    }
    document.addEventListener('fullscreenchange', updateFullscreenBtn);
    document.addEventListener('webkitfullscreenchange', updateFullscreenBtn);

    // ── Event: Keyboard ──────────────────────────────────────
    document.addEventListener('keydown', function (e) {
        if (!pdfDoc) return;
        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
            if (currentPage < totalPages) renderPage(currentPage + 1);
        }
        if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
            if (currentPage > 1) renderPage(currentPage - 1);
        }
    });

    // ── Event: Swipe mobile ───────────────────────────────────
    var touchStartX = 0;
    container.addEventListener('touchstart', function (e) {
        touchStartX = e.touches[0].clientX;
    }, { passive: true });
    container.addEventListener('touchend', function (e) {
        var diff = touchStartX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 50) {
            if (diff > 0 && currentPage < totalPages) renderPage(currentPage + 1);
            if (diff < 0 && currentPage > 1)          renderPage(currentPage - 1);
        }
    }, { passive: true });

    // ── Event: Resize / orientasi berubah ────────────────────
    window.addEventListener('resize', function () {
        if (pdfDoc) fitWidth();
    });

    // ── Pencarian teks dalam dokumen ──────────────────────────
    function cachePageText(pageNum, textContent) {
        if (pagesText[pageNum]) return;
        // PDF.js hanya membuat <span> untuk item dengan teks (str tidak kosong),
        // jadi item kosong dilewati agar offsets tetap selaras index-nya dengan span hasil render
        var items   = textContent.items;
        var offsets = [];
        var full    = '';
        for (var i = 0; i < items.length; i++) {
            var str = items[i].str || '';
            if (str.length === 0) continue;
            offsets.push({ start: full.length, end: full.length + str.length });
            full += str + ' ';
        }
        pagesText[pageNum] = { fullTextLower: full.toLowerCase(), offsets: offsets, itemCount: offsets.length };
    }

    // Ekstrak teks semua halaman yang belum ter-cache (dibutuhkan agar pencarian
    // mencakup seluruh dokumen, bukan cuma halaman yang sudah pernah dibuka)
    function ensureAllPagesTextCached(onProgress) {
        var pending = [];
        for (var p = 1; p <= totalPages; p++) {
            if (!pagesText[p]) pending.push(p);
        }
        if (pending.length === 0) return Promise.resolve();

        var idx = 0;
        function next() {
            if (idx >= pending.length) return Promise.resolve();
            var p = pending[idx++];
            return pdfDoc.getPage(p).then(function (page) {
                return page.getTextContent();
            }).then(function (textContent) {
                cachePageText(p, textContent);
                if (onProgress) onProgress(idx, pending.length);
                return next();
            });
        }
        return next();
    }

    function computeMatches(term) {
        var list      = [];
        var termLower = term.toLowerCase();
        if (!termLower) return list;
        for (var p = 1; p <= totalPages; p++) {
            var data = pagesText[p];
            if (!data) continue;
            var text    = data.fullTextLower;
            var fromIdx = 0;
            var idx;
            while ((idx = text.indexOf(termLower, fromIdx)) !== -1) {
                list.push({ pageNum: p, start: idx, end: idx + termLower.length });
                fromIdx = idx + termLower.length;
            }
        }
        return list;
    }

    function updateSearchCount() {
        if (!currentSearchTerm) { searchCountEl.textContent = ''; return; }
        if (searchMatches.length === 0) { searchCountEl.textContent = 'Tidak ditemukan'; return; }
        searchCountEl.textContent = (currentMatchIdx + 1) + ' / ' + searchMatches.length;
    }

    // Tandai semua kecocokan pada halaman yang sedang tampil; kecocokan aktif
    // (currentMatchIdx) diberi warna berbeda & di-scroll ke tampilan
    function applyHighlightsOnPage(textLayerDiv, pageNum) {
        var oldMarks = textLayerDiv.querySelectorAll('.pdf-search-highlight');
        for (var m = 0; m < oldMarks.length; m++) {
            oldMarks[m].classList.remove('pdf-search-highlight', 'current');
        }

        if (!currentSearchTerm || searchMatches.length === 0) return;

        var spans = textLayerDiv.querySelectorAll('span');
        var data  = pagesText[pageNum];
        if (!data || spans.length !== data.itemCount) return;

        var activeMatch     = searchMatches[currentMatchIdx];
        var firstHighlightEl = null;

        for (var i = 0; i < searchMatches.length; i++) {
            var match = searchMatches[i];
            if (match.pageNum !== pageNum) continue;
            var isCurrent = (match === activeMatch);

            for (var s = 0; s < data.offsets.length; s++) {
                var off = data.offsets[s];
                if (off.start < match.end && off.end > match.start) {
                    spans[s].classList.add('pdf-search-highlight');
                    if (isCurrent) {
                        spans[s].classList.add('current');
                        if (!firstHighlightEl) firstHighlightEl = spans[s];
                    }
                }
            }
        }

        if (firstHighlightEl) {
            firstHighlightEl.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
    }

    function goToMatch(idx) {
        if (searchMatches.length === 0) return;
        idx = ((idx % searchMatches.length) + searchMatches.length) % searchMatches.length;
        currentMatchIdx = idx;
        updateSearchCount();
        btnSearchPrev.disabled = false;
        btnSearchNext.disabled = false;

        var match = searchMatches[idx];
        if (match.pageNum === currentPage) {
            var wrapper      = document.getElementById('pdf-page-wrapper');
            var textLayerDiv = wrapper ? wrapper.querySelector('.textLayer') : null;
            if (textLayerDiv) applyHighlightsOnPage(textLayerDiv, currentPage);
        } else {
            renderPage(match.pageNum);
        }
    }

    function doSearch(term) {
        term = term.trim();
        currentSearchTerm = term;
        currentMatchIdx   = -1;
        searchMatches     = [];

        if (!term) {
            updateSearchCount();
            return;
        }

        searchCountEl.textContent   = 'Mencari...';
        btnSearchPrev.disabled = true;
        btnSearchNext.disabled = true;

        ensureAllPagesTextCached(function (done, total) {
            searchCountEl.textContent = 'Mencari... ' + done + '/' + total;
        }).then(function () {
            if (term !== currentSearchTerm) return; // query sudah berubah, batalkan
            searchMatches = computeMatches(term);

            if (searchMatches.length === 0) {
                searchCountEl.textContent = 'Tidak ditemukan';
                return;
            }

            var startIdx = 0;
            for (var i = 0; i < searchMatches.length; i++) {
                if (searchMatches[i].pageNum >= currentPage) { startIdx = i; break; }
            }
            goToMatch(startIdx);
        }).catch(function (err) {
            console.error('Search error:', err);
            searchCountEl.textContent = 'Gagal mencari';
        });
    }

    function clearSearch() {
        currentSearchTerm = '';
        searchMatches     = [];
        currentMatchIdx   = -1;
        searchInput.value = '';
        searchCountEl.textContent  = '';
        btnSearchPrev.disabled = true;
        btnSearchNext.disabled = true;

        var wrapper      = document.getElementById('pdf-page-wrapper');
        var textLayerDiv = wrapper ? wrapper.querySelector('.textLayer') : null;
        if (textLayerDiv) {
            var marks = textLayerDiv.querySelectorAll('.pdf-search-highlight');
            for (var m = 0; m < marks.length; m++) {
                marks[m].classList.remove('pdf-search-highlight', 'current');
            }
        }
    }

    searchToggleBtn.addEventListener('click', function () {
        var showing = (searchBar.style.display === 'flex');
        if (showing) {
            searchBar.style.display = 'none';
            clearSearch();
        } else {
            searchBar.style.display = 'flex';
            searchInput.focus();
        }
    });

    btnSearchClose.addEventListener('click', function () {
        searchBar.style.display = 'none';
        clearSearch();
    });

    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var sameQuery = (searchMatches.length > 0 && currentSearchTerm === searchInput.value.trim());
            if (sameQuery) {
                goToMatch(currentMatchIdx + (e.shiftKey ? -1 : 1));
            } else {
                doSearch(searchInput.value);
            }
        } else if (e.key === 'Escape') {
            searchBar.style.display = 'none';
            clearSearch();
        }
    });

    btnSearchNext.addEventListener('click', function () { goToMatch(currentMatchIdx + 1); });
    btnSearchPrev.addEventListener('click', function () { goToMatch(currentMatchIdx - 1); });

})();
</script>

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
                                <a href="langganan" class="btn-upgrade-now">
                                    <i class="fa-solid fa-rocket"></i> Upgrade ke Premium Sekarang
                                </a>
                                <p class="small-text">Mulai dari Rp 49.999/bulan</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if(!empty($doc['uji_materi'])): ?>
                <div class="doc-description">
                    <h3>Uji Materi</h3>
                    <p><?php echo nl2br($doc['uji_materi']); ?></p>
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
                            <div class="status-konten"><?php echo nl2br($doc['dicabut']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($doc['dicabut_sebagian'])): ?>
                        <div class="status-box">
                            <span class="status-label"><i class="fa-solid fa-check"></i> Dicabut sebagian dengan : </span>
                            <div class="status-konten"><?php echo nl2br($doc['dicabut_sebagian']); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if(!empty($doc['mencabut'])): ?>
                        <div class="status-box">
                            <span class="status-label"><i class="fa-solid fa-check"></i> Mencabut :</span>
                            <div class="status-konten"><?php echo nl2br($doc['mencabut']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($doc['mencabut_sebagian'])): ?>
                        <div class="status-box">
                            <span class="status-label"><i class="fa-solid fa-check"></i> Mencabut sebagian :</span>
                            <div class="status-konten"><?php echo nl2br($doc['mencabut_sebagian']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($doc['diubah'])): ?>
                        <div class="status-box">
                            <span class="status-label"><i class="fa-solid fa-check"></i> Diubah dengan :</span>
                            <div class="status-konten"><?php echo nl2br($doc['diubah']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($doc['diubah_sebagian'])): ?>
                        <div class="status-box">
                            <span class="status-label"><i class="fa-solid fa-check"></i> Diubah sebagian dengan :</span>
                            <div class="status-konten"><?php echo nl2br($doc['diubah_sebagian']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($doc['mengubah'])): ?>
                        <div class="status-box">
                            <span class="status-label"><i class="fa-solid fa-check"></i> Mengubah :</span>
                            <div class="status-konten"><?php echo nl2br($doc['mengubah']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($doc['mengubah_sebagian'])): ?>
                        <div class="status-box">
                            <span class="status-label"><i class="fa-solid fa-check"></i> Mengubah sebagian :</span>
                            <div class="status-konten"><?php echo nl2br($doc['mengubah_sebagian']); ?></div>
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
                                <h4><a href="database-detail?id=<?php echo $pop['id']; ?>&slug=<?php echo $pop['slug']; ?>&kategori=<?php echo $pop['kategori']; ?>"><?php echo htmlspecialchars($pop['judul']); ?></a></h4>
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
                            <a href="database-detail?id=<?php echo $rec['id']; ?>&slug=<?php echo $rec['slug']; ?>&kategori=<?php echo $rec['kategori']; ?>">
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