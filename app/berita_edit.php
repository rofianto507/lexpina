<?php
session_start();
include("../config/configuration.php");

// 1. CEK AUTENTIKASI
if(!isset($_SESSION["id"]) || !isset($_SESSION["nama"])) {
  header("Location: ../login/");
  exit;
}

if(empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
  $nama=$_SESSION["nama"];
  $id=$_SESSION["id"];
  $username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
  $foto=$_SESSION["foto"];
  $last_login=$_SESSION["last_login"];

$id_berita = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$_SESSION["menu"] = "berita";

// ==========================================
// PROSES PENANGANAN FORM UPDATE (POST)
// ==========================================
if (isset($_POST["action"]) && $_POST['action'] == 'edit_berita') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Akses ditolak! Token keamanan tidak valid.");
    }

    $judul       = $_POST['judul'];
    $kategori_id = (int)$_POST['kategori_id'];
    $konten      = $_POST['konten'];
    $slug        = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul)));
    
    // Ambil nama file gambar lama
    $file_url = $_POST['file_lama']; 

    // Logika Upload File Gambar Baru (Jika Ada)
    if (isset($_FILES['file_image']) && $_FILES['file_image']['error'] == 0) {
        $ext = pathinfo($_FILES['file_image']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $upload_dir = '../public/upload/news/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $file_name = time() . '_' . md5($_FILES['file_image']['name']) . '.' . $ext;
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['file_image']['tmp_name'], $target_file)) {
                // Hapus file lama jika file baru berhasil diupload
                if(!empty($file_url) && file_exists($upload_dir . $file_url)) {
                    unlink($upload_dir . $file_url);
                }
                $file_url = $file_name; // Gunakan gambar baru
            }
        } else {
            die("Hanya file gambar (JPG, PNG, GIF) yang diizinkan!");
        }
    }

    try {
        $stmt_update = $pdo->prepare("UPDATE `beritas` SET 
            kategori_id = ?, judul = ?, slug = ?, konten = ?, gambar = ? 
            WHERE id = ?");
        
        $stmt_update->execute([
            $kategori_id, $judul, $slug, $konten, $file_url, $id_berita
        ]);

        header("Location: berita?status=sukses");
        exit;
    } catch (PDOException $e) {
        die("Error mengubah data berita: " . $e->getMessage());
    }
}

// ==========================================
// AMBIL DATA BERITA SAAT INI
// ==========================================
try {
    $stmt = $pdo->prepare("SELECT * FROM `beritas` WHERE id = ?");
    $stmt->execute([$id_berita]);
    $berita = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$berita) {
        die("Data berita tidak ditemukan!");
    }

    $views = isset($berita['views']) ? (int)$berita['views'] : 0;

} catch (PDOException $e) {
    die("Error mengambil data: " . $e->getMessage());
}

// ==========================================
// AMBIL DATA KOMENTAR (DENGAN FALLBACK AMAN)
// ==========================================
$komentar_list = [];
$total_komentar = 0;

