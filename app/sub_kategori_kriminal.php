<?php
session_start();
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:;");
include("../config/configuration.php");
if(!isset($_SESSION["id"]) || !isset($_SESSION["nama"])) {
  header("Location: ../index");
  exit;
}
 if(empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  $_SESSION["menu"]="sub-kategori-kriminal";
  $menu=$_SESSION["menu"];
  $nama=$_SESSION["nama"];
  $id=$_SESSION["id"];
  $username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
  $foto=$_SESSION["foto"];
  $last_login=$_SESSION["last_login"];
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
      ) {
        // Token tidak valid, tolak permintaan
        header("Location: sub-kategori-kriminal?status=csrf_failed");
        exit;
    }
  }
  if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['namaSubKategori'])) {
      $namaSubKategori = trim($_POST['namaSubKategori']);
      $kategori_id = intval($_POST['kategori_id']);
      if($namaSubKategori != "") {
          $stmtInsert = $pdo->prepare("INSERT INTO kriminal_sub_kategoris (nama, kategori_id, status) VALUES (:nama, :kategori_id, 1)");
          $stmtInsert->bindParam(':nama', $namaSubKategori);
          $stmtInsert->bindParam(':kategori_id', $kategori_id);
          $stmtInsert->execute();
          // Redirect agar refresh page dan menghilangkan POST (juga agar tabel otomatis update)
          header("Location: sub-kategori-kriminal?status=sukses");
          exit;
      }
  }
  // Handle proses Update
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_id'], $_POST['edit_nama'])) {
    $edit_id = intval($_POST['edit_id']);
    $edit_nama = trim($_POST['edit_nama']);
    $edit_kategori_id = intval($_POST['edit_kategori_id']);
    if($edit_id && $edit_nama != "") {
      $stmtUpdate = $pdo->prepare("UPDATE kriminal_sub_kategoris SET nama=:nama, kategori_id=:kategori_id, updated_at=NOW() WHERE id=:id");
      $stmtUpdate->execute([':nama'=>$edit_nama, ':kategori_id'=>$edit_kategori_id, ':id'=>$edit_id]);
      header("Location: sub-kategori-kriminal?status=edit_sukses");
      exit;
    }
  }
  // Handle proses Hapus
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['hapus_id'])) {
    $hapus_id = intval($_POST['hapus_id']);
    if($hapus_id) {
      $stmtDel = $pdo->prepare("UPDATE kriminal_sub_kategoris SET status=0 WHERE id=:id");
      $stmtDel->execute([':id'=>$hapus_id]);
      header("Location: sub-kategori-kriminal?status=hapus_sukses");
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
    <title>Sub Kategori Kriminal | Peta Digital</title>
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
     <link rel="stylesheet" type="text/css" href="../vendors/datatables/datatables.min.css"/>
    <link rel="stylesheet" type="text/css" href="../assets/icon/font-awesome/css/font-awesome.min.css">
    <link href="../vendors/prism/prism-okaidia.css" rel="stylesheet">
    <link href="../vendors/overlayscrollbars/OverlayScrollbars.min.css" rel="stylesheet">
    <link href="../assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
    <link href="../assets/css/theme.min.css" rel="stylesheet" id="style-default">
    <link href="../assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
    <link href="../assets/css/user.min.css" rel="stylesheet" id="user-style-default">
 
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
                  <h5 class="fs-0 mb-0"><span class="fa fa-tags me-2 fs-0"></span> Sub Kategori Kriminal</h5>
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
                <table id="kategoriTable" class="display table table-striped table-bordered table-sm" >
                  <thead class="bg-primary text-white">
                    <tr>
                      <th>No</th>
                      <th>Nama Sub Kategori</th>
                      <th>Kategori Utama</th>
                      <th>Aksi</th>
                      
                    </tr>
                  </thead>
                  <tbody>
                  <?php
                    $no=1;
                    $stmt = $pdo->query("SELECT kriminal_sub_kategoris.*, kriminal_kategoris.nama as kategori_nama
                     FROM kriminal_sub_kategoris
                     JOIN kriminal_kategoris ON kriminal_sub_kategoris.kategori_id = kriminal_kategoris.id
                     WHERE kriminal_sub_kategoris.status=1 ORDER BY kriminal_sub_kategoris.id ASC");
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      echo "<tr>
                        <td>".$no++."</td>
                        <td>".htmlspecialchars($row['nama'], ENT_QUOTES)."</td>
                        <td>".htmlspecialchars($row['kategori_nama'], ENT_QUOTES)."</td>

                        <td>
                          <button class='btn btn-sm btn-info btnEditKategori' data-id='{$row['id']}' data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)."' data-kategori-id='".htmlspecialchars($row['kategori_id'],ENT_QUOTES)."' >Edit</button>
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
          <!-- Modal Tambah Data Kategori kriminal -->
          <div class="modal fade" id="modalTambahKategori" tabindex="-1" aria-labelledby="modalTambahKategoriLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formTambahKategori">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahKategoriLabel">Tambah Sub Kategori Kriminal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label for="namaSubKategori" class="form-label">Nama Sub Kategori</label>
                      <input type="text" class="form-control" id="namaSubKategori" name="namaSubKategori" required>
                    </div>
                    <div class="mb-3">
                      <label for="kategori_id" class="form-label">Kategori Utama</label>
                      <select class="form-select" id="kategori_id" name="kategori_id" required>
                        <option value="">Pilih Kategori</option>
                        <?php
                          $stmt = $pdo->query("SELECT * FROM kriminal_kategoris WHERE status=1");
                          while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='".$row['id']."'>".htmlspecialchars($row['nama'], ENT_QUOTES)."</option>";
                          }
                        ?>
                      </select>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                  </div>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
              </form>
            </div>
          </div>
          <!-- Modal Edit Kategori kriminal -->
          <div class="modal fade" id="modalEditKategori" tabindex="-1" aria-labelledby="modalEditKategoriLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formEditKategori">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalEditKategoriLabel">Edit Sub Kategori Kriminal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label for="edit_nama" class="form-label">Nama Sub Kategori Kriminal</label>
                      <input type="text" class="form-control" id="edit_nama" name="edit_nama" required>
                    </div>
                    <div class="mb-3">
                      <label for="edit_kategori_id" class="form-label">Kategori Utama</label>
                      <select class="form-select" id="edit_kategori_id" name="edit_kategori_id" required>
                        <option value="">Pilih Kategori</option>
                        <?php
                          $stmt = $pdo->query("SELECT * FROM kriminal_kategoris WHERE status=1");
                          while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='".$row['id']."'>".htmlspecialchars($row['nama'], ENT_QUOTES)."</option>";
                          }
                        ?>
                      </select>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                  </div>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
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
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
              </form>
            </div>
          </div>
          <?php include_once("footer.php") ?>
        </div>
      </div>
    </main>
    <!-- JavaScripts -->
       <script src="../vendors/jquery/jquery-3.7.0.min.js"></script>
    <script src="../vendors/datatables/datatables.min.js"></script>
    <script src="../assets/js/sub-kategori-kriminal.js"></script>
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
 