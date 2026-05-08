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
                                                <a href="database_detail.php?id=<?= $kon['id'] ?>&kategori=<?= $kon['kategori'] ?>" style="color: #2c3e50; font-weight: 700; text-decoration: none; font-size: 14px;">
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
        </div>
        <div class="toolbar-group">
            <button id="btn-zoom-out"  title="Perkecil"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
            <span id="zoom-level">100%</span>
            <button id="btn-zoom-in"   title="Perbesar"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
            <button id="btn-zoom-fit"  title="Fit Lebar">Fit</button>
            <button id="btn-fullscreen" title="Layar Penuh"><i class="fa-solid fa-expand"></i></button>
        </div>
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
        pdfDoc.getPage(1).then(function (page) {
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

            // Hapus canvas lama, buat baru
            container.innerHTML = '';
            var canvas    = document.createElement('canvas');
            var ctx       = canvas.getContext('2d');
            canvas.width  = Math.floor(viewport.width  * dpr);
            canvas.height = Math.floor(viewport.height * dpr);
            canvas.style.width  = Math.floor(viewport.width)  + 'px';
            canvas.style.height = Math.floor(viewport.height) + 'px';
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            container.appendChild(canvas);

            return page.render({ canvasContext: ctx, viewport: viewport }).promise.then(function () {
                currentPage            = pageNum;
                pageInfoEl.textContent = 'Hal ' + currentPage + ' / ' + totalPages;
                updateButtons();
                isRendering = false;

                if (renderQueue !== null) {
                    var next    = renderQueue;
                    renderQueue = null;
                    renderPage(next);
                }
            });

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
    document.getElementById('btn-fullscreen').addEventListener('click', function () {
        var wrapper = document.getElementById('pdf-main-wrapper');
        if (!document.fullscreenElement && !document.webkitFullscreenElement) {
            (wrapper.requestFullscreen || wrapper.webkitRequestFullscreen).call(wrapper);
        } else {
            (document.exitFullscreen || document.webkitExitFullscreen).call(document);
        }
    });

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