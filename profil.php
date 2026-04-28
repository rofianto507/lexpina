<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'config/configuration.php';

// Ambil data user awal untuk pengecekan tipe user
try {
    $stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt_user->execute([$_SESSION['user_id']]);
    $user_awal = $stmt_user->fetch();
} catch (PDOException $e) {
    die("Error mengambil data user: " . $e->getMessage());
}

$is_manual_user = empty($user_awal['google_id']);

// ==========================================
// LOGIKA UPDATE PROFIL & PASSWORD
// ==========================================
$pesan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // --- 1. PROSES UPDATE NAMA ---
    if ($_POST['action'] == 'update_profil') {
        $nama_baru = trim($_POST['nama']);
        
        if (!empty($nama_baru)) {
            try {
                $stmt_update = $pdo->prepare("UPDATE users SET nama = ? WHERE id = ?");
                $stmt_update->execute([$nama_baru, $_SESSION['user_id']]);
                
                $_SESSION['user_nama'] = $nama_baru;
                $_SESSION['nama'] = $nama_baru;
                
                $pesan = '<div style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; border: 1px solid #c3e6cb;"><i class="fa-solid fa-circle-check"></i> Profil berhasil diperbarui!</div>';
            } catch (PDOException $e) {
                $pesan = '<div style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; border: 1px solid #f5c6cb;">Gagal menyimpan profil: ' . $e->getMessage() . '</div>';
            }
        } else {
            $pesan = '<div style="background-color: #fff3cd; color: #856404; padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; border: 1px solid #ffeeba;">Nama tidak boleh kosong!</div>';
        }
    }

    // --- 2. PROSES UPDATE PASSWORD ---
    if ($_POST['action'] == 'update_password' && $is_manual_user) {
        $pass_lama = $_POST['pass_lama'];
        $pass_baru = $_POST['pass_baru'];
        $pass_konfirm = $_POST['pass_konfirm'];

        // Cek apakah password lama cocok
        if (password_verify($pass_lama, $user_awal['password'])) {
            if ($pass_baru === $pass_konfirm) {
                if (strlen($pass_baru) >= 6) {
                    try {
                        $hash_baru = password_hash($pass_baru, PASSWORD_DEFAULT);
                        $stmt_pass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt_pass->execute([$hash_baru, $_SESSION['user_id']]);
                        
                        $pesan = '<div style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; border: 1px solid #c3e6cb;"><i class="fa-solid fa-circle-check"></i> Password berhasil diperbarui!</div>';
                    } catch (PDOException $e) {
                        $pesan = '<div style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; border: 1px solid #f5c6cb;">Gagal mengubah password: ' . $e->getMessage() . '</div>';
                    }
                } else {
                    $pesan = '<div style="background-color: #fff3cd; color: #856404; padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; border: 1px solid #ffeeba;">Password baru minimal harus 6 karakter.</div>';
                }
            } else {
                $pesan = '<div style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; border: 1px solid #f5c6cb;">Konfirmasi password tidak cocok!</div>';
            }
        } else {
            $pesan = '<div style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; border: 1px solid #f5c6cb;">Password lama yang Anda masukkan salah.</div>';
        }
    }
}

