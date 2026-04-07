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
  $_SESSION["menu"]="anggota";
  $id_login=$_SESSION["id"];
    $menu=$_SESSION["menu"];
  $nama=$_SESSION["nama"];
  $id=$_SESSION["id"];
  $username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
  $foto=$_SESSION["foto"];
  $last_login=$_SESSION["last_login"];
  $polres_id=$_SESSION["polres_id"] ?? null;
  $polsek_id=$_SESSION["polsek_id"] ?? null;
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
      ) {
        // Token tidak valid, tolak permintaan
        header("Location: anggota_edit?id=$id_user&status=csrf_failed");
        exit;
    }
  }
  
  $id_user = isset($_GET['id']) ? intval($_GET['id']) : 0;
  if(!$id_user){ header("Location: anggota"); exit; }

  // Ambil data user
  $quser = $pdo->prepare("SELECT anggotas.* FROM anggotas WHERE anggotas.id=? LIMIT 1"); 
  $quser->execute([$id_user]);
  $data = $quser->fetch(PDO::FETCH_ASSOC);
  if(!$data) { header("Location: anggota"); exit; }

  // Untuk akses drop-down
  $aksesList = [
    'POLDA','POLRES','POLSEK' 
  ];

  // Ambil polres dan polsek milik user jika ada
  $polres_id = $data['polres_id'] ?? 0; 
  $polsek_id = $data['polsek_id'] ?? 0;
  $polsek_polres_id = $data['polres_id'] ?? 0;

  // Proses update
  if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['edit_id'])){
    $edit_id = intval($_POST['edit_id']);
    $nama_inp = trim($_POST['nama_inp'] ?? "");
    $username_inp = trim($_POST['username_inp'] ?? "");
    $akses_inp = trim($_POST['akses_inp'] ?? "");
    $password_inp = trim($_POST['password_inp'] ?? "");
    // Password strength check if user wants to change password
    if ($password_inp) {
      if(strlen($password_inp) < 8 ||
        !preg_match('/[A-Z]/', $password_inp) ||
        !preg_match('/[a-z]/', $password_inp) ||
        !preg_match('/[0-9]/', $password_inp) ||
        !preg_match('/[\W_]/', $password_inp)) {
        $error = "Password minimal 8 karakter, mengandung huruf besar, huruf kecil, angka, dan simbol.";
      }
    }
    $polres_id_inp = intval($_POST['polres_id'] ?? 0);
    $polsek_id_inp = intval($_POST['polsek_id'] ?? 0);
    $fotoin = $data['foto'];

    // Cek username unik (jika diganti)
    if($username_inp && $username_inp != $data['username']) {
      $cek = $pdo->prepare("SELECT COUNT(*) FROM anggotas WHERE username=? AND id<>?");
      $cek->execute([$username_inp, $edit_id]);
      if($cek->fetchColumn()) $error = "Username sudah dipakai user lain!";
    }

    // Upload foto jika ada
    if(empty($error) && !empty($_FILES['foto']['name'])){
      $finfo = pathinfo($_FILES['foto']['name']);
      $ext = strtolower($finfo['extension']);
      $allowed = ['jpg','jpeg','png'];
      if(in_array($ext, $allowed)){
        $new_name = uniqid('anggota_').'.'.$ext;
        $upload_dir = __DIR__ . '/../public/upload/pengguna';
        if(!is_dir($upload_dir)) mkdir($upload_dir,0777,true);
        $target = $upload_dir . '/' . $new_name;
        if(move_uploaded_file($_FILES['foto']['tmp_name'], $target)){
          $fotoin = $new_name;
        }
      }
    }
    // update anggota
    if(empty($error) && $nama_inp && $username_inp && $akses_inp){
      $setpass = $password_inp ? "password=:password," : "";
      $params = [
        ':nama'=>$nama_inp,
        ':username'=>$username_inp,
        ':akses'=>$akses_inp,
        ':foto'=>$fotoin,
        ':edit_id'=>$edit_id
      ];
      $sql = "UPDATE anggotas SET 
                nama=:nama,
                username=:username,
                akses=:akses,
                foto=:foto,
                updated_at=NOW()";
      if($password_inp){
        $sql .= ", password=:password";
        $params[':password'] = password_hash($password_inp,PASSWORD_DEFAULT);
      }
      $sql .= " WHERE id=:edit_id";
      $stmtEdit = $pdo->prepare($sql);
      $stmtEdit->execute($params);
   
      // Reset semua user_id di polress/polseks yg tertaut ke user ini (agar bisa ganti polres/polsek)
      $pdo->prepare("UPDATE anggotas SET polres_id=NULL WHERE id=?")->execute([$edit_id]);
      $pdo->prepare("UPDATE anggotas SET polsek_id=NULL WHERE id=?")->execute([$edit_id]);

      // Jika akses POLRES dan polres_id valid, update polress.user_id
      if($akses_inp=="POLRES" && $polres_id_inp) {
        $pdo->prepare("UPDATE anggotas SET polres_id=? WHERE id=?")->execute([$polres_id_inp, $edit_id]);
      }
      // Jika akses POLSEK, update polseks.user_id
      if($akses_inp=="POLSEK" && $polsek_id_inp) {
        $pdo->prepare("UPDATE anggotas SET polres_id=?, polsek_id=? WHERE id=?")->execute([$polres_id_inp, $polsek_id_inp, $edit_id]);
      }

      header("Location: anggota?status=edit_sukses"); exit;
    }
  }

  // Dropdown Polres/Polsek
  if($akses=="POLDA") {
    $polresList = $pdo->query("SELECT id, nama FROM polress ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
  }else if($akses=="POLRES") {
    $polresList = $pdo->prepare("SELECT id, nama FROM polress WHERE user_id=? ORDER BY nama");
    $polresList->execute([$id]);
    $polresList = $polresList->fetchAll(PDO::FETCH_ASSOC);
  } else if($akses=="POLSEK") {
    $polresList = $pdo->prepare("SELECT polress.id, polress.nama FROM polseks join polress on polseks.polres_id=polress.id WHERE polseks.id=? ORDER BY polress.nama");
    $polresList->execute([$polsek_id]);
    $polresList = $polresList->fetchAll(PDO::FETCH_ASSOC);  
  }else{
    $polresList = [];
  }
  $polsekAll = [];
  foreach($polresList as $pr){
    $q = $pdo->prepare("SELECT id, nama FROM polseks WHERE polres_id=? and status=1 ORDER BY nama");
    $q->execute([$pr['id']]);
    $polsekAll[$pr['id']] = $q->fetchAll(PDO::FETCH_ASSOC);
  }

?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta charset="utf-8">
  <title>Edit Anggota | Peta Digital</title>
<link rel="apple-touch-icon" sizes="180x180" href="../assets/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/img/favicons/favicon-16x16.png">
    <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicons/favicon.ico">
    <link rel="manifest" href="../assets/img/favicons/manifest.json">
    <meta name="msapplication-TileImage" content="../assets/img/favicons/mstile-150x150.png">
    <meta name="theme-color" content="#ffffff">
    <script src="../assets/js/config.js"></script>
    <script src="../vendors/overlayscrollbars/OverlayScrollbars.min.js"></script>
     <link href="../vendors/prism/prism-okaidia.css" rel="stylesheet">
        <link rel="stylesheet" type="text/css" href="../assets/icon/font-awesome/css/font-awesome.min.css">
     <link href="../vendors/overlayscrollbars/OverlayScrollbars.min.css" rel="stylesheet">
    <link href="../assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
    <link href="../assets/css/theme.min.css" rel="stylesheet" id="style-default">
    <link href="../assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
    <link href="../assets/css/user.min.css" rel="stylesheet" id="user-style-default">
 
</head>
<body>
  <main class="main" id="top">
    <div class="container-fluid" data-layout="container">
      <?php include_once("navbar.php"); ?>
      <div class="content">
        <?php include_once("header.php"); ?>
        <div class="row">
          <div class="col-lg-8">
            <div class="card mb-3">
              <div class="card-header bg-light">
                <div class="row flex-between-end">
                  <div class="col-auto align-self-center">
                    <h5 class="fs-0 mb-0"><span class="fa fa-edit me-2 fs-0"></span> Edit Data Anggota</h5>
                  </div>
                  <div class="col-auto ms-auto">
                    <a href="anggota" class="btn btn-falcon-default btn-sm">Kembali ke Data Anggota</a>
                  </div>
                </div>
              </div>
              <div class="card-body">
              <?php if(isset($error)&&$error): ?>
              <div class="alert alert-danger"><?=$error?></div>
              <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                  <input type="hidden" name="edit_id" value="<?=$data['id']?>">
                  <div class="mb-3">
                    <label class="form-label">Akses</label>
                    <select class="form-select" name="akses_inp" id="aksesInp" required>
                        <option value="">- Pilih Akses -</option>
                        <?php foreach($aksesList as $akses): ?>
                        <option value="<?=$akses?>" <?=($data['akses']==$akses?'selected':'')?>><?=$akses?></option>
                        <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username_inp" value="<?=htmlspecialchars($data['username'])?>" required readonly>
                  </div>
                  <div class="mb-3 position-relative">
                      <label class="form-label">Password</label>
                      <div class="input-group">
                        <input type="password" class="form-control" name="password_inp" id="password_inp" placeholder="Kosongkan jika tidak ganti password.">
                        <button type="button" class="btn btn-outline-secondary" id="togglePassword" tabindex="-1">
                          <i class="fa fa-eye-slash" id="togglePasswordIcon"></i>
                        </button>
                      </div>
                      <small class="form-text text-muted">
                        Kosongkan jika tidak ganti password. Password minimal 8 karakter, mengandung huruf besar, kecil, angka &amp; simbol.
                      </small>
                      <div id="passStrength" class="mt-1 text-danger"></div>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" class="form-control" name="nama_inp" value="<?=htmlspecialchars($data['nama'])?>" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Foto</label>
                    <?php if($data['foto']): ?>
                      <br><img src="../public/upload/pengguna/<?=$data['foto']?>" class="preview-foto-user" alt="Foto anggota">
                    <?php endif; ?>
                    <input type="file" class="form-control" name="foto" accept=".jpg,.jpeg,.png">
                  </div>
                  <!-- Group Polres -->
                  <div class="mb-3 d-none" id="groupPolres"  >
                    <label class="form-label">Polres</label>
                    <select class="form-select" name="polres_id" id="polres_idInp">
                      
                      <?php foreach($polresList as $pr): ?>
                        <option value="<?=$pr['id']?>" <?=($polres_id==$pr['id']||($polsek_polres_id??0)==$pr['id']?'selected':'')?>>
                          <?=htmlspecialchars($pr['nama'])?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <!-- Group Polsek -->
                  <div class="mb-3 d-none" id="groupPolsek"  >
                    <label class="form-label">Polsek</label>
                    <select class="form-select" name="polsek_id" id="polsek_idInp">
                      <option value="">- Pilih Polsek -</option>
                      <!-- opsi diisi AJAX -->
                    </select>
                  </div>
                  <button type="submit" class="btn btn-primary">Update</button>
                  <a href="anggota" class="btn btn-secondary">Batal</a>
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                </form>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="card mb-3">
              <div class="card-header bg-light">
                <h5 class="fs-0 mb-0"><span class="fa fa-info-circle me-2 fs-0"></span> Info</h5>
              </div>
              <div class="card-body">
                <p><strong>Akses POLRES:</strong> Harus memilih Polres terkait. User ini hanya bisa mengelola data di Polres tersebut.</p>
                <p><strong>Akses POLSEK:</strong> Harus memilih Polres dan Polsek terkait. User ini hanya bisa mengelola data di Polsek tersebut.</p>
                
              </div>
            </div>
        </div>
        <?php include_once("footer.php") ?>
      </div>
    </div>
  </main>
    <script src="../vendors/jquery/jquery-3.7.0.min.js"></script>
  <script src="../assets/js/anggota-edit.js"></script>
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
 