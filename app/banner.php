<?php
session_start();
 
include("../config/configuration.php");
if(!isset($_SESSION["id"]) || !isset($_SESSION["nama"])) {
  header("Location: ../login/");
  exit;
}
 if(empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  $_SESSION["menu"]="banner";
 
  $menu=$_SESSION["menu"];
  $nama=$_SESSION["nama"];
  $id=$_SESSION["id"];
  $username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
  $foto=$_SESSION["foto"];
  $last_login=$_SESSION["last_login"];
 
// ==========================================
// PROSES PENANGANAN FORM (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Akses ditolak! Token keamanan tidak valid.");
    }

    $judul     = $_POST['judul'];
    $link_url  = $_POST['link_url'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Ambil data versi dan gambar lama
    $stmt_old = $pdo->query("SELECT gambar, versi FROM banners WHERE id = 1");
    $old_data = $stmt_old->fetch(PDO::FETCH_ASSOC);
    
    $file_url = $old_data['gambar'];
    $versi_baru = $old_data['versi']; 

    // Logika Upload File
    $gambar_diubah = false;
    if (isset($_FILES['file_image']) && $_FILES['file_image']['error'] == 0) {
        $ext = pathinfo($_FILES['file_image']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $upload_dir = '../public/upload/banners/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $file_name = 'banner_' . time() . '.' . $ext;
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['file_image']['tmp_name'], $target_file)) {
                if(!empty($file_url) && file_exists($upload_dir . $file_url)) {
                    unlink($upload_dir . $file_url); // Hapus gambar lama
                }
                $file_url = $file_name;
                $gambar_diubah = true;
            }
        } else {
            die("Format gambar tidak didukung!");
        }
    }

    // Jika gambar diubah atau status diaktifkan ulang, naikkan versi agar frontend memunculkan popup lagi
    if($gambar_diubah || (isset($_POST['reset_popup']) && $_POST['reset_popup'] == '1')){
        $versi_baru = $old_data['versi'] + 1;
    }

    try {
        $stmt_update = $pdo->prepare("UPDATE banners SET judul = ?, link_url = ?, is_active = ?, gambar = ?, versi = ? WHERE id = 1");
        $stmt_update->execute([$judul, $link_url, $is_active, $file_url, $versi_baru]);
        
        header("Location: banner?msg=sukses_update");
        exit;
    } catch (PDOException $e) {
        die("Error mengubah data: " . $e->getMessage());
    }
}
// AMBIL DATA BANNER SAAT INI
$stmt = $pdo->query("SELECT * FROM banners WHERE id = 1");
$banner = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Banner | LexPina</title>

    <!-- Favicons, CSS, dan Theme -->
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/img/favicons/favicon-16x16.png">
    <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicons/favicon.ico">
    <link rel="manifest" href="../assets/img/favicons/manifest.json">
    <meta name="msapplication-TileImage" content="../assets/img/favicons/mstile-150x150.png">
    <meta name="theme-color" content="#ffffff">
    <script src="../assets/js/config.js"></script>
    <script src="../vendors/overlayscrollbars/OverlayScrollbars.min.js"></script>
     <link rel="stylesheet" type="text/css" href="../vendors/datatables/datatables.min.css"/>
    <link rel="stylesheet" type="text/css" href="../assets/icon/font-awesome/css/font-awesome.min.css">
    <link href="../vendors/prism/prism-okaidia.css" rel="stylesheet">
     <link href="../vendors/overlayscrollbars/OverlayScrollbars.min.css" rel="stylesheet">
    <link href="../assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
    <link href="../assets/css/theme.min.css" rel="stylesheet" id="style-default">
    <link href="../assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
    <link href="../assets/css/user.min.css" rel="stylesheet" id="user-style-default">
        <link href="../assets/css/database.css" rel="stylesheet">
    <script>
    var isRTL = JSON.parse(localStorage.getItem('isRTL'));
    if (isRTL) {
      var linkDefault = document.getElementById('style-default');
      var userLinkDefault = document.getElementById('user-style-default');
      linkDefault.setAttribute('disabled', true);
      userLinkDefault.setAttribute('disabled', true);
      document.querySelector('html').setAttribute('dir', 'rtl');
    } else {
      var linkRTL = document.getElementById('style-rtl');
      var userLinkRTL = document.getElementById('user-style-rtl');
      linkRTL.setAttribute('disabled', true);
      userLinkRTL.setAttribute('disabled', true);
    }
    </script>
  </head>
  <body  >
    <main class="main" id="top">
      <div class="container-fluid" data-layout="container">
        <?php include_once("navbar.php") ?>
        <div class="content">
          <?php include_once("header.php") ?>
          
          <?php if(isset($_GET['msg']) && $_GET['msg'] == 'sukses_update'): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">Pengaturan banner berhasil diperbarui.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
          <?php endif; ?>

          <div class="d-flex mb-4 mt-3">
            <span class="fa fa-picture-o me-2 fs-3 text-primary"></span>
            <div>
              <h4 class="mb-0">Banner Promosi & Pengumuman</h4>
              <span class="text-muted">Muncul sebagai popup satu kali kepada setiap pengunjung web.</span>
            </div>
          </div>

          <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="row g-3">
              <div class="col-lg-7">
                <div class="card mb-3">
                  <div class="card-header bg-light">
                    <h6 class="mb-0">Pengaturan Konten Banner</h6>
                  </div>
                  <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Judul (Hanya untuk catatan internal)</label>
                        <input type="text" class="form-control" name="judul" value="<?php echo htmlspecialchars($banner['judul']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Link Tujuan (Opsional)</label>
                        <input type="url" class="form-control" name="link_url" value="<?php echo htmlspecialchars($banner['link_url']); ?>" placeholder="https://...">
                        <small class="text-muted">Jika pengguna mengklik gambar banner, mereka akan diarahkan ke link ini.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><i class="fa fa-upload text-primary"></i> Upload Gambar Banner Baru</label>
                        <input type="file" class="form-control" name="file_image" accept="image/*">
                        <small class="text-muted">Ukuran yang disarankan: 800x600 px (Landscape). Mengupload gambar baru akan otomatis memaksa popup muncul lagi ke semua pengguna.</small>
                    </div>

                    <hr class="my-4">

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="statusBanner" name="is_active" value="1" <?php echo ($banner['is_active'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold text-dark" for="statusBanner" style="cursor:pointer;">Aktifkan Popup Banner di Website</label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="resetPopup" name="reset_popup" value="1">
                        <label class="form-check-label text-danger" for="resetPopup">
                           Paksa Munculkan Ulang (Tanpa ganti gambar)
                        </label>
                        <br><small class="text-muted">Centang jika Anda ingin memaksa semua pengguna melihat banner ini lagi meskipun mereka sudah pernah menutupnya.</small>
                    </div>

                  </div>
                  <div class="card-footer bg-light text-end">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan Pengaturan</button>
                  </div>
                </div>
              </div>

              <div class="col-lg-5">
                <div class="card mb-3">
                  <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fa fa-eye text-info me-2"></i>Preview Banner Aktif</h6>
                  </div>
                  <div class="card-body text-center bg-200">
                    <?php if(!empty($banner['gambar'])): ?>
                        <img src="../public/upload/banners/<?php echo htmlspecialchars($banner['gambar']); ?>" class="img-fluid rounded shadow-sm" alt="Banner Preview">
                        <div class="mt-3 text-start">
                            <span class="badge bg-secondary mb-1">Versi Banner: <?php echo $banner['versi']; ?></span><br>
                            <span class="text-muted fs--1">Terakhir diupdate: <?php echo date('d M Y H:i', strtotime($banner['updated_at'])); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="p-5 border border-dashed rounded bg-white text-muted">
                            <i class="fa fa-image fs-4 mb-2"></i><br>
                            Belum ada gambar banner.
                        </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </form>

          <?php include_once("footer.php") ?>
        </div>
      </div>
    </main>
    <!-- Scripts -->
<script src="../vendors/jquery/jquery-3.7.0.min.js"></script>
    <script src="../vendors/datatables/datatables.min.js"></script>
            
    <script src="../vendors/popper/popper.min.js"></script>
    <script src="../vendors/bootstrap/bootstrap.min.js"></script>
    <script src="../vendors/anchorjs/anchor.min.js"></script>
    <script src="../vendors/is/is.min.js"></script>
    <script src="../vendors/prism/prism.js"></script>
    <script src="../vendors/lodash/lodash.min.js"></script>
    <script src="../vendors/list.js/list.min.js"></script>
    <script src="../assets/js/theme.js"></script>
  </body>
</html>
 