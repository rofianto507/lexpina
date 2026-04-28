<?php
// 1. Wajib di baris pertama: Memulai sesi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Memanggil file koneksi & konfigurasi global Anda
require_once 'config/configuration.php';
include 'track_visitor.php';
// Inisialisasi variabel notifikasi default
$unread_notif_count = 0;
$notifikasi_list = [];

// ==========================================
// 3. SINKRONISASI SESI, CEK EXPIRED & DATABASE
// ==========================================
if(isset($_SESSION['user_id'])) {
    try {
        // Ambil status akses dan batas langganan terbaru
        $stmt_sync = $pdo->prepare("SELECT akses, batas_langganan FROM users WHERE id = ?");
        $stmt_sync->execute([$_SESSION['user_id']]);
        $user_sync = $stmt_sync->fetch();

        if($user_sync) {
            $akses_terkini = $user_sync['akses'];

            // Logika Auto-Downgrade: Cek jika user adalah MEMBER dan punya batas waktu
            if ($akses_terkini == 'MEMBER' && !empty($user_sync['batas_langganan'])) {
                
                $waktu_sekarang = new DateTime(); // Waktu saat ini
                $batas_waktu = new DateTime($user_sync['batas_langganan']); // Waktu expired dari database

                // Jika waktu sekarang sudah melewati batas langganan...
                if ($waktu_sekarang > $batas_waktu) {
                    // 1. Turunkan akses di database kembali menjadi PENGGUNA
                    $update_kasta = $pdo->prepare("UPDATE users SET akses = 'PENGGUNA' WHERE id = ?");
                    $update_kasta->execute([$_SESSION['user_id']]);
                    
                    // 2. Perbarui variabel agar session juga ikut turun kasta
                    $akses_terkini = 'PENGGUNA';
                }
            }

            // Timpa session lama dengan status terbaru
            $_SESSION['akses'] = $akses_terkini;

            // ==========================================
            // AMBIL DATA NOTIFIKASI USER
            // ==========================================
            // A. Hitung jumlah yang belum dibaca
            $stmt_notif_count = $pdo->prepare("SELECT COUNT(*) FROM notifikasis WHERE user_id = ? AND status = 0");
            $stmt_notif_count->execute([$_SESSION['user_id']]);
            $unread_notif_count = $stmt_notif_count->fetchColumn();

            // B. Tarik 5 notifikasi terbaru untuk ditampilkan di dropdown
            $stmt_notif_list = $pdo->prepare("SELECT * FROM notifikasis WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
            $stmt_notif_list->execute([$_SESSION['user_id']]);
            $notifikasi_list = $stmt_notif_list->fetchAll();

        } else {
            // Force logout jika user ternyata dihapus oleh admin
            session_unset();
            session_destroy();
            header("Location: " . $path . "index.php");
            exit();
        }
    } catch (PDOException $e) {
        // Biarkan kosong agar website tidak nge-blank jika ada error minor
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $app_name; ?> - <?php echo $app_description; ?></title>
    
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Playfair+Display:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="<?php echo $path; ?>assets/css/style.css">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>

    <header class="top-header main-header">
        <div class="logo">
            <a href="<?php echo $path; ?>index.php">
                <img src="<?php echo $path; ?>assets/img/logo.png" alt="Logo LexPina" class="logo-img">
            </a>
        </div>
        <div style="display: flex; align-items: center;">
            <?php if(isset($_SESSION['user_id'])) { ?>
            <div class="header-user-actions" style="display: flex; align-items: center; gap: 20px;">
                <?php if(empty($_SESSION['akses']) || $_SESSION['akses'] != 'MEMBER') { ?>
                    <a href="<?php echo $path; ?>langganan.php" class="btn-subscribe-header">Subscribe</a>
                <?php } ?>

                <div class="user-profile dropdown-profile">
                    <div class="profile-trigger" style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        
                        <?php if(isset($_SESSION['akses']) && $_SESSION['akses'] == 'MEMBER') { ?>
                            <i class="fa-solid fa-crown" style="color: gold;" title="Member Premium"></i>
                        <?php } ?>

                        <span>Halo, <strong><?php echo htmlspecialchars($_SESSION['user_nama']); ?>!</strong></span>
                        
                        <div style="position: relative; display: flex; align-items: center;">
                            <?php if(!empty($_SESSION['foto'])) { ?>
                                <img src="<?php echo htmlspecialchars($_SESSION['foto']); ?>" alt="Foto Profil" referrerpolicy="no-referrer" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                            <?php } else { ?>
                                <i class="fa-solid fa-circle-user profile-icon" style="font-size: 35px; color: #ccc;"></i>
                            <?php } ?>

                            <?php if($unread_notif_count > 0): ?>
                                <span class="notif-badge-profile"><?php echo $unread_notif_count; ?></span>
                            <?php endif; ?>
                        </div>

                    </div>
                    
                    <div class="profile-menu">
                        <a href="<?php echo $path; ?>profil.php?tab=profil"><i class="fa-regular fa-id-badge"></i> Profil Saya</a>
                        
                        

                        <hr>
                        <a href="#" id="btnLogoutTrigger" class="logout-text"><i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar</a>
                    </div>
                </div>

            </div>

            <?php } else { ?>
            <div class="auth-buttons" style="display: flex; align-items: center; gap: 10px;">
                <a href="<?php echo $path; ?>langganan.php" class="btn-subscribe-header">Subscribe</a>
                <button type="button" id="btnOpenLogin" class="btn-signin">Sign In</button>
            </div>
            <?php } ?>
            
            <button type="button" id="btnThemeToggle" class="btn-theme" title="Ubah Tema" style="margin-left: 20px;">
                <i class="fa-solid fa-moon"></i>
            </button>
        </div>
    </header>

    <div id="loginModal" class="modal-overlay">
        <div class="modal-content login-modal-content">
            <button type="button" id="btnCloseLogin" class="modal-close"><i class="fa-solid fa-xmark"></i></button>
            
            <div class="modal-header">
                <img src="<?php echo $path; ?>assets/img/icon.png" alt="LexPina Logo" class="modal-logo">
                <h2>Selamat Datang</h2>
                <p>Masuk ke akun LexPina Anda</p>
            </div>

            <div class="modal-body">
                <form id="formLoginManual">
                    
                    <div class="input-group-custom">
                        <label for="loginUserEmail">Username atau Email</label>
                        <input type="text" name="user_email" id="loginUserEmail" placeholder="Masukkan username atau email" required autocomplete="username">
                    </div>
                    <div class="input-group-custom">
                        <label for="loginPassword">Password</label>
                        <input type="password" name="password" id="loginPassword" placeholder="********" required autocomplete="current-password">
                    </div>
                    
                    <div id="loginErrorMessage" class="login-error-message"></div>
                    
                    <button type="submit" id="btnSubmitLogin" class="btn-primary-block">Sign In</button>
                </form>

                <div class="modal-divider">
                    <span>atau</span>
                </div>

                <div class="google-signin-wrapper">
                    <div id="g_id_onload" data-client_id="36354248807-n36q4bh9dnrau56roo9f13lrlfokb0nn.apps.googleusercontent.com" data-context="signin" data-ux_mode="popup" data-callback="handleGoogleLogin" data-auto_prompt="false"></div>
                    <div class="g_id_signin" data-type="standard" data-shape="rectangular" data-theme="outline" data-text="continue_with" data-size="large" data-logo_alignment="left" data-width="340"></div>
                </div>
            </div>

            <div class="modal-footer login-modal-footer">
                <p>Belum punya akun? <a href="javascript:void(0)" id="btnToSignUp" class="link-signup">Daftar Sekarang</a></p>
            </div>
        </div>
    </div>
    <div id="registerModal" class="modal-overlay">
        <div class="modal-content login-modal-content">
            <button type="button" id="btnCloseRegister" class="modal-close"><i class="fa-solid fa-xmark"></i></button>
            
            <div class="modal-header">
                <img src="<?php echo $path; ?>assets/img/icon.png" alt="LexPina Logo" class="modal-logo">
                <h2>Buat Akun Baru</h2>
                <p>Bergabunglah dengan LexPina sekarang</p>
            </div>

            <div class="modal-body">
                <form id="formRegisterManual">
                    <div class="input-group-custom">
                        <label for="regNama">Nama Lengkap</label>
                        <input type="text" name="nama" id="regNama" placeholder="Masukkan nama lengkap" required>
                    </div>
                    <div class="input-group-custom">
                        <label for="regEmail">Alamat Email</label>
                        <input type="email" name="email" id="regEmail" placeholder="Masukkan alamat email aktif" required>
                    </div>
                    <div class="input-group-custom">
                        <label for="regPassword">Password</label>
                        <input type="password" name="password" id="regPassword" placeholder="Minimal 6 karakter" required minlength="6">
                    </div>
                    <div class="input-group-custom">
                        <label for="regPasswordConfirm">Konfirmasi Password</label>
                        <input type="password" name="password_confirm" id="regPasswordConfirm" placeholder="Ketik ulang password" required minlength="6">
                    </div>
                    
                    <div id="registerErrorMessage" class="login-error-message"></div>
                    
                    <button type="submit" id="btnSubmitRegister" class="btn-primary-block">Daftar Sekarang</button>
                </form>
            </div>

            <div class="modal-footer login-modal-footer">
                <p>Sudah punya akun? <a href="javascript:void(0)" id="btnToSignIn" class="link-signup">Sign In</a></p>
            </div>
        </div>
    </div>
    <div id="logoutModal" class="modal-overlay">
        <div class="modal-content modal-sm">
            <div class="modal-icon-warning">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </div>
            <h2>Konfirmasi Keluar</h2>
            <p>Apakah Anda yakin ingin keluar dari akun LexPina?</p>
            <div class="modal-actions">
                <button type="button" id="btnCancelLogout" class="btn-cancel-modal">Batal</button>
                <a href="<?php echo $path; ?>logout.php" class="btn-confirm-logout">Ya, Keluar</a>
            </div>
        </div>
    </div>