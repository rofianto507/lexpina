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
  $_SESSION["menu"]="kategori-berita";
 
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
        header("Location: kategori-berita?msg=csrf_failed");
        exit;
    }

    $action = $_POST['action'] ?? '';

    try {
        // --- 1. PROSES TAMBAH ---
        if ($action == 'tambah' && !empty($_POST['nama_kategori'])) {
            $nama_kat = $_POST['nama_kategori'];
            $slug_kat = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nama_kat)));

            $stmt = $pdo->prepare("INSERT INTO kategoris (nama_kategori, slug_kategori, status) VALUES (?, ?, 1)");
            $stmt->execute([$nama_kat, $slug_kat]);
            
            header("Location: kategori-berita?msg=sukses_tambah");
            exit;
        }

        // --- 2. PROSES EDIT ---
        if ($action == 'edit' && !empty($_POST['edit_id']) && !empty($_POST['nama_kategori'])) {
            $edit_id = (int)$_POST['edit_id'];
            $nama_kat = $_POST['nama_kategori'];
            $slug_kat = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nama_kat)));

            $stmt = $pdo->prepare("UPDATE kategoris SET nama_kategori = ?, slug_kategori = ? WHERE id = ?");
            $stmt->execute([$nama_kat, $slug_kat, $edit_id]);
            
            header("Location: kategori-berita?msg=sukses_edit");
            exit;
        }

        // --- 3. PROSES HAPUS (SOFT DELETE) ---
        if ($action == 'hapus' && !empty($_POST['hapus_id'])) {
            $hapus_id = (int)$_POST['hapus_id'];
            
            $stmt = $pdo->prepare("UPDATE kategoris SET status = 0 WHERE id = ?");
            $stmt->execute([$hapus_id]);
            
            header("Location: kategori-berita?msg=sukses_hapus");
            exit;
        }

    } catch (PDOException $e) {
        die("Error memproses data: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kategori Berita | LexPina</title>

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

          <?php if(isset($_GET['msg'])): ?>
            <?php if($_GET['msg'] == 'sukses_tambah'): ?>
              <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">Data kategori berhasil ditambahkan.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'sukses_edit'): ?>
              <div class="alert alert-info alert-dismissible fade show mt-3" role="alert">Data kategori berhasil diperbarui.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'sukses_hapus'): ?>
              <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">Data kategori berhasil dihapus.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'csrf_failed'): ?>
              <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert"><strong>Akses ditolak!</strong> Token keamanan tidak valid.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
          <?php endif; ?>

          <div class="card mb-3 mt-3">
            <div class="card-header">
              <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                  <h5 class="fs-0 mb-0"><span class="fa fa-tags me-2 fs-0 text-primary"></span>Master Data: Kategori Berita</h5>
                </div>
                <div class="col-auto ms-auto">
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    Tambah Data
                  </button>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="kategoriTable" class="display table table-striped table-bordered table-sm" style="width:100%">
                  <thead class="bg-primary text-white">
                    <tr>
                      <th width="5%">No</th>
                      <th>Nama Kategori</th>
                      <th>Slug (URL)</th>
                      <th width="20%">Tanggal</th>
                      <th width="15%" class="text-center">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php
                    $query = "SELECT * FROM kategoris WHERE status = 1 ORDER BY id DESC";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute();
                    $no = 1;
                    
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      $tgl = date('d/m/Y H:i', strtotime($row['created_at']));
                      echo "<tr>
                        <td class='text-center'>".$no++."</td>
                        <td class='fw-bold'>".htmlspecialchars($row['nama_kategori'])."</td>
                        <td><span class='badge badge-soft-secondary text-lowercase'>".$row['slug_kategori']."</span></td>
                        <td>".$tgl."</td>
                        <td class='text-center'>
                          <button class='btn btn-sm btn-info btnEdit' data-id='".$row['id']."' data-nama='".htmlspecialchars($row['nama_kategori'], ENT_QUOTES)."'>Edit</button>
                          <button class='btn btn-sm btn-danger btnHapus' data-id='".$row['id']."' data-nama='".htmlspecialchars($row['nama_kategori'], ENT_QUOTES)."'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <label class="form-label">Nama Kategori</label>
                    <input type="text" class="form-control" name="nama_kategori" required placeholder="Contoh: Hukum Pidana">
                    <small class="text-muted mt-1 d-block">*Slug URL akan dibuat secara otomatis.</small>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="edit_id" id="form_edit_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Edit Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <label class="form-label">Nama Kategori</label>
                    <input type="text" class="form-control" name="nama_kategori" id="form_edit_nama" required>
                    <small class="text-muted mt-1 d-block">*Mengubah nama akan otomatis memperbarui slug URL.</small>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info text-white">Update</button>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <div class="modal fade" id="modalHapus" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="hapus">
                <input type="hidden" name="hapus_id" id="form_hapus_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h6 class="modal-title">Konfirmasi Hapus</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body text-center">
                    <p class="mb-0">Yakin ingin menghapus kategori <br><strong id="label_hapus_nama" class="text-danger"></strong>?</p>
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
    <!-- Scripts -->
<script src="../vendors/jquery/jquery-3.7.0.min.js"></script>
    <script src="../vendors/datatables/datatables.min.js"></script>
     <script>
    $(document).ready(function() {
      // Init DataTables
      $('#kategoriTable').DataTable({
        "autoWidth": false,
        "order": [[ 0, "asc" ]] 
      });

      // Trigger Edit Modal
      $(document).on('click', '.btnEdit', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        
        $('#form_edit_id').val(id);
        $('#form_edit_nama').val(nama);
        $('#modalEdit').modal('show');
      });

      // Trigger Hapus Modal
      $(document).on('click', '.btnHapus', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        
        $('#form_hapus_id').val(id);
        $('#label_hapus_nama').text(nama);
        $('#modalHapus').modal('show');
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
 