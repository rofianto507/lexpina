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
  $_SESSION["menu"]="polsek";
  $menu=$_SESSION["menu"];
  $nama=$_SESSION["nama"];
  $id=$_SESSION["id"];
  $username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
  $foto=$_SESSION["foto"];
  $last_login=$_SESSION["last_login"];
  $polres_id = $_SESSION["polres_id"] ?? null;
  $polsek_id = $_SESSION["polsek_id"] ?? null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
      ) {
        header("Location: polsek?status=csrf_failed");
        exit;
    }
  }
  if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['namaPolsek'])) {
     if($akses !== "POLDA" && $akses !== "POLRES"){
        //die("Unauthorized access");
        header("Location: polsek?status=add_gagal");
        exit;
    }
      $polres_id = intval($_POST['polres_id']);
      $namaPolsek = trim($_POST['namaPolsek']);
      if($namaPolsek != "") {
          $stmtInsert = $pdo->prepare("INSERT INTO polseks (nama, polres_id, status) VALUES (:nama, :polres_id, 1)");
          $stmtInsert->bindParam(':nama', $namaPolsek);
          $stmtInsert->bindParam(':polres_id', $polres_id);
          $stmtInsert->execute();
          // Redirect agar refresh page dan menghilangkan POST (juga agar tabel otomatis update)
          header("Location: polsek?status=sukses");
          exit;
      }
  }
  // Handle proses Update
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_id'], $_POST['edit_nama'])) {
    if($akses !== "POLDA" && $akses !== "POLRES"){
      //  die("Unauthorized access");
        header("Location: polsek?status=edit_gagal");
        exit;
    }
    $edit_id = intval($_POST['edit_id']);
    $edit_nama = trim($_POST['edit_nama']);
    $polres_id = intval($_POST['polres_id']);
    if($edit_id && $edit_nama != "") {
      $stmtUpdate = $pdo->prepare("UPDATE polseks SET nama=:nama, polres_id=:polres_id, updated_at=NOW() WHERE id=:id");
      $stmtUpdate->execute([':nama'=>$edit_nama, ':polres_id'=>$polres_id, ':id'=>$edit_id]);
      header("Location: polsek?status=edit_sukses");
      exit;
    }
  }
  // Handle proses Hapus
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['hapus_id'])) {
    if($akses != "POLDA") {
      header("Location: polsek?status=hapus_gagal");
      exit;
    }
    $hapus_id = intval($_POST['hapus_id']);
    if($hapus_id) {
      $stmtDel = $pdo->prepare("UPDATE polseks SET status=0 WHERE id=:id");
      $stmtDel->execute([':id'=>$hapus_id]);
      header("Location: polsek?status=hapus_sukses");
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
    <title>Polsek | Peta Digital</title>
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
                <?php elseif(isset($_GET['status']) && $_GET['status']=='edit_gagal'): ?>
                <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
                  Edit Gagal. Anda tidak memiliki akses untuk mengedit data.
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                  <?php elseif(isset($_GET['status']) && $_GET['status']=='add_gagal'): ?>
                <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
                  Tambah Gagal. Anda tidak memiliki akses untuk menambah data.
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
                  <h5 class="fs-0 mb-0"><span class="fa fa-home me-2 fs-0"></span> Polsek</h5>
                </div>
                <div class="col-auto ms-auto">
                  <?php if($akses=="POLDA"): ?>
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahPolsek">
                    Tambah Data
                  </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="PolsekTable" class="display table table-striped table-bordered table-sm" >
                  <thead class="bg-primary text-white">
                    <tr>
                      <th>ID</th>
                   
                      <th>Nama</th>
                      <th>User</th>
                      <th>Polres</th>
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
                    $no=1;
                    if($akses=="POLRES") {
                      $stmt = $pdo->prepare("SELECT polseks.*, polress.nama AS polres_nama, 
                      (SELECT username FROM users WHERE users.id=polseks.user_id) AS user 
                      FROM polseks JOIN polress ON polseks.polres_id = polress.id WHERE polseks.status=1 AND polseks.polres_id=? ORDER BY polseks.id ASC");
                      $stmt->execute([$polres_id]);
                    } else if($akses=="POLSEK") {
                      $stmt = $pdo->prepare("SELECT polseks.*, polress.nama AS polres_nama, 
                      (SELECT username FROM users WHERE users.id=polseks.user_id) AS user 
                      FROM polseks JOIN polress ON polseks.polres_id = polress.id WHERE polseks.status=1 AND polseks.id=? ORDER BY polseks.id ASC");
                      $stmt->execute([$polsek_id]);
                    } else {
                    $stmt = $pdo->query("SELECT polseks.*, polress.nama AS polres_nama, 
                    (SELECT username FROM users WHERE users.id=polseks.user_id) AS user 
                    FROM polseks JOIN polress ON polseks.polres_id = polress.id WHERE polseks.status=1 ORDER BY polseks.id ASC");
                    }
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      echo "<tr>

                        <td>".intval($row['id'])."</td>
                        <td>".htmlspecialchars($row['nama'], ENT_QUOTES)."</td>
                        <td><a class='text-800' href='pengguna-edit?id=".intval($row['user_id'])."'>".(htmlspecialchars($row['user'] ?? "", ENT_QUOTES) ?? "")."</a></td>
                        <td>".htmlspecialchars($row['polres_nama'], ENT_QUOTES)."</td>

                        <td>
                          <button class='btn btn-sm btn-info btnEditPolsek' data-id='{$row['id']}' data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)."' data-polres-id='{$row['polres_id']}'>Edit</button>
                          <button class='btn btn-sm btn-danger btnHapusPolsek' data-id='{$row['id']}' data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)."'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <!-- Modal Tambah Data Polsek -->
          <div class="modal fade" id="modalTambahPolsek" tabindex="-1" aria-labelledby="modalTambahPolsekLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formTambahPolsek">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahPolsekLabel">Tambah Polsek</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label for="polres_id" class="form-label">Pilih Polres</label>
                      <select class="form-select" id="polres_id" name="polres_id" required>
                        <option value="" selected disabled>-- Pilih Polres --</option>
                        <?php
                        if($akses == "POLRES") {
                          $stmtPolres = $pdo->prepare("SELECT id, nama FROM polress WHERE status=1 AND id=? ORDER BY nama ASC");
                          $stmtPolres->execute([$polres_id]);
                        }else if($akses == "POLSEK") {
                          $stmtPolres = $pdo->prepare("SELECT polress.id, polress.nama FROM polress left join polseks on polress.id = polseks.polres_id WHERE polress.status=1 and polseks.id=? ORDER BY polress.nama ASC");
                          $stmtPolres->execute([$polsek_id]);
                        }else if($akses == "POLDA") {
                          $stmtPolres = $pdo->query("SELECT id, nama FROM polress WHERE status=1 ORDER BY nama ASC");
                        }
                          while($rowPolres = $stmtPolres->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='".$rowPolres['id']."'>".$rowPolres['nama']."</option>";
                          }
                        ?>
                      </select>
                    </div>   
                    <div class="mb-3">
                      <label for="namaPolsek" class="form-label">Nama Polsek</label>
                      <input type="text" class="form-control" id="namaPolsek" name="namaPolsek" required>
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
          <div class="modal fade" id="modalEditPolsek" tabindex="-1" aria-labelledby="modalEditPolsekLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formEditPolsek">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalEditPolsekLabel">Edit Polsek</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label for="polres_id_edit" class="form-label">Pilih Polres</label>
                      <select class="form-select" id="polres_id_edit" name="polres_id" required>
                        <option value="" selected disabled>-- Pilih Polres --</option>
                        <?php
                        if($akses == "POLRES") {
                          $stmtPolres = $pdo->prepare("SELECT id, nama FROM polress WHERE status=1 AND id=? ORDER BY nama ASC");
                          $stmtPolres->execute([$polres_id]);
                        } else if($akses == "POLSEK") {
                          $stmtPolres = $pdo->prepare("SELECT polress.id, polress.nama FROM polress LEFT JOIN polseks ON polress.id = polseks.polres_id WHERE polress.status=1 AND polseks.id=? ORDER BY polress.nama ASC");
                          $stmtPolres->execute([$polsek_id]);
                        }else if($akses == "POLDA") {
                          $stmtPolres = $pdo->query("SELECT id, nama FROM polress WHERE status=1 ORDER BY nama ASC");
                        }
                          while($rowPolres = $stmtPolres->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='".$rowPolres['id']."'>".$rowPolres['nama']."</option>";
                          }
                        ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="edit_nama" class="form-label">Nama Polsek</label>
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
          <div class="modal fade" id="modalHapusPolsek" tabindex="-1" aria-labelledby="modalHapusPolsekLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formHapusPolsek">
                <input type="hidden" name="hapus_id" id="hapus_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalHapusPolsekLabel">Konfirmasi Hapus</h5>
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
    <script src="../assets/js/polsek.js"></script>
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
 