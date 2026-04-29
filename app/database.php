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
    $kategori=$_GET["kategori"] ?? "";
  $_SESSION["menu"]=$kategori;
  $kategori_nama= ucwords(str_replace('-', ' ', $kategori));
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
    // Validasi Token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: database?kategori=" . $kategori . "&status=csrf_failed");
        exit;
    }

    // 1. PROSES HAPUS DATA
    if (isset($_POST['hapus_id']) && !empty($_POST['hapus_id'])) {
        $hapus_id = (int)$_POST['hapus_id'];
        try {
            $stmt_hapus = $pdo->prepare("update `databases` set status = 0 WHERE id = ?");
            $stmt_hapus->execute([$hapus_id]);
            
            header("Location: database?kategori=" . $kategori . "&status=hapus_sukses");
            exit;
        } catch (PDOException $e) {
            die("Error menghapus data: " . $e->getMessage());
        }
    }

    // 2. PROSES TAMBAH DATA
    if (isset($_POST['judul'])) {
        $judul = $_POST['judul'];
        $sumber = $_POST['sumber'];
        $tgl_penetapan = $_POST['tanggal_penetapan'];
        $tgl_pengundangan = $_POST['tanggal_pengundangan'];
        $tgl_berlaku = $_POST['tanggal_berlaku'];
        $deskripsi = $_POST['deskripsi'];
        $dicabut = $_POST['dicabut'];
        $dicabut_sebagian = $_POST['dicabut_sebagian'] ?? '';
        $mencabut = $_POST['mencabut'];
        $mencabut_sebagian = $_POST['mencabut_sebagian'] ?? '';
        $diubah = $_POST['diubah'] ?? '';
        $diubah_sebagian = $_POST['diubah_sebagian'] ?? '';
        $mengubah = $_POST['mengubah'] ?? '';
        $mengubah_sebagian = $_POST['mengubah_sebagian'] ?? '';
        $uji_materi = $_POST['uji_materi'] ?? '';
        $kategori_post = $_POST['kategori'];
        $rekomendasi = isset($_POST['rekomendasi']) ? 1 : 0;
        $file_url = ''; // Variabel untuk menyimpan path PDF

        // Logika Upload File PDF
        if (isset($_FILES['file_pdf']) && $_FILES['file_pdf']['error'] == 0) {
            $ext = pathinfo($_FILES['file_pdf']['name'], PATHINFO_EXTENSION);
            if (strtolower($ext) == 'pdf') {
                $upload_dir = '../public/upload/documents/';
                // Pastikan folder ada
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Buat nama file unik agar tidak tertimpa
                $file_name = time() . '_' . md5($_FILES['file_pdf']['name']) . '.pdf';
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['file_pdf']['tmp_name'], $target_file)) {
                    $file_url = $file_name;
                }
            } else {
                die("Hanya file PDF yang diizinkan!");
            }
        }

        try {
            $stmt_tambah = $pdo->prepare("INSERT INTO `databases` 
                (kategori, judul, sumber, tanggal_penetapan, tanggal_pengundangan, tanggal_berlaku, deskripsi, dicabut, dicabut_sebagian, mencabut, mencabut_sebagian, diubah, diubah_sebagian, mengubah, mengubah_sebagian, uji_materi, file_pdf, rekomendasi, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

            $stmt_tambah->execute([
                $kategori_post, $judul, $sumber, $tgl_penetapan, $tgl_pengundangan, $tgl_berlaku, $deskripsi, $dicabut, $dicabut_sebagian, $mencabut, $mencabut_sebagian, $diubah, $diubah_sebagian, $mengubah, $mengubah_sebagian, $uji_materi, $file_url, $rekomendasi
            ]);

            header("Location: database?kategori=" . $kategori . "&status=sukses");
            exit;
        } catch (PDOException $e) {
            die("Error menambah data: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database - <?php echo htmlspecialchars($kategori_nama); ?></title>

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
          <?php if(isset($_GET['status']) && $_GET['status']=='sukses'): ?>
          <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            Data berhasil ditambahkan.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          
          <?php if(isset($_GET['status']) && $_GET['status']=='hapus_sukses'): ?>
          <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            Data berhasil dihapus.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
            <?php elseif(isset($_GET['status']) && $_GET['status']=='csrf_failed'): ?>
                <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                  <strong>Akses ditolak!</strong> Token keamanan tidak valid. Silakan muat ulang halaman dan coba lagi.
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
          <div class="card mb-3">
            <div class="card-header">
              <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                  <h5 class="fs-0 mb-0"><span class="fa fa-folder me-2 fs-0"></span>Data <?php echo htmlspecialchars($kategori_nama); ?></h5>
                </div>
                <div class="col-auto ms-auto">
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahDokumen">
                    Tambah Data
                  </button>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="dokumenTable" class="display table table-striped table-bordered table-sm" >
                  <thead class="bg-primary text-white">
                    <tr>
                      <th>ID</th>
                      <th>Tanggal</th>
                      <th>Judul</th>
                      <th>Sumber</th>
                   
                      <th>Tanggal Penetapan</th>
                      <th>Tanggal Pengundangan</th>
                      <th>Tanggal Berlaku</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php
                  
                    $query = "SELECT* from `databases` WHERE status=1 and kategori=? ORDER BY id DESC";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$kategori]);
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      echo "<tr>
                        <td>".$row['id']."</td>
                        <td>".htmlspecialchars(date('d/m/Y H:i', strtotime($row['created_at'])))."</td>
                        <td>".htmlspecialchars($row['judul'])."</td>
                        <td>".htmlspecialchars($row['sumber'])."</td>                      
                        <td>".htmlspecialchars(date('d/m/Y', strtotime($row['tanggal_penetapan'])))."</td>
                        <td>".htmlspecialchars(date('d/m/Y', strtotime($row['tanggal_pengundangan'])))."</td>
                        <td>".htmlspecialchars(date('d/m/Y', strtotime($row['tanggal_berlaku'])))."</td>
                        <td>
                          <a href='database-edit?id=".$row['id']."' class='btn btn-sm btn-info'>Edit</a>
                          <button class='btn btn-sm btn-danger btnHapusDokumen' data-id='".$row['id']."' data-judul='".htmlspecialchars($row['judul'], ENT_QUOTES)."'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <!-- Modal Konfirmasi Hapus Dokumen -->
            <div class="modal fade" id="modalHapusDokumen" tabindex="-1" aria-labelledby="modalHapusDokumenLabel" aria-hidden="true">
              <div class="modal-dialog">
                <form method="POST" id="formHapusDokumen">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                  <input type="hidden" name="hapus_id" id="hapus_id_dokumen">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalHapusDokumenLabel">Konfirmasi Hapus</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <p>Yakin ingin menghapus data dokumen dengan judul: <b id="hapus_judul"></b>?</p>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                      <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>

          <!-- Modal Tambah Data Dokumen -->
          <div class="modal fade" id="modalTambahDokumen" tabindex="-1" aria-labelledby="modalTambahDokumenLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <form action="" method="POST" id="formTambahDokumen" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="kategori" value="<?php echo htmlspecialchars($kategori); ?>">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahDokumenLabel">Tambah Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label class="form-label">Judul</label>
                      <input type="text" class="form-control form-control-sm" name="judul" required>
                    </div>
                                   
                    <div class="mb-3">
                      <label class="form-label">Tanggal Penetapan</label>
                      <input type="date" class="form-control form-control-sm" name="tanggal_penetapan" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Tanggal Pengundangan</label>
                      <input type="date" class="form-control form-control-sm" name="tanggal_pengundangan" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Tanggal Berlaku</label>
                      <input type="date" class="form-control form-control-sm" name="tanggal_berlaku" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Sumber</label>
                      <input type="text" class="form-control form-control-sm" name="sumber" required>
                    </div>
                      <div class="mb-3">
                        <label class="form-label">File PDF (URL)</label>
                        <input type="file" class="form-control form-control-sm" name="file_pdf" required>
                      </div>
                    <div class="mb-3">
                      <label class="form-label">Deskripsi</label>
                      <textarea class="form-control form-control-sm" name="deskripsi" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Dicabut</label>
                      <textarea class="form-control form-control-sm" name="dicabut" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Dicabut sebagian</label>
                      <textarea class="form-control form-control-sm" name="dicabut_sebagian" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Mencabut</label>
                      <textarea class="form-control form-control-sm" name="mencabut" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Mencabut sebagian</label>
                      <textarea class="form-control form-control-sm" name="mencabut_sebagian" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Diubah</label>
                      <textarea class="form-control form-control-sm" name="diubah" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Diubah sebagian</label>
                      <textarea class="form-control form-control-sm" name="diubah_sebagian" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Mengubah</label>
                      <textarea class="form-control form-control-sm" name="mengubah" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Mengubah sebagian</label>
                      <textarea class="form-control form-control-sm" name="mengubah_sebagian" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Uji Materi</label>
                      <textarea class="form-control form-control-sm" name="uji_materi" rows="5"></textarea>
                    </div>
                    <div class="mb-3 mt-4">
                      <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="rekomendasiCheck" name="rekomendasi" value="1">
                        <label class="form-check-label fw-bold text-primary" for="rekomendasiCheck" style="cursor:pointer;">
                          <i class="fa fa-star text-warning"></i> Jadikan Dokumen Rekomendasi
                        </label>
                      </div>
                      <small class="text-muted">Jika dicentang, dokumen ini akan tampil di bagian atas (slider) halaman beranda pengguna.</small>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                  </div>
                </div>
              </form>
            </div>
          </div>
          <?php include_once("footer.php") ?>
        </div>
      </div>
    </main>
    <!-- Scripts -->
<script src="../vendors/jquery/jquery-3.7.0.min.js"></script>
    <script src="../vendors/datatables/datatables.min.js"></script>
     <script>
    $(document).ready(function() {
      $('#dokumenTable').DataTable({
        "autoWidth": false,
        "order": [[ 0, "desc" ]], 
        
      });
    });
      $(document).on('click', '.btnHapusDokumen', function() {
      var id = $(this).data('id');
      var judul = $(this).data('judul');
      $('#hapus_id_dokumen').val(id);
      $('#hapus_judul').text(judul);
      $('#modalHapusDokumen').modal('show');
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
 