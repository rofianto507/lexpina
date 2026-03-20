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
  $_SESSION["menu"]="pengguna";
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
        // Token tidak valid, tolak permintaan
        header("Location: pengguna?status=csrf_failed");
        exit;
    }
  }
  if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_inp'])) {
    $fotoin = "";
    // PROSES upload foto
    if(!empty($_FILES['foto']['name'])) {
      $finfo = pathinfo($_FILES['foto']['name']);
      $ext = strtolower($finfo['extension']);
      $allowed = ['jpg','jpeg','png'];
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime = finfo_file($finfo, $_FILES['foto']['tmp_name']);
      $allowed_mime = ['image/jpeg','image/png'];
      if(!in_array($mime,$allowed_mime)){
        header("Location: pengguna?status=add_gagal_foto");
        exit;
      }
      if($_FILES['foto']['size'] > 2*1024*1024){
         header("Location: pengguna?status=add_gagal_ukuran");
        exit;
      }
      if(in_array($ext, $allowed)) {
        $new_name = uniqid('pengguna_').'.'.$ext;
        $upload_dir = __DIR__ . '/../public/upload/pengguna';
        if(!is_dir($upload_dir)) mkdir($upload_dir,0777,true);
        $target = $upload_dir . '/' . $new_name;
        if(move_uploaded_file($_FILES['foto']['tmp_name'], $target)) {
          $fotoin = $new_name;
        }
      }
    }
    $akses_inp = trim($_POST['akses_inp'] ?? '');
    $username_inp = trim($_POST['username_inp'] ?? '');
    $password_inp = trim($_POST['password_inp'] ?? '');
    $strength_error = '';
    if(strlen($password_inp) < 8 
      || !preg_match('/[A-Z]/', $password_inp)
      || !preg_match('/[a-z]/', $password_inp)
      || !preg_match('/[0-9]/', $password_inp)
      || !preg_match('/[\W_]/', $password_inp)) {
        $strength_error = "Password minimal 8 karakter, mengandung huruf kapital, huruf kecil, angka dan simbol.";
    }
    if($strength_error) {
        $error = $strength_error;
    }
    $nama_inp = trim($_POST['nama_inp'] ?? '');
    $polres_id = intval($_POST['polres_id'] ?? 0);
    $polsek_id = intval($_POST['polsek_id'] ?? 0);
    if($akses_inp=="POLSEK" && (!$polres_id || !$polsek_id)) {
      $error = "Polres dan Polsek harus dipilih untuk akses POLSEK!";
    }
    if($nama_inp && $username_inp && $password_inp && $akses_inp) {
      $cek = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username LIMIT 1");
      $cek->execute([':username'=>$username_inp]);
      $sudahAda = $cek->fetchColumn();
      if($sudahAda) {
        $error = 'Username sudah terdaftar, silakan gunakan yang lain.';
       }else{
        $stmtInsert = $pdo->prepare("INSERT INTO users (username,password,nama,akses,foto,status)
        VALUES (:username, :password, :nama, :akses, :foto, 1)");
        $stmtInsert->execute([
          ':username'=>$username_inp,
          ':password'=>password_hash($password_inp, PASSWORD_DEFAULT),
          ':nama'=>$nama_inp,
          ':akses'=>$akses_inp,
          ':foto'=>$fotoin
        ]);
        $new_user_id = $pdo->lastInsertId();
        if($akses_inp=="POLRES" && $polres_id) {
          $pdo->prepare("UPDATE polress SET user_id=?,updated_at=NOW() WHERE id=?")->execute([$new_user_id, $polres_id]);
        }
        if($akses_inp=="POLSEK" && $polsek_id) {
          $pdo->prepare("UPDATE polseks SET user_id=?,updated_at=NOW() WHERE id=?")->execute([$new_user_id, $polsek_id]);
        }
        header("Location: pengguna?status=sukses");
        exit;
      }
      
    }
  }

