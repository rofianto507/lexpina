<?php
session_start();
include("../config/configuration.php");
if($_SESSION["nama"]!="" && $_SESSION["id"]!=""){
  $_SESSION["menu"]="kategori-konflik";
  $menu=$_SESSION["menu"];
  $nama=$_SESSION["nama"];
  $id=$_SESSION["id"];
  $username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
  $foto=$_SESSION["foto"];
  $last_login=$_SESSION["last_login"];
  if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['namaKategori'])) {
      $namaKategori = trim($_POST['namaKategori']);
      if($namaKategori != "") {
          $stmtInsert = $pdo->prepare("INSERT INTO konflik_kategoris (nama, status) VALUES (:nama, 1)");
          $stmtInsert->bindParam(':nama', $namaKategori);
          $stmtInsert->execute();
          // Redirect agar refresh page dan menghilangkan POST (juga agar tabel otomatis update)
          header("Location: kategori-konflik?status=sukses");
          exit;
      }
  }
  // Handle proses Update
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_id'], $_POST['edit_nama'])) {
    $edit_id = intval($_POST['edit_id']);
    $edit_nama = trim($_POST['edit_nama']);
    if($edit_id && $edit_nama != "") {
      $stmtUpdate = $pdo->prepare("UPDATE konflik_kategoris SET nama=:nama, updated_at=NOW() WHERE id=:id");
      $stmtUpdate->execute([':nama'=>$edit_nama, ':id'=>$edit_id]);
      header("Location: kategori-konflik?status=edit_sukses");
      exit;
    }
  }
  // Handle proses Hapus
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['hapus_id'])) {
    $hapus_id = intval($_POST['hapus_id']);
    if($hapus_id) {
      $stmtDel = $pdo->prepare("DELETE FROM konflik_kategoris WHERE id=:id");
      $stmtDel->execute([':id'=>$hapus_id]);
      header("Location: kategori-konflik?status=hapus_sukses");
      exit;
    }
  }
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kategori Konflik | Peta Digital</title>
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/img/favicons/favicon-16x16.png">
    <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicons/favicon.ico">
    <link rel="manifest" href="../assets/img/favicons/manifest.json">
    <meta name="msapplication-TileImage" content="../assets/img/favicons/mstile-150x150.png">
    <meta name="theme-color" content="#ffffff">
    <script src="../assets/js/config.js"></script>
    <script src="../vendors/overlayscrollbars/OverlayScrollbars.min.js"></script>

    <!-- DataTables CSS & jQuery -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css"/>
    <link href="../vendors/prism/prism-okaidia.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">
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
          <?php if(isset($_GET['status']) && $_GET['status']=='sukses'): ?>
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                  Data kategori berhasil ditambahkan.
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                <?php if(isset($_GET['status']) && $_GET['status']=='edit_sukses'): ?>
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                  Data kategori berhasil diubah.
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php elseif(isset($_GET['status']) && $_GET['status']=='hapus_sukses'): ?>
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                  Data kategori berhasil dihapus.
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
          <div class="card mb-3">
            <div class="card-header">
              <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                  <h5 class="fs-0 mb-0"><span class="fas fa-rocket me-2 fs-0"></span> Kategori Konflik</h5>
                </div>
                <div class="col-auto ms-auto">
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
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
                      <th>No</th>
                      <th>Nama Kategori</th>
                      <th>Aksi</th>
                      
                    </tr>
                  </thead>
                  <tbody>
                  <?php
                    $no=1;
                    $stmt = $pdo->query("SELECT * FROM konflik_kategoris WHERE status=1 ORDER BY id ASC");
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      echo "<tr>
                        <td>".$no++."</td>
                        <td>".$row['nama']."</td>
                        
                     
                        <td>
                          <button class='btn btn-sm btn-info btnEditKategori' data-id='{$row['id']}' data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)."'>Edit</button>
                          <button class='btn btn-sm btn-danger btnHapusKategori' data-id='{$row['id']}' data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)."'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <!-- Modal Tambah Data Kategori Konflik -->
          <div class="modal fade" id="modalTambahKategori" tabindex="-1" aria-labelledby="modalTambahKategoriLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formTambahKategori">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahKategoriLabel">Tambah Kategori Konflik</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label for="namaKategori" class="form-label">Nama Kategori</label>
                      <input type="text" class="form-control" id="namaKategori" name="namaKategori" required>
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
          <!-- Modal Edit Kategori Konflik -->
          <div class="modal fade" id="modalEditKategori" tabindex="-1" aria-labelledby="modalEditKategoriLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formEditKategori">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalEditKategoriLabel">Edit Kategori Konflik</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label for="edit_nama" class="form-label">Nama Kategori Konflik</label>
                      <input type="text" class="form-control" id="edit_nama" name="edit_nama" required>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <!-- Modal Konfirmasi Hapus -->
          <div class="modal fade" id="modalHapusKategori" tabindex="-1" aria-labelledby="modalHapusKategoriLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formHapusKategori">
                <input type="hidden" name="hapus_id" id="hapus_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalHapusKategoriLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <p>Yakin ingin menghapus kategori <b id="hapus_nama"></b>?</p>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                  </div>
                </div>
              </form>
            </div>
          </div>
          <?php include_once("footer.php") ?>
        </div>
      </div>
    </main>
    <!-- JavaScripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
      $(document).ready(function() {
        $('#kategoriTable').DataTable({
          "autoWidth": false,

        });
      });
      // Handle tombol Edit (reload data di modal)
      $(document).on('click', '.btnEditKategori', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#edit_id').val(id);
        $('#edit_nama').val(nama);
        $('#modalEditKategori').modal('show');
      });

      // Handle tombol Hapus (isi nama di modal konfirmasi)
      $(document).on('click', '.btnHapusKategori', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#hapus_id').val(id);
        $('#hapus_nama').text(nama);
        $('#modalHapusKategori').modal('show');
      });
    </script>
    <script src="../vendors/popper/popper.min.js"></script>
    <script src="../vendors/bootstrap/bootstrap.min.js"></script>
    <script src="../vendors/anchorjs/anchor.min.js"></script>
    <script src="../vendors/is/is.min.js"></script>
    <script src="../vendors/prism/prism.js"></script>
    <script src="../vendors/fontawesome/all.min.js"></script>
    <script src="../vendors/lodash/lodash.min.js"></script>
    <script src="../vendors/list.js/list.min.js"></script>
    <script src="../assets/js/theme.js"></script>
  </body>
</html>
<?php
}else{
  header("Location: ../index");
}
?>