// Ambil data terbaru user setelah proses update (jika ada perubahan nama)
try {
    $stmt_user_updated = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt_user_updated->execute([$_SESSION['user_id']]);
    $user = $stmt_user_updated->fetch();
} catch (PDOException $e) {
    die("Error mengambil data user: " . $e->getMessage());
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'profil';
$active_page = ''; 

include 'header.php'; 
include 'navbar.php'; 
?>

    <main class="profile-page">
        <div class="news-container">
            
            <aside class="profile-sidebar">
                <div class="profile-card-user">
                    <div class="avatar-large">
                        <?php if(!empty($_SESSION['foto'])): ?>
                            <img src="<?php echo $_SESSION['foto']; ?>" alt="Foto Profil" referrerpolicy="no-referrer" class="profile-img">
                        <?php else: ?>
                            <i class="fa-solid fa-circle-user"></i>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo htmlspecialchars($user['nama']); ?></h3>
                    <p><?php echo htmlspecialchars($user['username']); ?></p>
                    
                   <?php if($user['akses'] == 'MEMBER'): ?>
                        <span class="badge-premium"><i class="fa-solid fa-crown"></i> Member Premium</span>
                        
                        <?php if(!empty($user['batas_langganan'])): ?>
                            <div class="premium-active-date">
                                Aktif s.d: <strong><?php echo date('d M Y', strtotime($user['batas_langganan'])); ?></strong>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <span class="badge-regular">Pengguna Biasa</span>
                    <?php endif; ?>
                </div>

                <ul class="profile-nav-list">
                    <li><a href="profil.php?tab=profil" class="<?php echo ($tab == 'profil') ? 'active' : ''; ?>"><i class="fa-regular fa-user"></i> Informasi Pribadi</a></li>
                    
                    <?php if($is_manual_user): ?>
                    <li><a href="profil.php?tab=password" class="<?php echo ($tab == 'password') ? 'active' : ''; ?>"><i class="fa-solid fa-key"></i> Ganti Password</a></li>
                    <?php endif; ?>

                    <li><a href="profil.php?tab=transaksi" class="<?php echo ($tab == 'transaksi') ? 'active' : ''; ?>"><i class="fa-solid fa-file-invoice-dollar"></i> Riwayat Transaksi</a></li>
                    <li>
                        <a href="profil.php?tab=notifikasi" class="<?php echo ($tab == 'notifikasi') ? 'active' : ''; ?>" style="display: flex; justify-content: space-between; align-items: center;">
                            <span><i class="fa-regular fa-bell"></i> Notifikasi</span>
                            <?php if($unread_notif_count > 0): ?>
                                <span style="background: #e74c3c; color: #fff; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: bold;"><?php echo $unread_notif_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li><a href="profil.php?tab=saran" class="<?php echo ($tab == 'saran') ? 'active' : ''; ?>"><i class="fa-solid fa-envelope-open-text"></i> Riwayat Saran & Masukan</a></li>
                    <li><a href="profil.php?tab=bookmark" class="<?php echo ($tab == 'bookmark') ? 'active' : ''; ?>"><i class="fa-regular fa-bookmark"></i> Dokumen Tersimpan</a></li>
                </ul>
            </aside>

            <div class="profile-main-content">
                <div class="profile-box">
                    
                    <?php if ($tab == 'profil'): ?>
                        <h2 class="section-title">Informasi Pribadi</h2>
                        
                        <?php echo $pesan; ?>

                        <form class="form-profile" method="POST" action="profil.php?tab=profil">
                            <input type="hidden" name="action" value="update_profil">
                            
                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama" value="<?php echo htmlspecialchars($user['nama']); ?>" class="form-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Alamat Email (Username)</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['username']); ?>" class="form-input input-readonly" readonly>
                                <?php if($is_manual_user): ?>
                                    <span class="form-note">Username/Email bersifat tetap dan tidak dapat diubah.</span>
                                <?php else: ?>
                                    <span class="form-note">Email terhubung dengan Google SSO dan tidak dapat diubah dari halaman ini.</span>
                                <?php endif; ?>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-save-profile">Simpan Perubahan</button>
                            </div>
                        </form>

                    <?php elseif ($tab == 'password' && $is_manual_user): ?>
                        <h2 class="section-title">Ganti Password</h2>
                        <p class="section-subtitle">Gunakan password yang kuat untuk menjaga keamanan akun Anda.</p>

                        <?php echo $pesan; ?>

                        <form class="form-profile" method="POST" action="profil.php?tab=password">
                            <input type="hidden" name="action" value="update_password">
                            
                            <div class="form-group">
                                <label>Password Lama</label>
                                <input type="password" name="pass_lama" class="form-input" required placeholder="Masukkan password saat ini">
                            </div>
                            
                            <div class="form-group">
                                <label>Password Baru</label>
                                <input type="password" name="pass_baru" class="form-input" required minlength="6" placeholder="Minimal 6 karakter">
                            </div>

                            <div class="form-group">
                                <label>Konfirmasi Password Baru</label>
                                <input type="password" name="pass_konfirm" class="form-input" required minlength="6" placeholder="Ketik ulang password baru">
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-save-profile" style="background-color: #f39c12;">Simpan Password Baru</button>
                            </div>
                        </form>

                    <?php elseif ($tab == 'transaksi'): ?>
                        <h2 class="section-title">Riwayat Transaksi & Langganan</h2>
                        <p class="section-subtitle">Pantau status pembayaran paket Premium Anda di sini.</p>

                        <?php
                        $stmt_trx = $pdo->prepare("SELECT t.*, p.nama_paket FROM transaksis t JOIN produks p ON t.produk_id = p.id WHERE t.user_id = ? ORDER BY t.created_at DESC");
                        $stmt_trx->execute([$_SESSION['user_id']]);
                        $transaksis = $stmt_trx->fetchAll();
                        ?>

                        <?php if (count($transaksis) > 0): ?>
                            <div class="table-responsive">
                                <table class="table-transactions">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Paket</th>
                                            <th>Total Transfer</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($transaksis as $trx): ?>
                                        <tr>
                                            <td><?php echo date('d M Y, H:i', strtotime($trx['created_at'])); ?></td>
                                            <td><strong>LexPina <?php echo htmlspecialchars($trx['nama_paket']); ?></strong></td>
                                            <td>Rp <?php echo number_format($trx['total_transfer'], 0, ',', '.'); ?></td>
                                            <td>
                                                <?php if($trx['status'] == 'PENDING'): ?>
                                                    <span class="status-badge badge-pending"><i class="fa-solid fa-clock"></i> Menunggu Verifikasi</span>
                                                <?php elseif($trx['status'] == 'LUNAS'): ?>
                                                    <span class="status-badge badge-lunas"><i class="fa-solid fa-check"></i> Lunas & Aktif</span>
                                                <?php elseif($trx['status'] == 'DITOLAK'): ?>
                                                    <span class="status-badge badge-ditolak"><i class="fa-solid fa-xmark"></i> Ditolak</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state-box">
                                <i class="fa-solid fa-receipt empty-state-icon"></i>
                                <p class="empty-state-text">Anda belum memiliki riwayat transaksi.</p>
                                <a href="langganan.php" class="btn-save-profile btn-link-action">Lihat Paket Premium</a>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($tab == 'notifikasi'): ?>
                        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px;">
                            <div>
                                <h2 class="section-title" style="margin-bottom: 5px;">Notifikasi</h2>
                                <p class="section-subtitle" style="margin-bottom: 0;">Pembaruan, promosi, dan penawaran terbaru untuk Anda.</p>
                            </div>
                            <?php if($unread_notif_count > 0): ?>
                                <a href="proses_notifikasi.php?action=read_all" class="btn-save-profile" style="padding: 8px 15px; font-size:12px; background:#f1c40f; color:#111; text-decoration:none; display: inline-flex; align-items: center;">
                                    <i class="fa-solid fa-check-double" style="margin-right: 5px;"></i> Tandai Semua Dibaca
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php
                        // Tarik semua notifikasi milik user ini
                        $stmt_notifs = $pdo->prepare("SELECT * FROM notifikasis WHERE user_id = ? ORDER BY created_at DESC");
                        $stmt_notifs->execute([$_SESSION['user_id']]);
                        $notifikasis = $stmt_notifs->fetchAll();
                        ?>

                        <?php if (count($notifikasis) > 0): ?>
                            <div class="notif-page-list">
                                <?php foreach($notifikasis as $notif): 
                                    $icon_class = 'fa-bell';
                                    $icon_color = '#666';
                                    if($notif['tipe'] == 'berita') { $icon_class = 'fa-newspaper'; $icon_color = '#3498db'; }
                                    if($notif['tipe'] == 'promosi') { $icon_class = 'fa-tags'; $icon_color = '#e74c3c'; }
                                    if($notif['tipe'] == 'tawaran') { $icon_class = 'fa-gift'; $icon_color = '#f1c40f'; }
                                    if($notif['tipe'] == 'pemberitahuan') { $icon_class = 'fa-info-circle'; $icon_color = '#2ecc71'; }
                                ?>
                                    <div class="history-item notif-clickable <?php echo ($notif['status'] == 0) ? 'unread-notif' : ''; ?>" 
                                         onclick="openNotifModal(this, <?php echo $notif['id']; ?>)"
                                         data-title="<?php echo htmlspecialchars($notif['judul']); ?>"
                                         data-date="<?php echo date('d M Y, H:i', strtotime($notif['created_at'])); ?>"
                                         data-content="<?php echo htmlspecialchars($notif['konten']); ?>"
                                         data-icon="<?php echo $icon_class; ?>"
                                         data-color="<?php echo $icon_color; ?>">
                                        
                                        <div class="notif-card-icon" style="color: <?php echo $icon_color; ?>;">
                                            <i class="fa-solid <?php echo $icon_class; ?>"></i>
                                        </div>
                                        
                                        <div class="notif-card-body">
                                            <div class="notif-card-header">
                                                <h3 class="notif-card-title">
                                                    <?php echo htmlspecialchars($notif['judul']); ?>
                                                </h3>
                                                <span class="notif-card-date">
                                                    <i class="fa-regular fa-clock"></i> <?php echo date('d M Y, H:i', strtotime($notif['created_at'])); ?>
                                                </span>
                                            </div>
                                            <p class="notif-card-excerpt">
                                                <?php echo substr(htmlspecialchars($notif['konten']), 0, 100); ?>... <span class="read-more-text">(Baca selengkapnya)</span>
                                            </p>
                                        </div>
                                        
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state-box">
                                <i class="fa-regular fa-bell-slash empty-state-icon"></i>
                                <p class="empty-state-text">Anda belum memiliki notifikasi.</p>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($tab == 'saran'): ?>
                        <h2 class="section-title">Riwayat Saran & Masukan</h2>
                        <p class="section-subtitle">Daftar masukan dan laporan yang pernah Anda kirimkan kepada tim LexPina.</p>

                        <?php
                        $stmt_saran = $pdo->prepare("SELECT * FROM sarans WHERE user_id = ? ORDER BY created_at DESC");
                        $stmt_saran->execute([$_SESSION['user_id']]);
                        $sarans = $stmt_saran->fetchAll();
                        ?>

                        <?php if (count($sarans) > 0): ?>
                            <div class="saran-history-list">
                                <?php foreach($sarans as $saran): ?>
                                    <div class="history-item">
                                        <div class="history-header">
                                            <h3 class="history-title">
                                                <?php echo htmlspecialchars($saran['judul']); ?>
                                            </h3>
                                            <?php if($saran['status'] == 0): ?>
                                                <span class="history-badge badge-sent">
                                                    <i class="fa-solid fa-paper-plane"></i> Terkirim
                                                </span>
                                            <?php else: ?>
                                                <span class="history-badge badge-reviewed">
                                                    <i class="fa-solid fa-check-double"></i> Telah Ditinjau
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="history-date">
                                            <i class="fa-regular fa-calendar"></i> <?php echo date('d M Y, H:i', strtotime($saran['created_at'])); ?>
                                        </div>
                                        <div class="history-content">
                                            <?php echo nl2br(htmlspecialchars($saran['konten'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state-box">
                                <i class="fa-solid fa-envelope-open-text empty-state-icon"></i>
                                <p class="empty-state-text">Anda belum pernah mengirimkan saran atau masukan.</p>
                                <a href="saran.php" class="btn-save-profile btn-link-action">
                                    <i class="fa-solid fa-pen-to-square"></i> Tulis Saran Sekarang
                                </a>
                            </div>
                        <?php endif; ?>

                    <?php elseif ($tab == 'bookmark'): ?>
                        <h2 class="section-title">Dokumen Tersimpan</h2>
                        <p class="section-subtitle">Kumpulan peraturan dan putusan yang telah Anda simpan untuk dibaca kembali.</p>

                        <?php
                        // Tarik data bookmark di-JOIN dengan tabel databases
                        $stmt_bms = $pdo->prepare("
                            SELECT b.dokumen_id, d.judul, d.kategori, d.tanggal_penetapan, d.views 
                            FROM bookmarks b 
                            JOIN `databases` d ON b.dokumen_id = d.id 
                            WHERE b.user_id = ? 
                            ORDER BY b.created_at DESC
                        ");
                        $stmt_bms->execute([$_SESSION['user_id']]);
                        $bookmarks = $stmt_bms->fetchAll();
                        ?>

                        <?php if (count($bookmarks) > 0): ?>
                            <div class="bookmark-list">
                                <?php foreach($bookmarks as $bm): 
                                    $cat_name = ucwords(str_replace('-', ' ', $bm['kategori']));
                                ?>
                                    <div class="history-item" style="display: flex; justify-content: space-between; align-items: center;">
                                        <div class="bookmark-info">
                                            <span class="history-badge" style="background:#eee; color:#333; margin-left:0; margin-bottom:10px; display:inline-block;">
                                                <?php echo $cat_name; ?>
                                            </span>
                                            <h3 class="history-title" >
                                                <a href="database_detail.php?id=<?php echo $bm['dokumen_id']; ?>&kategori=<?php echo $bm['kategori']; ?>" >
                                                    <?php echo htmlspecialchars($bm['judul']); ?>
                                                </a>
                                            </h3>
                                            <div class="history-date" style="margin-bottom: 0;">
                                                <i class="fa-regular fa-calendar"></i> <?php echo date('d M Y', strtotime($bm['tanggal_penetapan'])); ?> 
                                                <span style="margin: 0 10px;">|</span>
                                                <i class="fa-solid fa-eye"></i> <?php echo number_format($bm['views'], 0, ',', '.'); ?> Views
                                            </div>
                                        </div>
                                        
                                        <div class="bookmark-action">
                                            <a href="proses_bookmark.php?id=<?php echo $bm['dokumen_id']; ?>&action=remove&ref=profil" class="btn-bookmark bookmarked" title="Hapus dari Tersimpan" style="position: static; padding: 10px;">
                                                <i class="fa-solid fa-bookmark"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state-box">
                                <i class="fa-regular fa-bookmark empty-state-icon"></i>
                                <p class="empty-state-text">Anda belum menyimpan dokumen apapun.</p>
                                <a href="database.php" class="btn-save-profile btn-link-action">
                                    <i class="fa-solid fa-magnifying-glass"></i> Cari Dokumen Hukum
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
    <div id="notifDetailModal" class="modal-overlay" style="visibility: hidden; opacity: 0; transition: all 0.3s ease; z-index: 9999;">
        <div class="modal-content" style="max-width: 500px; text-align: left; padding: 30px;">
            <button type="button" class="modal-close" onclick="closeNotifModal()"><i class="fa-solid fa-xmark"></i></button>
            
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                <div id="modalNotifIcon" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px;"></div>
                <div>
                    <h2 id="modalNotifTitle" style="margin: 0 0 5px 0; font-size: 18px; color: #111;">Judul</h2>
                    <span id="modalNotifDate" style="font-size: 12px; color: #999;">Tanggal</span>
                </div>
            </div>
            
            <div class="modal-body">
                <p id="modalNotifContent" style="font-size: 15px; color: #444; line-height: 1.8; white-space: pre-wrap; margin: 0;"></p>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>