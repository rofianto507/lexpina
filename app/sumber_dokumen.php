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
  $_SESSION["menu"]="sumber_dokumen";
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
        header("Location: sumber-dokumen?status=csrf_failed");
        exit;
    }
  }

  // Handle proses Tambah
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['nama'], $_POST['tahun'], $_POST['tipe'], $_POST['keterangan'])) {
       
      $nama = trim($_POST['nama']);
      $tahun=$_POST['tahun'];
      $tipe=$_POST['tipe'];
      $keterangan=$_POST['keterangan'];
      if($nama != "" && $tahun!="" && $tipe!="" && $keterangan!="") {
          $stmtInsert = $pdo->prepare("INSERT INTO sumbers (nama,tahun,tipe,keterangan, status) VALUES (:nama, :tahun, :tipe, :keterangan, 1)");
          $stmtInsert->bindParam(':nama', $nama);
          $stmtInsert->bindParam(':tahun', $tahun);
          $stmtInsert->bindParam(':tipe', $tipe);
          $stmtInsert->bindParam(':keterangan', $keterangan);
          $stmtInsert->execute();
          // Redirect agar refresh page dan menghilangkan POST (juga agar tabel otomatis update)
          header("Location: sumber-dokumen?status=sukses");
          exit;
      }
  }
  // Handle proses Update
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_id'], $_POST['edit_nama'])) {
    $edit_id = intval($_POST['edit_id']);
    $edit_nama = trim($_POST['edit_nama']);
    $edit_tahun=trim($_POST['edit_tahun']);
    $edit_tipe=trim($_POST['edit_tipe']);
    $edit_keterangan=trim($_POST['edit_keterangan']);
    if($edit_id && $edit_nama != "") {
      $stmtUpdate = $pdo->prepare("UPDATE sumbers SET nama=:nama, tahun=:tahun, tipe=:tipe, keterangan=:keterangan, updated_at=NOW() WHERE id=:id");
      $stmtUpdate->execute([':nama'=>$edit_nama, ':tahun'=>$edit_tahun, ':tipe'=>$edit_tipe, ':keterangan'=>$edit_keterangan, ':id'=>$edit_id]);
      header("Location: sumber-dokumen?status=edit_sukses");
      exit;
    }
  }
  // Handle proses Hapus
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['hapus_id'])) {
    $hapus_id = intval($_POST['hapus_id']);
    if($hapus_id) {
      $stmtDel = $pdo->prepare("UPDATE sumbers SET status=0 WHERE id=:id");
      $stmtDel->execute([':id'=>$hapus_id]);
      header("Location: sumber-dokumen?status=hapus_sukses");
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
    <title>Sumber Dokumen | Peta Digital</title>
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
                  <h5 class="fs-0 mb-0"><span class="fa fa-folder me-2 fs-0"></span> Sumber Dokumen</h5>
                </div>
                <div class="col-auto ms-auto">
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahSumber">
                    Tambah Data
                  </button>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="PolresTable" class="display table table-striped table-bordered table-sm"  >
                  <thead class="bg-primary text-white">
                    <tr>
                      <th>ID</th>
                      <th>Nama</th>
                      <th>Tipe</th>
                      <th>Tahun</th>
                      <th>Aksi</th>
                      
                    </tr>
                  </thead>
                  <tbody>
                  <?php
                    $no=1;
                    $stmt = $pdo->query("SELECT * FROM sumbers WHERE status=1 ORDER BY id ASC");
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      echo "<tr>
                        <td>".intval($row['id'])."</td>
                        <td>".htmlspecialchars($row['nama'], ENT_QUOTES)."</td>
                        <td>".htmlspecialchars($row['tipe'], ENT_QUOTES)."</td>
                        <td>".intval($row['tahun'])."</td>
                        <td>
                          <button class='btn btn-sm btn-info btnEditSumber' data-id='{$row['id']}' data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)."' data-tipe='".htmlspecialchars($row['tipe'],ENT_QUOTES)."' data-tahun='".htmlspecialchars($row['tahun'],ENT_QUOTES)."' data-keterangan='".htmlspecialchars($row['keterangan'],ENT_QUOTES)."'>Edit</button>
                          <button class='btn btn-sm btn-danger btnHapusSumber' data-id='{$row['id']}' data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)."'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <!-- Modal Tambah Data Polres -->
          <div class="modal fade" id="modalTambahSumber" tabindex="-1" aria-labelledby="modalTambahSumberLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formTambahSumber">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahSumberLabel">Tambah Sumber Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label for="namaSumber" class="form-label">Nama Sumber</label>
                      <input type="text" class="form-control" id="namaSumber" name="nama" required>
                    </div>
                    <div class="mb-3">
                      <label for="tipeSumber" class="form-label">Tipe Sumber</label>
                      <select name="tipe" id="tipeSumber" class="form-select" required>
                        <option value="" selected>Pilih Tipe Sumber</option>
                        <option value="BENCANA">BENCANA</option>
                        <option value="KRIMINALITAS">KRIMINALITAS</option>
                        <option value="LALU LINTAS">LALU LINTAS</option>
                        <option value="LOKASI PENTING">LOKASI PENTING</option>
                        <option value="KASUS MENONJOL">KASUS MENONJOL</option>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="tahunSumber" class="form-label">Tahun</label>
                      <select name="tahun" id="tahunSumber" class="form-select" required>
                        <option value="" selected>Pilih Tahun</option>
                        <?php
                          $currentYear = date("Y");
                          for($year = $currentYear; $year >= 2020; $year--) {
                            echo "<option value='{$year}'>{$year}</option>";
                          }
                        ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="keteranganSumber" class="form-label">Keterangan</label>
                      <textarea class="form-control" id="keteranganSumber" name="keterangan" required></textarea>
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
          <div class="modal fade" id="modalEditSumber" tabindex="-1" aria-labelledby="modalEditSumberLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formEditSumber">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalEditSumberLabel">Edit Sumber Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label for="edit_nama" class="form-label">Nama Sumber</label>
                      <input type="text" class="form-control" id="edit_nama" name="edit_nama" required>
                    </div>
                    <div class="mb-3">
                      <label for="edit_tipe" class="form-label">Tipe Sumber</label>
                      <select name="edit_tipe" id="edit_tipe" class="form-select" required>
                        <option value="" selected>Pilih Tipe Sumber</option>
                         <option value="BENCANA">BENCANA</option>
                        <option value="KRIMINALITAS">KRIMINALITAS</option>
                        <option value="LALU LINTAS">LALU LINTAS</option>
                        <option value="LOKASI PENTING">LOKASI PENTING</option>
                        <option value="KASUS MENONJOL">KASUS MENONJOL</option>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="edit_tahun" class="form-label">Tahun</label>
                      <select name="edit_tahun" id="edit_tahun" class="form-select" required>
                        <option value="" selected>Pilih Tahun</option>
                        <?php
                          $currentYear = date("Y");
                          for($year = $currentYear; $year >= 2020; $year--) {
                            echo "<option value='{$year}'>{$year}</option>";
                          }
                        ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="edit_keterangan" class="form-label">Keterangan</label>
                      <textarea class="form-control" id="edit_keterangan" name="edit_keterangan" required></textarea>
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
          <div class="modal fade" id="modalHapusSumber" tabindex="-1" aria-labelledby="modalHapusSumberLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formHapusSumber">
                <input type="hidden" name="hapus_id" id="hapus_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalHapusSumberLabel">Konfirmasi Hapus</h5>
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
    <script src="../assets/js/sumber-dokumen.js"></script>
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
 