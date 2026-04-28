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

$id_dokumen = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ==========================================
// PROSES PENANGANAN FORM UPDATE (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi Token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Akses ditolak! Token keamanan tidak valid.");
    }

    $judul            = $_POST['judul'];
    $sumber           = $_POST['sumber'];
    $tgl_penetapan    = $_POST['tanggal_penetapan'];
    $tgl_pengundangan = $_POST['tanggal_pengundangan'];
    $tgl_berlaku      = $_POST['tanggal_berlaku'];
    $deskripsi        = $_POST['deskripsi'];
    $dicabut          = $_POST['dicabut'];
    $mencabut         = $_POST['mencabut'];
    $kategori_post    = $_POST['kategori'];
    $rekomendasi      = isset($_POST['rekomendasi']) ? 1 : 0; 

    // Ambil nama file lama (jika user tidak upload PDF baru)
    $file_url = $_POST['file_lama']; 

    // Logika Upload File PDF Baru (Jika Ada)
    if (isset($_FILES['file_pdf']) && $_FILES['file_pdf']['error'] == 0) {
        $ext = pathinfo($_FILES['file_pdf']['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) == 'pdf') {
            $upload_dir = '../public/upload/documents/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $file_name = time() . '_' . md5($_FILES['file_pdf']['name']) . '.pdf';
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['file_pdf']['tmp_name'], $target_file)) {
                // Hapus file lama jika file baru berhasil diupload
                if(!empty($file_url) && file_exists($upload_dir . $file_url)) {
                    unlink($upload_dir . $file_url);
                }
                $file_url = $file_name; // Gunakan file baru
            }
        } else {
            die("Hanya file PDF yang diizinkan!");
        }
    }

    try {
        $stmt_update = $pdo->prepare("UPDATE `databases` SET 
            kategori = ?, judul = ?, sumber = ?, tanggal_penetapan = ?, 
            tanggal_pengundangan = ?, tanggal_berlaku = ?, deskripsi = ?, 
            dicabut = ?, mencabut = ?, file_pdf = ?, rekomendasi = ? 
            WHERE id = ?");
        
        $stmt_update->execute([
            $kategori_post, $judul, $sumber, $tgl_penetapan, $tgl_pengundangan, 
            $tgl_berlaku, $deskripsi, $dicabut, $mencabut, $file_url, $rekomendasi, $id_dokumen
        ]);

        // Redirect kembali ke halaman list kategori
        header("Location: database?kategori=" . $kategori_post . "&status=sukses_edit");
        exit;
    } catch (PDOException $e) {
        die("Error mengubah data: " . $e->getMessage());
    }
}