// Hapus pengguna
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['hapus_id'])) {
  if($akses !== 'POLDA') {
    $error = "Anda tidak memiliki izin untuk menghapus pengguna.";
  } else {
    $hapus_id = intval($_POST['hapus_id']);
    if($hapus_id) {
      $stmtDel = $pdo->prepare("UPDATE users SET status=0 WHERE id=:id");
      $stmtDel->execute([':id'=>$hapus_id]);
      header("Location: pengguna?status=hapus_sukses");
      exit;
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
    <title>Pengguna | Peta Digital</title>
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
                <?php elseif(isset($_GET['status']) && $_GET['status']=='add_gagal_foto'): ?>
                <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                  Gagal tambah pengguna. File foto tidak valid!
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php elseif(isset($_GET['status']) && $_GET['status']=='add_gagal_ukuran'): ?>
                <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                  Gagal tambah pengguna. Ukuran foto melebihi 2mb!
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                 <?php elseif(isset($_GET['status']) && $_GET['status']=='csrf_failed'): ?>
                <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                  <strong>Akses ditolak!</strong> Token keamanan tidak valid. Silakan muat ulang halaman dan coba lagi.
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                <?php if(isset($error) && $error): ?>
                        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                          <?= htmlspecialchars($error) ?>
                          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                      <?php endif; ?>
          <div class="card mb-3">
            <div class="card-header">
              <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                  <h5 class="fs-0 mb-0"><span class="fa fa-users me-2 fs-0"></span> Data Pengguna</h5>
                </div>
                <div class="col-auto ms-auto">
                  <?php if($akses=="POLDA"): ?>
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahPengguna">
                    Tambah Data
                  </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="penggunaTable" class="display table table-striped table-bordered table-sm" >
                   <thead class="bg-primary text-white">
                    <tr>
                      <th>ID</th>
                      <th>Username</th>
                      <th>Nama</th>
                      <th>Akses</th>
                      <th>Last In</th>
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
                  if($akses=="POLDA") {
                    $q = "SELECT *from users WHERE status=1 ORDER BY id DESC";
                  }else{
                    $q = "SELECT *from users WHERE status=1 and id=$id ORDER BY id DESC";
                  }
                    $stmt = $pdo->query($q);
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      echo "<tr>
                        <td>".intval($row['id'])."</td>
                        <td>".htmlspecialchars($row['username'], ENT_QUOTES)."</td>
                        <td>".htmlspecialchars($row['nama'], ENT_QUOTES)."</td>
                        <td>".htmlspecialchars($row['akses'], ENT_QUOTES)."</td>
                        <td>".htmlspecialchars($row['updated_at'] ?? '', ENT_QUOTES)."</td>

                        <td>
                          <a href='pengguna-edit?id={$row['id']}' class='btn btn-sm btn-info' >Edit</a>
                         <button class='btn btn-sm btn-danger btnHapusPengguna' data-id='{$row['id']}' data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)."'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                   
                </table>
              </div>
            </div>
          </div>
           <div class="modal fade" id="modalTambahPengguna" tabindex="-1" aria-labelledby="modalTambahPenggunaLabel" aria-hidden="true">
              <div class="modal-dialog modal-lg">
                <form method="POST" id="formTambahPengguna" enctype="multipart/form-data">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Tambah Pengguna</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      
                      <div class="mb-3">
                        <label class="form-label">Akses</label>
                        <select class="form-select" name="akses_inp"  required>
                          <option value="">- Pilih Akses -</option>
                          <option value="POLDA">POLDA</option>
                          <option value="POLRES">POLRES</option>
                          <option value="POLSEK">POLSEK</option>
                          <option value="DITNARKOBA">DITNARKOBA</option>
                          <option value="DITRESKRIMSUS">DITRESKRIMSUS</option>
                          <option value="DITRESKRIMUM">DITRESKRIMUM</option>
                          <option value="DITINTELKAM">DITINTELKAM</option>
                          <option value="DITPOLAIRUD">DITPOLAIRUD</option>
                          <option value="DITLANTAS">DITLANTAS</option>
                          <option value="DITSAMAPTA">DITSAMAPTA</option>
                          <option value="DITSABHARA">DITSABHARA</option>
                          <option value="PPA">PPA</option>
                          <option value="PPO">PPO</option>
                          <option value="SPKT">SPKT</option>
                        </select>
                      </div>
                      <!-- POLRES: muncul jika akses POLRES/POLSEK -->
                      <div class="mb-3 d-none" id="groupPolres" >
                        <label class="form-label">Polres</label>
                        <select class="form-select" name="polres_id" id="polres_idInp">
                          <option value="">- Pilih Polres -</option>
                          <?php
                            $stmtPolres = $pdo->query("SELECT id, nama FROM polress where status=1 ORDER BY nama");
                            while($polres = $stmtPolres->fetch(PDO::FETCH_ASSOC)) {
                              echo "<option value='{$polres['id']}'>".htmlspecialchars($polres['nama'])."</option>";
                            }
                          ?>
                        </select>
                      </div>

                      <!-- POLSEK: hanya muncul jika akses=POLSEK -->
                      <div class="mb-3 d-none" id="groupPolsek" >
                        <label class="form-label">Polsek</label>
                        <select class="form-select" name="polsek_id" id="polsek_idInp">
                          <option value="">- Pilih Polsek -</option>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username_inp" required>
                      </div>
                     <div class="mb-3 position-relative">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                          <input type="password" class="form-control" name="password_inp" id="password_inp" required>
                          <button type="button" class="btn btn-outline-secondary" id="togglePassword" tabindex="-1">
                            <i class="fa fa-eye-slash" id="togglePasswordIcon"></i>
                          </button>
                        </div>
                        <small id="passwordHelp" class="form-text text-muted">
                          Password minimal 8 karakter, mengandung huruf besar, kecil, angka, dan simbol.
                        </small>
                        <div id="passStrength" class="mt-1 text-danger"></div>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" class="form-control" name="nama_inp" required>
                      </div>
                      
                      <div class="mb-3">
                        <label class="form-label">Foto (JPG/PNG, opsional)</label>
                        <input type="file" class="form-control" name="foto" accept=".jpg,.jpeg,.png">
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
          <!-- Modal Edit Lokasi -->
           

           <!-- Modal Hapus Pengguna -->
          <div class="modal fade" id="modalHapusPengguna" tabindex="-1" aria-labelledby="modalHapusPenggunaLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formHapusPengguna">
                <input type="hidden" name="hapus_id" id="hapus_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalHapusPenggunaLabel">Konfirmasi Hapus</h5>
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
    <script src="../assets/js/pengguna.js"></script>
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
 