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
  $_SESSION["menu"]="kabupaten";
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
        header("Location: kabupaten?status=csrf_failed");
        exit;
    }
  }
  if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['namaKabupaten'])) {
      $kodeKabupaten = trim($_POST['kodeKabupaten']);
      $jenis = trim($_POST['jenis']);
      $namaKabupaten = trim($_POST['namaKabupaten']);
      if($namaKabupaten != "") {
          $stmtInsert = $pdo->prepare("INSERT INTO kabupatens (kode, jenis, nama, status) VALUES (:kode, :jenis, :nama, 1)");
          $stmtInsert->bindParam(':kode', $kodeKabupaten);
          $stmtInsert->bindParam(':jenis', $jenis);
          $stmtInsert->bindParam(':nama', $namaKabupaten);
          $stmtInsert->execute();
          // Redirect agar refresh page dan menghilangkan POST (juga agar tabel otomatis update)
          header("Location: kabupaten?status=sukses");
          exit;
      }
  }
  // Handle proses Update
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_id'], $_POST['edit_nama'])) {
    $edit_id = intval($_POST['edit_id']);
    $edit_nama = trim($_POST['edit_nama']);
    $edit_polres_id = intval($_POST['edit_polres_id']);
    if($edit_id && $edit_nama != "") {
      $stmtUpdate = $pdo->prepare("UPDATE kabupatens SET nama=:nama,polres_id=:polres_id, updated_at=NOW() WHERE id=:id");
      $stmtUpdate->execute([':nama'=>$edit_nama,':polres_id'=>$edit_polres_id, ':id'=>$edit_id]);
      header("Location: kabupaten?status=edit_sukses");
      exit;
    }
  }
  // Handle proses Hapus
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['hapus_id'])) {
    if($akses != "POLDA") {
      header("Location: kabupaten?status=hapus_gagal");
      exit;
    }
    $hapus_id = intval($_POST['hapus_id']);
    if($hapus_id) {
      $stmtDel = $pdo->prepare("UPDATE kabupatens SET status=0 WHERE id=:id");
      $stmtDel->execute([':id'=>$hapus_id]);
      header("Location: kabupaten?status=hapus_sukses");
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
    <title>Kabupaten | Peta Digital</title>
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
                  <?php elseif(isset($_GET['status']) && $_GET['status']=='hapus_gagal'): ?>
                <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
                  Hapus Gagal. Anda tidak memiliki akses untuk menghapus data.
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
                  <h5 class="fs-0 mb-0"><span class="fa fa-map me-2 fs-0"></span> Kabupaten</h5>
                </div>
                <div class="col-auto ms-auto">
                  <?php if($akses == "POLDA") { ?>
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahKabupaten">
                    Tambah Data
                  </button>
                  <?php } ?>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="KabupatenTable" class="display table table-striped table-bordered table-sm">
                  <thead class="bg-primary text-white">
                    <tr>
                      <th>ID</th>
                      <th>Kode</th>
                      <th>Nama</th>
                      <th>Jenis</th>
                      <th>Polres</th>
                      <th>Ttl Kec</th>
                      <th>Aksi</th>
                      
                    </tr>
                  </thead>
                  <tbody>
                  <?php
                    $no=1;
                    if($akses=="POLRES") {
                      $stmt = $pdo->query("SELECT kabupatens.*, polress.nama as polres_nama,
                      (SELECT COUNT(*) FROM kecamatans WHERE kecamatans.kabupaten_id=kabupatens.id) as total_kecamatan
                      FROM kabupatens
                      left join polress on kabupatens.polres_id=polress.id
                      WHERE kabupatens.status=1 and kabupatens.polres_id = $polres_id ORDER BY kabupatens.id ASC");
                    } else {
                      $stmt = $pdo->query("SELECT kabupatens.*, polress.nama as polres_nama,
                      (SELECT COUNT(*) FROM kecamatans WHERE kecamatans.kabupaten_id=kabupatens.id) as total_kecamatan
                       FROM kabupatens
                       left join polress on kabupatens.polres_id=polress.id
                       WHERE kabupatens.status=1 ORDER BY kabupatens.id ASC");
                    }
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      echo "<tr>

                        <td>".intval($row['id'])."</td>
                        <td>".htmlspecialchars($row['kode'], ENT_QUOTES)."</td>
                        <td>".htmlspecialchars($row['nama'], ENT_QUOTES)."</td>
                        <td>".htmlspecialchars($row['jenis'], ENT_QUOTES)."</td>
                        <td>".htmlspecialchars($row['polres_nama'] ?? '', ENT_QUOTES)."</td>
                        <td><span class='badge bg-secondary rounded-pill'>".intval($row['total_kecamatan'])."</span></td>
                        <td>
                          <button class='btn btn-sm btn-info btnEditKabupaten' data-id='{$row['id']}' 
                          data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)."' 
                          data-kode='".htmlspecialchars($row['kode'],ENT_QUOTES)."' 
                          data-polres-id='". intval($row['polres_id']) ."'
                          >Edit</button>
                          <button class='btn btn-sm btn-danger btnHapusKabupaten' data-id='{$row['id']}' data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)."'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <!-- Modal Tambah Data Kabupaten -->
          <div class="modal fade" id="modalTambahKabupaten" tabindex="-1" aria-labelledby="modalTambahKabupatenLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formTambahKabupaten">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahKabupatenLabel">Tambah Kabupaten</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                  </div>
                  <div class="modal-body">
                     <div class="mb-3">
                      <label for="namaKabupaten" class="form-label">Jenis</label>
                      <select class="form-select" id="jenis" name="jenis" required>
                        <option value="KOTA">KOTA</option>
                        <option value="KABUPATEN">KABUPATEN</option>
                      </select>
                    </div>
                     <div class="mb-3">
                      <label for="kodeKabupaten" class="form-label">Kode Kabupaten</label>
                      <input type="text" class="form-control" id="kodeKabupaten" name="kodeKabupaten" required>
                    </div>
                    <div class="mb-3">
                      <label for="namaKabupaten" class="form-label">Nama Kabupaten</label>
                      <input type="text" class="form-control" id="namaKabupaten" name="namaKabupaten" required>
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
          <div class="modal fade" id="modalEditKabupaten" tabindex="-1" aria-labelledby="modalEditKabupatenLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formEditKabupaten">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalEditKabupatenLabel">Edit Kabupaten</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label for="edit_kode" class="form-label">Kode Kabupaten</label>
                      <input type="text" class="form-control" id="edit_kode" name="edit_kode" readonly>
                    </div>
                    <div class="mb-3">
                      <label for="edit_nama" class="form-label">Nama Kabupaten</label>
                      <input type="text" class="form-control" id="edit_nama" name="edit_nama" required>
                    </div>
                    <div class="mb-3">
                      <label for="edit_polres_id" class="form-label">Polres</label>
                      <select class="form-select" id="edit_polres_id" name="edit_polres_id" required>
                        <option value="">Pilih Polres</option>
                        <?php
                          $stmtPolres = $pdo->query("SELECT * FROM polress WHERE status=1 ORDER BY id ASC");
                          while($polres = $stmtPolres->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='".$polres['id']."'>".$polres['nama']."</option>";
                          }
                        ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="totalKecamatan" class="form-label">List Kecamatan</label>
                      <ul id="listKecamatan" class="list-group">
                      </ul>
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
          <div class="modal fade" id="modalHapusKabupaten" tabindex="-1" aria-labelledby="modalHapusKabupatenLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formHapusKabupaten">
                <input type="hidden" name="hapus_id" id="hapus_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalHapusKabupatenLabel">Konfirmasi Hapus</h5>
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
    <script src="../assets/js/kabupaten.js"></script>
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
 