// ==========================================
// AMBIL DATA DOKUMEN SAAT INI UNTUK DIEDIT
// ==========================================
try {
    $stmt = $pdo->prepare("SELECT * FROM `databases` WHERE id = ?");
    $stmt->execute([$id_dokumen]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        die("Dokumen tidak ditemukan!");
    }

    // Variabel fallback jika kolom ini belum ada di tabel Anda agar tidak error
    $views = isset($doc['views']) ? $doc['views'] : 0;
    $likes = isset($doc['likes']) ? $doc['likes'] : 0;
    $bookmarks = isset($doc['bookmarks']) ? $doc['bookmarks'] : 0;

} catch (PDOException $e) {
    die("Error mengambil data: " . $e->getMessage());
}

  $nama=$_SESSION["nama"];
  $_SESSION["menu"]=$doc['kategori'];
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Dokumen | LexPina</title>
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
            <span class="fa fa-edit me-2 fs-3 text-primary"></span>
            <div>
              <h4 class="mb-0">Edit Dokumen</h4>
              <span class="text-muted">Perbarui informasi dan file dokumen LexPina</span>
            </div>
          </div>

          <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="file_lama" value="<?php echo htmlspecialchars($doc['file_pdf']); ?>">

            <div class="row g-3">
              <div class="col-lg-8">
                <div class="card mb-3">
                  <div class="card-header bg-light">
                    <h6 class="mb-0">Detail Dokumen</h6>
                  </div>
                  <div class="card-body">
                    <div class="row g-3">
                      <div class="col-md-12">
                        <label class="form-label">Judul Dokumen</label>
                        <input type="text" class="form-control" name="judul" value="<?php echo htmlspecialchars($doc['judul']); ?>" required>
                      </div>
                      
                      <div class="col-md-6">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="kategori" required>
                          <option value="peraturan" <?php if($doc['kategori']=='peraturan') echo 'selected'; ?>>Peraturan</option>
                          <option value="peraturan-konsolidasi" <?php if($doc['kategori']=='peraturan-konsolidasi') echo 'selected'; ?>>Peraturan Konsolidasi</option>
                          <option value="karya-ilmiah" <?php if($doc['kategori']=='karya-ilmiah') echo 'selected'; ?>>Karya Ilmiah</option>
                          <option value="jurnal" <?php if($doc['kategori']=='jurnal') echo 'selected'; ?>>Jurnal</option>
                          <option value="putusan" <?php if($doc['kategori']=='putusan') echo 'selected'; ?>>Putusan</option>
                          <option value="artikel" <?php if($doc['kategori']=='artikel') echo 'selected'; ?>>Artikel</option>
                          <option value="template-perjanjian" <?php if($doc['kategori']=='template-perjanjian') echo 'selected'; ?>>Template Perjanjian</option>
                        </select>
                      </div>

                      <div class="col-md-6">
                        <label class="form-label">Sumber</label>
                        <input type="text" class="form-control" name="sumber" value="<?php echo htmlspecialchars($doc['sumber']); ?>" required>
                      </div>

                      <div class="col-md-4">
                        <label class="form-label">Tanggal Penetapan</label>
                        <input type="date" class="form-control" name="tanggal_penetapan" value="<?php echo $doc['tanggal_penetapan']; ?>" required>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Tanggal Pengundangan</label>
                        <input type="date" class="form-control" name="tanggal_pengundangan" value="<?php echo $doc['tanggal_pengundangan']; ?>" required>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Tanggal Berlaku</label>
                        <input type="date" class="form-control" name="tanggal_berlaku" value="<?php echo $doc['tanggal_berlaku']; ?>" required>
                      </div>

                      <div class="col-md-12">
                        <label class="form-label">Deskripsi / Abstrak</label>
                        <textarea class="form-control" name="deskripsi" rows="4"><?php echo htmlspecialchars($doc['deskripsi'] ?? ''); ?></textarea>
                      </div>

                      <div class="col-md-6">
                        <label class="form-label">Status: Mencabut</label>
                        <textarea class="form-control" name="mencabut" rows="3"><?php echo htmlspecialchars($doc['mencabut'] ?? ''); ?></textarea>
                      </div>

                      <div class="col-md-6">
                        <label class="form-label">Status: Dicabut</label>
                        <textarea class="form-control" name="dicabut" rows="3"><?php echo htmlspecialchars($doc['dicabut'] ?? ''); ?></textarea>
                      </div>

                      <div class="col-md-12 mt-4">
                        <div class="p-3 bg-light rounded border">
                          <label class="form-label text-primary"><i class="fa fa-file-pdf-o"></i> Ganti File PDF</label>
                          <input type="file" class="form-control" name="file_pdf" accept=".pdf">
                          <small class="text-muted mt-1 d-block">
                            * File saat ini: <a href="../public/upload/documents/<?php echo $doc['file_pdf']; ?>" target="_blank" class="fw-bold"><?php echo $doc['file_pdf']; ?></a><br>
                            * Biarkan kosong jika Anda <b>tidak ingin</b> mengganti dokumen PDF.
                          </small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-lg-4">
                
                <div class="card mb-3">
                  <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fa fa-bar-chart text-info me-2"></i>Statistik Keterlibatan</h6>
                  </div>
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <div class="d-flex align-items-center">
                        <div class="icon-item icon-item-sm bg-soft-primary shadow-none me-2"><i class="fa fa-eye text-primary"></i></div>
                        <h6 class="mb-0 text-700">Dilihat</h6>
                      </div>
                      <h4 class="mb-0 text-primary"><?php echo number_format($views, 0, ',', '.'); ?></h4>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <div class="d-flex align-items-center">
                        <div class="icon-item icon-item-sm bg-soft-danger shadow-none me-2"><i class="fa fa-heart text-danger"></i></div>
                        <h6 class="mb-0 text-700">Disukai (Likes)</h6>
                      </div>
                      <h4 class="mb-0 text-danger"><?php echo number_format($likes, 0, ',', '.'); ?></h4>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                      <div class="d-flex align-items-center">
                        <div class="icon-item icon-item-sm bg-soft-warning shadow-none me-2"><i class="fa fa-bookmark text-warning"></i></div>
                        <h6 class="mb-0 text-700">Disimpan</h6>
                      </div>
                      <h4 class="mb-0 text-warning"><?php echo number_format($bookmarks, 0, ',', '.'); ?></h4>
                    </div>
                  </div>
                </div>

                <div class="card mb-3">
                  <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fa fa-cogs text-secondary me-2"></i>Pengaturan</h6>
                  </div>
                  <div class="card-body">
                    <div class="form-check form-switch mb-3">
                      <input class="form-check-input" type="checkbox" id="rekomendasiCheck" name="rekomendasi" value="1" <?php echo ($doc['rekomendasi'] == 1) ? 'checked' : ''; ?>>
                      <label class="form-check-label fw-bold text-dark" for="rekomendasiCheck">
                         Jadikan Rekomendasi
                      </label>
                    </div>

                    <hr class="my-4">
                    
                    <div class="alert alert-info py-2 fs--1" role="alert">
                      <i class="fa fa-info-circle me-1"></i> <strong>Petunjuk:</strong> Pastikan tanggal diisi dengan benar. Form <i>Mencabut</i> dan <i>Dicabut</i> bisa dibiarkan kosong jika dokumen berdiri sendiri.
                    </div>

                    <div class="d-grid gap-2">
                      <button class="btn btn-primary" type="submit">
                        <i class="fa fa-save me-1"></i> Simpan Perubahan
                      </button>
                      <a href="database?kategori=<?php echo $doc['kategori']; ?>" class="btn btn-outline-secondary">
                        <i class="fa fa-times me-1"></i> Batal / Kembali
                      </a>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </form>
          <?php include_once("footer.php") ?>
        </div>
      </div>
    </main>
    <script src="../vendors/jquery/jquery-3.7.0.min.js"></script>
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