try {
    // Asumsi tabel bernama 'komentars' berelasi dengan tabel 'users'
    $stmt_komen = $pdo->prepare("
        SELECT k.*, u.nama, u.foto 
        FROM komentars k 
        LEFT JOIN users u ON k.user_id = u.id 
        WHERE k.berita_id = ? AND k.status = 1 
        ORDER BY k.created_at DESC
    ");
    $stmt_komen->execute([$id_berita]);
    $komentar_list = $stmt_komen->fetchAll(PDO::FETCH_ASSOC);
    $total_komentar = count($komentar_list);
} catch (PDOException $e) {
    // Dibiarkan kosong agar halaman tidak error jika tabel komentar belum dibuat
}
// ==========================================
    // LOGIKA HAPUS KOMENTAR
    // ==========================================
    if (isset($_POST['action']) && $_POST['action'] == 'hapus_komentar') {
        $komen_id = (int)$_POST['komentar_id'];
        try {
            // Menggunakan soft delete (status = 0) atau bisa diganti DELETE FROM
            $stmt_del_komen = $pdo->prepare("UPDATE komentars SET status = 0 WHERE id = ?");
            $stmt_del_komen->execute([$komen_id]);
            
            // Redirect ke halaman yang sama dengan pesan sukses
            header("Location: berita-edit?id=" . $id_berita . "&msg=komen_dihapus");
            exit;
        } catch (PDOException $e) {
            die("Error menghapus komentar: " . $e->getMessage());
        }
    }

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Berita | LexPina</title>
 <link rel="apple-touch-icon" sizes="180x180" href="../assets/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/img/favicons/favicon-16x16.png">
    <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicons/favicon.ico">
    <link rel="manifest" href="../assets/img/favicons/manifest.json">
    <meta name="msapplication-TileImage" content="../assets/img/favicons/mstile-150x150.png">
    <meta name="theme-color" content="#ffffff">
    <script src="../assets/js/config.js"></script>
    <script src="../vendors/overlayscrollbars/OverlayScrollbars.min.js"></script>
    <link rel="stylesheet" type="text/css" href="../assets/icon/font-awesome/css/font-awesome.min.css">
    <link href="../vendors/prism/prism-okaidia.css" rel="stylesheet">
     <link href="../vendors/overlayscrollbars/OverlayScrollbars.min.css" rel="stylesheet">
    <link href="../assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
    <link href="../assets/css/theme.min.css" rel="stylesheet" id="style-default">
    <link href="../assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
    <link href="../assets/css/user.min.css" rel="stylesheet" id="user-style-default">
    <style>
        .comment-item { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px; }
        .comment-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .comment-avatar { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; }
    </style>
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
  <body>
    <main class="main" id="top">
      <div class="container-fluid" data-layout="container">
        <?php include_once("navbar.php") ?>
        <div class="content">
          <?php include_once("header.php") ?>
          
          <div class="d-flex mb-4 mt-3">
            <span class="fa fa-pencil-square-o me-2 fs-3 text-primary"></span>
            <div>
              <h4 class="mb-0">Edit Berita</h4>
              <span class="text-muted">Perbarui konten artikel dan pantau interaksi pengguna.</span>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-lg-8">
              <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="file_lama" value="<?php echo htmlspecialchars($berita['gambar']); ?>">
                  <input type="hidden" name="action" value="edit_berita">
                <div class="card mb-3">
                  <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Detail Artikel</h6>
                    <span class="badge bg-primary">Dipublikasikan: <?php echo date('d M Y', strtotime($berita['created_at'])); ?></span>
                  </div>
                  <div class="card-body">
                    <div class="row g-3">
                      
                      <div class="col-md-12">
                        <label class="form-label">Judul Berita</label>
                        <input type="text" class="form-control" name="judul" value="<?php echo htmlspecialchars($berita['judul']); ?>" required>
                      </div>
                      
                      <div class="col-md-12">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="kategori_id" required>
                          <?php
                            $stmt_kategori = $pdo->prepare("SELECT id, nama_kategori FROM kategoris ORDER BY nama_kategori ASC");
                            $stmt_kategori->execute();
                            while($kategori = $stmt_kategori->fetch(PDO::FETCH_ASSOC)) {
                                $selected = ($berita['kategori_id'] == $kategori['id']) ? 'selected' : '';
                                echo "<option value='".$kategori['id']."' ".$selected.">".htmlspecialchars($kategori['nama_kategori'])."</option>";
                            }
                          ?>
                        </select>
                      </div>

                      <div class="col-md-12">
                        <label class="form-label">Konten Artikel</label>
                        <textarea class="form-control" name="konten" rows="12" required><?php echo htmlspecialchars($berita['konten']); ?></textarea>
                      </div>

                      <div class="col-md-12 mt-4">
                        <div class="row align-items-center p-3 bg-light rounded border">
                            <div class="col-md-3 text-center">
                                <?php if(!empty($berita['gambar'])): ?>
                                    <img src="../public/upload/news/<?php echo $berita['gambar']; ?>" class="img-thumbnail" style="max-height: 120px;" alt="Cover Berita">
                                <?php else: ?>
                                    <div class="p-3 border border-dashed rounded text-muted">Tak ada gambar</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-9">
                                <label class="form-label text-primary fw-bold"><i class="fa fa-picture-o"></i> Ganti Cover Gambar</label>
                                <input type="file" class="form-control" name="file_image" accept="image/*">
                                <small class="text-muted mt-1 d-block">Biarkan kosong jika tidak ingin mengganti gambar.</small>
                            </div>
                        </div>
                      </div>

                    </div>
                  </div>
                  <div class="card-footer bg-light text-end">
                    <a href="berita" class="btn btn-secondary me-2"><i class="fa fa-times"></i> Batal</a>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan Perubahan</button>
                  </div>
                </div>
              </form>
            </div>

            <div class="col-lg-4">
              
              <div class="card mb-3">
                <div class="card-header bg-light">
                  <h6 class="mb-0"><i class="fa fa-line-chart text-success me-2"></i>Statistik Interaksi</h6>
                </div>
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center">
                      <div class="icon-item icon-item-sm bg-soft-primary shadow-none me-2"><i class="fa fa-eye text-primary"></i></div>
                      <h6 class="mb-0 text-700">Dilihat</h6>
                    </div>
                    <h4 class="mb-0 text-primary"><?php echo number_format($views, 0, ',', '.'); ?></h4>
                  </div>
                  
                  <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                      <div class="icon-item icon-item-sm bg-soft-warning shadow-none me-2"><i class="fa fa-comments text-warning"></i></div>
                      <h6 class="mb-0 text-700">Komentar</h6>
                    </div>
                    <h4 class="mb-0 text-warning"><?php echo number_format($total_komentar, 0, ',', '.'); ?></h4>
                  </div>
                </div>
              </div>

              <div class="card mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                  <h6 class="mb-0"><i class="fa fa-comments-o text-secondary me-2"></i>Komentar Terbaru</h6>
                </div>
                <div class="card-body" style="max-height: 450px; overflow-y: auto;">
                  
                  <?php if($total_komentar > 0): ?>
                    <?php foreach($komentar_list as $komen): 
                        $foto_profil = !empty($komen['foto']) ? $komen['foto'] : '../public/img/user/avatar.png';
                        $nama_user = !empty($komen['nama']) ? htmlspecialchars($komen['nama']) : 'User Tidak Dikenal';
                    ?>
                        <div class="comment-item">
                            <div class="d-flex">
                                <img src="<?php echo $foto_profil; ?>" referrerpolicy="no-referrer" class="comment-avatar me-2" alt="Avatar">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 text-dark fs--1"><?php echo $nama_user; ?></h6>
                                        <small class="text-muted" style="font-size: 11px;"><?php echo date('d M H:i', strtotime($komen['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-0 text-600 fs--1 mt-1"><?php echo nl2br(htmlspecialchars($komen['isi_komentar'])); ?></p>
                                    
                                    <div class="text-end mt-1">
                                        <button type="button" class="btn btn-link text-danger p-0 fs--2 btnHapusKomen" data-id="<?php echo $komen['id']; ?>" title="Hapus Komentar">
                                            <i class="fa fa-trash"></i> Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fa fa-commenting-o fs-3 mb-2 opacity-50"></i><br>
                        Belum ada komentar di artikel ini.
                    </div>
                  <?php endif; ?>

                </div>
              </div>

            </div>
          </div>
          <div class="modal fade" id="modalHapusKomentar" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-sm">
              <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="hapus_komentar">
                <input type="hidden" name="komentar_id" id="hapus_id_komentar">
                <div class="modal-content">
                  <div class="modal-header">
                    <h6 class="modal-title">Hapus Komentar?</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body text-center">
                    <p class="fs--1 mb-0">Komentar ini akan dihapus dari artikel.</p>
                  </div>
                  <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger">Ya, Hapus</button>
                  </div>
                </div>
              </form>
            </div>
          </div>
          <?php include_once("footer.php") ?>
        </div>
      </div>
    </main>
    <script src="../vendors/jquery/jquery-3.7.0.min.js"></script>
    <script>
      $(document).ready(function() {
        // Trigger Modal Hapus Komentar
        $('.btnHapusKomen').on('click', function() {
          var id = $(this).data('id');
          $('#hapus_id_komentar').val(id);
          $('#modalHapusKomentar').modal('show');
        });
      });
    </script>
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