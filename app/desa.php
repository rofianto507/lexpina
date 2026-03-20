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
  $_SESSION["menu"]="desa";
  $menu=$_SESSION["menu"];
  $nama=$_SESSION["nama"];
  $id=$_SESSION["id"];
  $username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
  $foto=$_SESSION["foto"];
  $polres_id=$_SESSION["polres_id"] ?? null;
  $polsek_id=$_SESSION["polsek_id"] ?? null;
  $last_login=$_SESSION["last_login"];
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
      ) {
        header("Location: desa?status=csrf_failed");
        exit;
    }
  }
  if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['namadesa'])) {
      $kodedesa = trim($_POST['kodedesa']);
      $jenis = trim($_POST['jenis']);
      $kecamatan_id = trim($_POST['kecamatan_id']);
      $namadesa = trim($_POST['namadesa']);
      if($namadesa != "") {
          $stmtInsert = $pdo->prepare("INSERT INTO desas (kode, jenis, kecamatan_id, nama, status) VALUES (:kode, :jenis, :kecamatan_id, :nama, 1)");
          $stmtInsert->bindParam(':kode', $kodedesa);
          $stmtInsert->bindParam(':jenis', $jenis);
          $stmtInsert->bindParam(':kecamatan_id', $kecamatan_id);
          $stmtInsert->bindParam(':nama', $namadesa);
          $stmtInsert->execute();
          // Redirect agar refresh page dan menghilangkan POST (juga agar tabel otomatis update)
          header("Location: desa?status=sukses");
          exit;
      }
  }
  // Handle proses Update
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_id'], $_POST['edit_nama'])) {
    $edit_id = intval($_POST['edit_id']);
    $edit_jenis = trim($_POST['edit_jenis']);
    $edit_kecamatan_id = intval($_POST['edit_kecamatan_id']);
    $edit_nama = trim($_POST['edit_nama']);
    if($edit_id && $edit_nama != "") {
      $stmtUpdate = $pdo->prepare("UPDATE desas SET nama=:nama, kecamatan_id=:kecamatan_id, jenis=:jenis, updated_at=NOW() WHERE id=:id");
      $stmtUpdate->execute([':nama'=>$edit_nama, ':kecamatan_id'=>$edit_kecamatan_id, ':jenis'=>$edit_jenis, ':id'=>$edit_id]);
      header("Location: desa?status=edit_sukses");
      exit;
    }
  }
  // Handle proses Hapus
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['hapus_id'])) {
    if($akses != "POLDA") {
      header("Location: desa?status=hapus_gagal");
      exit;
    }
    $hapus_id = intval($_POST['hapus_id']);
    if($hapus_id) {
      $stmtDel = $pdo->prepare("update desas set status=0 WHERE id=:id");
      $stmtDel->execute([':id'=>$hapus_id]);
      header("Location: desa?status=hapus_sukses");
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
    <title>Desa | Peta Digital</title>
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
                  Data berhasil ditambahkan.
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                <?php if(isset($_GET['status']) && $_GET['status']=='edit_sukses'): ?>
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                  Data berhasil diubah.
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php elseif(isset($_GET['status']) && $_GET['status']=='hapus_sukses'): ?>
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
                  <h5 class="fs-0 mb-0"><span class="fa fa-map me-2 fs-0"></span> Desa</h5>
                </div>
                <div class="col-auto ms-auto">
                  <?php if($akses == "POLDA"): ?>
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahdesa">
                    Tambah Data
                  </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="desaTable" class="display table table-striped table-bordered table-sm" >
                  <thead class="bg-primary text-white">
                    <tr>
                      <th>ID</th>
                      <th>Kode</th>
                      <th>Nama</th>
                      <th>Jenis</th>
                      <th>Kecamatan</th>
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
                      <th></th>
                    </tr>
                  </tfoot>
                  <tbody>
                  <?php
                    $no=1;
                    if($akses=="POLSEK"){
                      $stmt = $pdo->query("SELECT desas.*, kecamatans.nama AS kecamatan_nama FROM desas JOIN kecamatans ON desas.kecamatan_id = kecamatans.id 
                      WHERE desas.status=1 AND kecamatans.polsek_id = $polsek_id ORDER BY desas.id ASC");
                    } else if($akses=="POLRES"){
                      $stmt = $pdo->query("SELECT desas.*, kecamatans.nama AS kecamatan_nama FROM desas 
                      JOIN kecamatans ON desas.kecamatan_id = kecamatans.id 
                      JOIN kabupatens ON kecamatans.kabupaten_id = kabupatens.id
                      WHERE desas.status=1 AND kabupatens.polres_id = $polres_id ORDER BY desas.id ASC");
                    } else {
                    $stmt = $pdo->query("SELECT desas.*, kecamatans.nama AS kecamatan_nama FROM desas JOIN kecamatans ON desas.kecamatan_id = kecamatans.id 
                    WHERE desas.status=1 ORDER BY desas.id ASC");
                    }
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      echo "<tr>
                        <td>".intval($row['id'])."</td>
                        <td>".htmlspecialchars($row['kode'], ENT_QUOTES)."</td>
                        <td>".htmlspecialchars($row['nama'], ENT_QUOTES)."</td>
                        <td>".htmlspecialchars($row['jenis'], ENT_QUOTES)."</td>
                        <td>".htmlspecialchars($row['kecamatan_nama'], ENT_QUOTES)."</td>
                        <td>
                          <button class='btn btn-sm btn-info btnEditdesa' data-id='{$row['id']}' data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)." ' data-kecamatan-id='{$row['kecamatan_id']}' data-kode='".htmlspecialchars($row['kode'],ENT_QUOTES)."' data-jenis='".htmlspecialchars($row['jenis'],ENT_QUOTES)."'>Edit</button>
                          <button class='btn btn-sm btn-danger btnHapusdesa' data-id='{$row['id']}' data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)."'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <!-- Modal Tambah Data desa -->
          <div class="modal fade" id="modalTambahdesa" tabindex="-1" aria-labelledby="modalTambahdesaLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formTambahdesa">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahdesaLabel">Tambah desa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label for="jenis" class="form-label">Jenis</label>
                      <select class="form-select" id="jenis" name="jenis" required>
                        <option value="DESA">DESA</option>
                        <option value="KELURAHAN">KELURAHAN</option>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="namadesa" class="form-label">Kecamatan</label>
                      <select class="form-select" id="kecamatan_id" name="kecamatan_id" required>
                        <option value="">Pilih Kecamatan</option>
                        <?php
                          $stmtKecamatan = $pdo->query("
                          SELECT kecamatans.*,kabupatens.nama AS kabupaten_nama FROM kecamatans 
                          JOIN kabupatens ON kecamatans.kabupaten_id = kabupatens.id 
                          WHERE kecamatans.status=1 ORDER BY kecamatans.id ASC");
                          while($kecamatan = $stmtKecamatan->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='".$kecamatan['id']."'>".$kecamatan['kabupaten_nama']." - ".$kecamatan['nama']."</option>";
                          }
                        ?>
                      </select>
                    </div>
                     <div class="mb-3">
                      <label for="kodedesa" class="form-label">Kode desa</label>
                      <input type="text" class="form-control" id="kodedesa" name="kodedesa" required>
                    </div>
                    <div class="mb-3">
                      <label for="namadesa" class="form-label">Nama desa</label>
                      <input type="text" class="form-control" id="namadesa" name="namadesa" required>
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
          <!-- Modal Edit Jenis TKP -->
          <div class="modal fade" id="modalEditdesa" tabindex="-1" aria-labelledby="modalEditdesaLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formEditdesa">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalEditdesaLabel">Edit desa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label for="edit_jenis" class="form-label">Jenis</label>
                      <select class="form-select" id="edit_jenis" name="edit_jenis" required>
                        <option value="DESA">DESA</option>
                        <option value="KELURAHAN">KELURAHAN</option>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="edit_kecamatan_id" class="form-label">Kecamatan</label>
                      <select class="form-select" id="edit_kecamatan_id" name="edit_kecamatan_id" required>
                        <option value="">Pilih Kecamatan</option>
                        <?php
                        if($akses=="POLRES"){
                          $stmtKecamatan = $pdo->query("SELECT kecamatans.* FROM kecamatans join kabupatens ON kecamatans.kabupaten_id = kabupatens.id WHERE kecamatans.status=1 AND kabupatens.polres_id = $polres_id ORDER BY kecamatans.id ASC");
                        } else if($akses=="POLSEK"){
                          $stmtKecamatan = $pdo->query("SELECT kecamatans.* FROM kecamatans  WHERE kecamatans.status=1 AND kecamatans.polsek_id = $polsek_id ORDER BY kecamatans.id ASC");
                        } else {
                          $stmtKecamatan = $pdo->query("SELECT * FROM kecamatans WHERE status=1 ORDER BY id ASC");
                        }
                          while($kecamatan = $stmtKecamatan->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='".$kecamatan['id']."'>".$kecamatan['nama']."</option>";
                          }
                        ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="edit_kode" class="form-label">Kode desa</label>
                      <input type="text" class="form-control" id="edit_kode" name="edit_kode" readonly>
                    </div>
                    <div class="mb-3">
                      <label for="edit_nama" class="form-label">Nama desa</label>
                      <input type="text" class="form-control" id="edit_nama" name="edit_nama" required>
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
          <div class="modal fade" id="modalHapusdesa" tabindex="-1" aria-labelledby="modalHapusdesaLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formHapusdesa">
                <input type="hidden" name="hapus_id" id="hapus_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalHapusdesaLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <p>Yakin ingin menghapus data <b id="hapus_nama"></b>?</p>
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
    <script src="../assets/js/desa.js"></script>
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
 