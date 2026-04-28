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
  $_SESSION["menu"]="berita";
 
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
        header("Location: berita?status=csrf_failed");
        exit;
    }

    // 1. PROSES HAPUS DATA
    if (isset($_POST['hapus_id']) && !empty($_POST['hapus_id'])) {
        $hapus_id = (int)$_POST['hapus_id'];
        try {
            $stmt_hapus = $pdo->prepare("update `beritas` set status = 0 WHERE id = ?");
            $stmt_hapus->execute([$hapus_id]);

            header("Location: berita?status=hapus_sukses");
            exit;
        } catch (PDOException $e) {
            die("Error menghapus data: " . $e->getMessage());
        }
    }

    // 2. PROSES TAMBAH DATA
    if (isset($_POST['judul'])) {
        $judul = $_POST['judul'];
        $konten = $_POST['konten'];
        $kategori_id = $_POST['kategori_id'];
        
        $file_url = ''; // Variabel untuk menyimpan path Image yang diupload
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul)));
        // Logika Upload File image (jika ada)
        if (isset($_FILES['file_image']) && $_FILES['file_image']['error'] == 0) {
            $ext = pathinfo($_FILES['file_image']['name'], PATHINFO_EXTENSION);
            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])) {
                $upload_dir = '../public/upload/news/';
                // Pastikan folder ada
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Buat nama file unik agar tidak tertimpa
                $file_name = time() . '_' . md5($_FILES['file_image']['name']) . '.' . $ext;
                $target_file = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['file_image']['tmp_name'], $target_file)) {
                    $file_url = $file_name;
                }
            } else {
                die("Hanya file gambar yang diizinkan!");
            }
        }

        try {
            $stmt_tambah = $pdo->prepare("INSERT INTO `beritas` 
                (kategori_id, judul, slug, konten, gambar, status) 
                VALUES (?, ?, ?, ?, ?, 1)");

            $stmt_tambah->execute([
                $kategori_id, $judul, $slug, $konten, $file_url
            ]);

            header("Location: berita?status=sukses");
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
    <title>Berita | LexPina</title>

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
                  <h5 class="fs-0 mb-0"><span class="fa fa-newspaper-o me-2 fs-0"></span>Data Berita</h5>
                </div>
                <div class="col-auto ms-auto">
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahBerita">
                    Tambah Data
                  </button>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="beritaTable" class="display table table-striped table-bordered table-sm" >
                  <thead class="bg-primary text-white">
                    <tr>
                      <th>ID</th>
                      <th>Tanggal</th>
                      <th>Judul</th>
                      <th>Kategori</th>
                    
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tfoot>
                    <tr>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>              
                      <th></th>
                    </tr>
                  </tfoot>
                  <tbody>
                  <?php

                    $query = "SELECT beritas.*, kategoris.nama_kategori as kategori_nama FROM `beritas` JOIN `kategoris` ON beritas.kategori_id = kategoris.id WHERE beritas.status=1 ORDER BY beritas.id DESC";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute();  
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      echo "<tr>
                        <td>".$row['id']."</td>
                        <td>".htmlspecialchars(date('d/m/Y H:i', strtotime($row['created_at'])))."</td>
                        <td>".htmlspecialchars($row['judul'])."</td>
                        <td>".htmlspecialchars($row['kategori_nama'])."</td>                      
                       
                        <td>
                          <a href='berita-edit?id=".$row['id']."' class='btn btn-sm btn-info'>Edit</a>
                          <button class='btn btn-sm btn-danger btnHapusBerita' data-id='".$row['id']."' data-judul='".htmlspecialchars($row['judul'], ENT_QUOTES)."'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <!-- Modal Konfirmasi Hapus Berita -->
            <div class="modal fade" id="modalHapusBerita" tabindex="-1" aria-labelledby="modalHapusBeritaLabel" aria-hidden="true">
              <div class="modal-dialog">
                <form method="POST" id="formHapusBerita">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                  <input type="hidden" name="hapus_id" id="hapus_id_berita">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalHapusBeritaLabel">Konfirmasi Hapus</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <p>Yakin ingin menghapus data berita dengan judul: <b id="hapus_judul"></b>?</p>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                      <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>

          <!-- Modal Tambah Data Berita -->
          <div class="modal fade" id="modalTambahBerita" tabindex="-1" aria-labelledby="modalTambahBeritaLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <form action="" method="POST" id="formTambahBerita" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahBeritaLabel">Tambah Berita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label class="form-label">Judul</label>
                      <input type="text" class="form-control form-control-sm" name="judul" required>
                    </div>
                     <div class="mb-3">
                      <label class="form-label">Konten</label>
                      <textarea class="form-control form-control-sm" name="konten" rows="5"></textarea>
                    </div>               
                    <div class="mb-3">
                      <label class="form-label">Kategori</label>
                      <select class="form-select form-select-sm" name="kategori_id" required>
                        <option value="">Pilih Kategori</option>
                        <?php
                          $stmt_kategori = $pdo->prepare("SELECT id, nama_kategori FROM kategoris ORDER BY nama_kategori ASC");
                          $stmt_kategori->execute();
                          while($kategori = $stmt_kategori->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='".$kategori['id']."'>".htmlspecialchars($kategori['nama_kategori'])."</option>";
                          }
                        ?>
                      </select>
                    </div>
                      <div class="mb-3">
                        <label class="form-label">File Gambar</label>
                        <input type="file" class="form-control form-control-sm" name="file_image" required>
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
      $('#beritaTable').DataTable({
        "autoWidth": false,
        "order": [[ 0, "desc" ]], 
         initComplete: function () {
            this.api().columns([3]).every( function () {
              var column = this;
              var select = $('<select class="form-control form-control-sm"><option value="">- Semua -</option></select>')
                .appendTo( $(column.footer()).empty() )
                .on( 'change', function () {
                  var val = $.fn.dataTable.util.escapeRegex($(this).val());
                  column
                    .search( val ? '^'+val+'$' : '', true, false )
                    .draw();
                } );
              // Populate options: ambil unik dan urut
              column.data().unique().sort().each( function ( d, j ) {
                if(d) select.append( '<option value="'+d+'">'+d+'</option>' );
              });
            });
          }
      });
    });
      $(document).on('click', '.btnHapusBerita', function() {
      var id = $(this).data('id');
      var judul = $(this).data('judul');
      $('#hapus_id_berita').val(id);
      $('#hapus_judul').text(judul);
      $('#modalHapusBerita').modal('show');
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
 