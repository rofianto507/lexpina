<?php
session_start();
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://*.tile.openstreetmap.org;");
include("../config/configuration.php");
if(!isset($_SESSION["id"]) || !isset($_SESSION["nama"])) {
  header("Location: ../index");
  exit;
}
 if(empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  $_SESSION["menu"]="lalu-lintas";
  $menu=$_SESSION["menu"];
  $nama=$_SESSION["nama"];
  $id=$_SESSION["id"];
  $username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
  $polres_id=$_SESSION["polres_id"] ?? null;
  $polsek_id=$_SESSION["polsek_id"] ?? null;
  $foto=$_SESSION["foto"];
  $last_login=$_SESSION["last_login"];
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
      ) {
        header("Location: lalu-lintas?status=csrf_failed");
        exit;
    }
  }
    $query_provinsi = $pdo->query("SELECT * FROM provinsis WHERE status=1 limit 1");
  $data_provinsi = $query_provinsi->fetch();
  $nama_provinsi = $data_provinsi["nama"];
  $kode_provinsi = $data_provinsi["kode"];
  $lat_provinsi = $data_provinsi["lat"];
  $lng_provinsi = $data_provinsi["lng"];
  if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['namaLalin'])) {
  $nama = trim($_POST['namaLalin']);
  $desa_id = intval($_POST['desa_id']);
  $foto = "";
  // PROSES upload foto
  if(!empty($_FILES['fotoLalin']['name'])) {
    $finfo = pathinfo($_FILES['fotoLalin']['name']);
    $ext = strtolower($finfo['extension']);
    $allowed = ['jpg','jpeg','png'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    if(in_array($ext, $allowed)) {
      if($_FILES['fotoLalin']['size'] > $maxSize) {       
        header("Location: lalu-lintas?status=file_too_large");
        exit;
      }
      $new_name = uniqid('lalin_').'.'.$ext;
      $upload_dir = __DIR__ . '/../public/upload/lalin';
      if(!is_dir($upload_dir)) mkdir($upload_dir,0777,true);
      $target = $upload_dir . '/' . $new_name;
      if(move_uploaded_file($_FILES['fotoLalin']['tmp_name'], $target)) {
        $foto = $new_name;
      }
    }
  }
  $kategori_id = intval($_POST['kategori_id']);
  $jenis_jalan_id = intval($_POST['jenis_jalan_id']);
  $keterangan = trim($_POST['keterangan'] ?? '');
  $latitude = trim($_POST['latitude'] ?? '');
  $longitude = trim($_POST['longitude'] ?? '');
  $sumber_id = intval($_POST['sumber_id'] ?? 0);
  $penyebab = trim($_POST['penyebab'] ?? '');
  $tindak_lanjut = trim($_POST['tindak_lanjut'] ?? '');
  $penanggungjawab = trim($_POST['penanggungjawab'] ?? '');
  $state=$_POST['state'] ?? 'PROSES';
  $user_id = $id;

  if($nama && $desa_id){
    $stmtInsert = $pdo->prepare("INSERT INTO lalins (nama, keterangan, foto, desa_id, kategori_id, jenis_jalan_id, latitude, longitude, user_id,sumber_id, status,penyebab,tindak_lanjut,penanggungjawab,state)
        VALUES (:nama, :keterangan, :foto, :desa_id, :kategori_id, :jenis_jalan_id, :latitude, :longitude, :user_id, :sumber_id, 1,:penyebab,:tindak_lanjut,:penanggungjawab,:state)");
    $stmtInsert->execute([
      ':nama'=>$nama,
      ':keterangan'=>$keterangan,
      ':foto'=>$foto,
      ':desa_id'=>$desa_id,
      ':kategori_id'=>$kategori_id,
      ':jenis_jalan_id'=>$jenis_jalan_id,
      ':latitude'=>$latitude,
      ':longitude'=>$longitude,
      ':user_id'=>$user_id,
      ':sumber_id'=>$sumber_id,
      ':penyebab'=>$penyebab,
      ':tindak_lanjut'=>$tindak_lanjut,
      ':penanggungjawab'=>$penanggungjawab,
      ':state'=>$state
    ]);
    header("Location: lalu-lintas?status=sukses");
    exit;
  }
}

// Edit/update lalin
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_id'], $_POST['edit_nama'])) {
  $edit_id = intval($_POST['edit_id']);
  $edit_nama = trim($_POST['edit_nama']);
  $edit_desa_id = intval($_POST['edit_desa_id']);
  $edit_kategori_id = intval($_POST['edit_kategori_id']);
  $edit_jenis_jalan_id = intval($_POST['edit_jenis_jalan_id']);
  $edit_keterangan = trim($_POST['edit_keterangan'] ?? '');
  $edit_latitude = trim($_POST['edit_latitude'] ?? '');
  $edit_longitude = trim($_POST['edit_longitude'] ?? '');
  $edit_sumber_id = intval($_POST['edit_sumber_id'] ?? 0);
  $edit_penyebab = trim($_POST['edit_penyebab'] ?? '');
  $edit_tindak_lanjut = trim($_POST['edit_tindak_lanjut'] ?? '');
  $edit_penanggungjawab = trim($_POST['edit_penanggungjawab'] ?? '');
  $edit_state = $_POST['edit_state'] ?? 'PROSES';
  $edit_foto = "";
  // PROSES upload foto
  
  if(!empty($_FILES['edit_fotoLalin']['name'])) {
    $finfo = pathinfo($_FILES['edit_fotoLalin']['name']);
    $ext = strtolower($finfo['extension']);
    $allowed = ['jpg','jpeg','png'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    if(in_array($ext, $allowed)) {
      if($_FILES['edit_fotoLalin']['size'] > $maxSize) {
        header("Location: lalu-lintas?status=file_too_large");
        exit;
      }
      $new_name = uniqid('lalin_').'.'.$ext;
      $upload_dir = __DIR__ . '/../public/upload/lalin';
      if(!is_dir($upload_dir)) mkdir($upload_dir,0777,true);
      $target = $upload_dir . '/' . $new_name;
      if(move_uploaded_file($_FILES['edit_fotoLalin']['tmp_name'], $target)) {
        $edit_foto = $new_name;
        if(!empty($_POST['foto_lama']) && file_exists($upload_dir.'/'.$_POST['foto_lama'])) {
          @unlink($upload_dir.'/'.$_POST['foto_lama']);
        }
      }
    }
  }
  if($edit_id && $edit_nama){
    if(!empty($edit_foto)){
      $stmtUpdate = $pdo->prepare("
      UPDATE lalins SET 
      nama=:nama, 
      keterangan=:keterangan, 
      foto=:foto, 
      desa_id=:desa_id, 
      kategori_id=:kategori_id, 
      jenis_jalan_id=:jenis_jalan_id, 
      latitude=:latitude, 
      longitude=:longitude, 
      sumber_id=:sumber_id, 
      updated_at=NOW(), 
      penyebab=:penyebab, 
      tindak_lanjut=:tindak_lanjut, 
      penanggungjawab=:penanggungjawab, 
      state=:state WHERE id=:id");
      $stmtUpdate->execute([
        ':nama'=>$edit_nama,
        ':keterangan'=>$edit_keterangan,
        ':foto'=>$edit_foto,
        ':desa_id'=>$edit_desa_id,
        ':kategori_id'=>$edit_kategori_id,
        ':jenis_jalan_id'=>$edit_jenis_jalan_id,
        ':latitude'=>$edit_latitude,
        ':longitude'=>$edit_longitude,
        ':sumber_id'=>$edit_sumber_id,
        ':penyebab'=>$edit_penyebab,
        ':tindak_lanjut'=>$edit_tindak_lanjut,
        ':penanggungjawab'=>$edit_penanggungjawab,
        ':state'=>$edit_state,
        ':id'=>$edit_id
      ]);
    } else {
      $stmtUpdate = $pdo->prepare("UPDATE lalins SET nama=:nama, keterangan=:keterangan, desa_id=:desa_id, kategori_id=:kategori_id, jenis_jalan_id=:jenis_jalan_id, latitude=:latitude, 
      longitude=:longitude, sumber_id=:sumber_id, updated_at=NOW(),penyebab=:penyebab, tindak_lanjut=:tindak_lanjut, penanggungjawab=:penanggungjawab, state=:state WHERE id=:id");
      $stmtUpdate->execute([
        ':nama'=>$edit_nama,
        ':keterangan'=>$edit_keterangan,
        ':desa_id'=>$edit_desa_id,
        ':kategori_id'=>$edit_kategori_id,
        ':jenis_jalan_id'=>$edit_jenis_jalan_id,
        ':latitude'=>$edit_latitude,
        ':longitude'=>$edit_longitude,
        ':sumber_id'=>$edit_sumber_id,
        ':penyebab'=>$edit_penyebab,
        ':tindak_lanjut'=>$edit_tindak_lanjut,
        ':penanggungjawab'=>$edit_penanggungjawab,
        ':state'=>$edit_state,
        ':id'=>$edit_id
      ]);
    }
    header("Location: lalu-lintas?status=edit_sukses");
    exit;
  }
}

// Hapus lalin
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['hapus_id'])) {
  $hapus_id = intval($_POST['hapus_id']);
  if($hapus_id) {
    $stmtDel = $pdo->prepare("UPDATE lalins SET status=0 WHERE id=:id");
    $stmtDel->execute([':id'=>$hapus_id]);
    header("Location: lalu-lintas?status=hapus_sukses");
    exit;
  }
}
$kabList = [];
  if($akses == "POLRES") {
    $stmt_kab = $pdo->prepare("SELECT id, nama, kode FROM kabupatens WHERE status=1 AND polres_id=? ORDER BY nama");
    $stmt_kab->execute([$polres_id]);
    $kabList = $stmt_kab->fetchAll(PDO::FETCH_ASSOC);
  } else if($akses == "POLSEK") {
    $stmt_kab = $pdo->prepare("SELECT kabupatens.id, kabupatens.nama, kabupatens.kode FROM kabupatens left join kecamatans on kabupatens.id = kecamatans.kabupaten_id left join polseks on kecamatans.polsek_id = polseks.id WHERE kabupatens.status=1 AND polseks.id=? ORDER BY kabupatens.nama");
    $stmt_kab->execute([$polsek_id]);
    $kabList = $stmt_kab->fetchAll(PDO::FETCH_ASSOC);
  } else {
    $kabList = $pdo->query("SELECT id, nama, kode FROM kabupatens WHERE status=1 ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
  }
$katList = [];
$qKat = $pdo->query("SELECT id, nama FROM lalin_kategoris WHERE status=1 ORDER BY nama ASC");
while($k = $qKat->fetch(PDO::FETCH_ASSOC)) {
    $katList[] = $k;
}
$jenisJalanList = [];
$qJenisJalan = $pdo->query("SELECT id, nama FROM jenis_jalans WHERE status=1 ORDER BY nama ASC");
while($j = $qJenisJalan->fetch(PDO::FETCH_ASSOC)) {
    $jenisJalanList[] = $j;
}
$sumberList = [];
$qSumber = $pdo->query("SELECT id, nama FROM sumbers WHERE status=1 AND tipe='LALU LINTAS' ORDER BY nama ASC");
while($s = $qSumber->fetch(PDO::FETCH_ASSOC)) {
    $sumberList[] = $s;
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lalu Lintas | Peta Digital</title>
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
    <link href="../assets/css/lalin.css" rel="stylesheet">
    <link rel="stylesheet" href="../vendors/leaflet/leaflet.css" />
    <script src="../vendors/leaflet/leaflet.js"></script>
     
  </head>
  <body data-polsek-id="<?php echo htmlspecialchars($_SESSION['polsek_id'] ?? '', ENT_QUOTES); ?>"
        data-lat-provinsi="<?php echo htmlspecialchars($lat_provinsi, ENT_QUOTES); ?>"
        data-lng-provinsi="<?php echo htmlspecialchars($lng_provinsi, ENT_QUOTES); ?>">
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
                <?php elseif(isset($_GET['status']) && $_GET['status']=='file_too_large'): ?>
                <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                  Edit Gagal. Ukuran file foto maksimal 2MB.
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php elseif(isset($_GET['status']) && $_GET['status']=='csrf_failed'): ?>
                <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                  <strong>Akses ditolak!</strong> Token keamanan tidak valid. Silakan muat ulang halaman dan coba lagi.
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
          <div class="row mb-3">
              <div class="col">
                <div class="card bg-100 shadow-none border">
                  <div class="row gx-0 flex-between-center">
                    <div class="col-sm-auto d-flex align-items-center"><img class="ms-n2" src="../assets/img/illustrations/crm-bar-chart.png" alt="" width="90" />
                      <div>
                        <h6 class="text-primary fs--1 mb-0">Selamat Datang, <?php echo $nama; ?></h6>
                        <h4 class="text-primary fw-bold mb-0">Peta Digital Kamtibmas<span class="text-info fw-medium"> Polda Sumsel</span></h4>
                      </div><img class="ms-n4 d-md-none d-lg-block" src="../assets/img/illustrations/crm-line-chart.png" alt="" width="150" />
                    </div>
                    <div class="col-md-auto p-3" id="card-stats"  >
                      <?php if($akses!="POLSEK" ): ?>
                      <div class="row align-items-center mb-3">
                        <div class="col-auto">
                          <select id="filter-polres" class="form-select form-select-sm">
                            <option value="">-- Pilih Polres --</option>
                            <!-- JS/Server: Isi list Polres -->
                          </select>
                        </div>
                        <div class="col-auto">
                          <select id="filter-polsek" class="form-select form-select-sm" disabled>
                            <option value="">-- Pilih Polsek --</option>
                            <!-- OOtomatis diisi setelah pilih Polres -->
                          </select>
                        </div>
                      </div>
                      <?php endif; ?>
                      <div class="row align-items-center">
                        <div class="col-lg-6 border-lg-end border-bottom border-lg-0 pb-3 pb-lg-0">
                          <div class="d-flex flex-between-center mb-3">
                            <div class="d-flex align-items-center">
                              <div class="icon-item icon-item-sm bg-soft-warning shadow-none me-2 bg-soft-warning"><span class="fs--2 fa fa-refresh text-warning"></span></div>
                              <h6 class="mb-0" id="stat-label-1">Label 1</h6>
                            </div>                               
                          </div>
                          <div class="d-flex">
                            <div class="d-flex">
                              <p class="font-sans-serif lh-1 mb-1 fs-4 pe-2" id="stat-value-1">0</p>                    
                            </div>                                 
                          </div>
                        </div>
                        <div class="col-lg-6 border-lg-end border-bottom border-lg-0 pb-3 pb-lg-0">
                          <div class="d-flex flex-between-center mb-3">
                            <div class="d-flex align-items-center">
                              <div class="icon-item icon-item-sm bg-soft-success shadow-none me-2 bg-soft-success"><span class="fs--2 fa fa-check text-success"></span></div>
                              <h6 class="mb-0" id="stat-label-2">Label 2</h6>
                            </div>
                          </div>
                          <div class="d-flex">
                            <div class="d-flex">
                              <p class="font-sans-serif lh-1 mb-1 fs-4 pe-2" id="stat-value-2">0</p>                    
                            </div>                                 
                          </div>
                        </div>
                         
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>      
          <div class="card mb-3">
            <div class="card-header">
              <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                  <h5 class="fs-0 mb-0"><span class="fa fa-road me-2 fs-0"></span> Data Lalu Lintas</h5>
                </div>
                <div class="col-auto ms-auto">
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahLalin" id="btnTambahLalin">
                    Tambah Data
                  </button>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="LalinTable" class="display table table-striped table-bordered table-sm">
                   <thead class="bg-primary text-white">
                    <tr>
                      <th>ID</th>
                      <th>Nama</th>
                      <th>Desa</th>
                      <th>Kecamatan</th>
                      <th>Kabupaten</th>
                      <th>Kategori</th>
                      <th>Jenis Jalan</th>
                      <th>State</th>
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
                      <th></th>
                      <th></th>
                    </tr>
                  </tfoot>
                  <tbody>
                  <?php
                  if($akses == "POLDA" || $akses == "DITLANTAS") {
                    $q = "SELECT l.*, c.nama as kategori_nama, d.nama as desa_nama, k.nama as kecamatan_nama , d.id as desa_id, d.kecamatan_id, k.kabupaten_id, kb.nama as kabupaten_nama, jj.nama as jenis_jalan_nama FROM lalins l
                    LEFT JOIN desas d ON l.desa_id=d.id
                    LEFT JOIN kecamatans k ON d.kecamatan_id = k.id
                    LEFT JOIN kabupatens kb ON k.kabupaten_id = kb.id
                    LEFT JOIN lalin_kategoris c ON l.kategori_id = c.id
                    LEFT JOIN jenis_jalans jj ON l.jenis_jalan_id = jj.id
                    WHERE l.status=1 ORDER BY l.created_at DESC";
                  } elseif($akses == "POLRES") {
                    $q = "SELECT l.*, c.nama as kategori_nama, d.nama as desa_nama, k.nama as kecamatan_nama , d.id as desa_id, d.kecamatan_id, k.kabupaten_id, kb.nama as kabupaten_nama, jj.nama as jenis_jalan_nama FROM lalins l
                    LEFT JOIN desas d ON l.desa_id=d.id
                    LEFT JOIN kecamatans k ON d.kecamatan_id = k.id
                    LEFT JOIN kabupatens kb ON k.kabupaten_id = kb.id
                    LEFT JOIN lalin_kategoris c ON l.kategori_id = c.id
                    LEFT JOIN jenis_jalans jj ON l.jenis_jalan_id = jj.id
                    WHERE l.status=1 AND kb.polres_id = $polres_id ORDER BY l.created_at DESC";
                  }elseif($akses == "POLSEK") {
                    $q = "SELECT l.*, c.nama as kategori_nama, d.nama as desa_nama, k.nama as kecamatan_nama , d.id as desa_id, d.kecamatan_id, k.kabupaten_id, kb.nama as kabupaten_nama, jj.nama as jenis_jalan_nama FROM lalins l
                    LEFT JOIN desas d ON l.desa_id=d.id
                    LEFT JOIN kecamatans k ON d.kecamatan_id = k.id
                    LEFT JOIN kabupatens kb ON k.kabupaten_id = kb.id
                    LEFT JOIN lalin_kategoris c ON l.kategori_id = c.id
                    LEFT JOIN jenis_jalans jj ON l.jenis_jalan_id = jj.id
                    WHERE l.status=1 AND k.polsek_id = $polsek_id ORDER BY l.created_at DESC";
                  }else{
                    $q = "SELECT l.*, c.nama as kategori_nama, d.nama as desa_nama, k.nama as kecamatan_nama , d.id as desa_id, d.kecamatan_id, k.kabupaten_id, kb.nama as kabupaten_nama, jj.nama as jenis_jalan_nama FROM lalins l
                    LEFT JOIN desas d ON l.desa_id=d.id
                    LEFT JOIN kecamatans k ON d.kecamatan_id = k.id
                    LEFT JOIN kabupatens kb ON k.kabupaten_id = kb.id
                    LEFT JOIN lalin_kategoris c ON l.kategori_id = c.id
                    LEFT JOIN jenis_jalans jj ON l.jenis_jalan_id = jj.id
                    WHERE l.status=100 ORDER BY l.created_at DESC";
                  }

                    $stmt = $pdo->query($q);
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      echo "<tr>
                        <td>".intval($row['id'])."</td>
                        <td>".htmlspecialchars($row['nama'], ENT_QUOTES)."</td>
                        <td>".htmlspecialchars($row['desa_nama'], ENT_QUOTES)."</td>
                        <td>".htmlspecialchars($row['kecamatan_nama'], ENT_QUOTES)."</td>
                        <td>".htmlspecialchars($row['kabupaten_nama'], ENT_QUOTES)."</td>
                        <td>".htmlspecialchars($row['kategori_nama'] ?? '', ENT_QUOTES)."</td>
                        <td>".htmlspecialchars($row['jenis_jalan_nama'] ?? '', ENT_QUOTES)."</td>
                        <td><span class='badge bg-".(htmlspecialchars($row['state'], ENT_QUOTES) == 'SELESAI' ? 'success' : 'warning')."'>".htmlspecialchars($row['state'], ENT_QUOTES)."</span></td>
                        <td>
                          <button class='btn btn-sm btn-info btnEditLalin'
                            data-id='".intval($row['id'])."'
                            data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)."'
                            data-desa-id='".intval($row['desa_id'])."' 
                            data-kecamatan-id='".intval($row['kecamatan_id'])."'
                            data-kabupaten-id='".intval($row['kabupaten_id'])."' 
                            data-kategori-id='".intval($row['kategori_id'])."' 
                            data-jenis-jalan-id='".intval($row['jenis_jalan_id'])."' 
                            data-sumber-id='".intval($row['sumber_id'])."'
                            data-latitude='".htmlspecialchars($row['latitude'],ENT_QUOTES)."'
                            data-longitude='".htmlspecialchars($row['longitude'],ENT_QUOTES)."'           
                            data-keterangan='".htmlspecialchars($row['keterangan'],ENT_QUOTES)."'
                            data-foto='".htmlspecialchars($row['foto'],ENT_QUOTES)."'
                            data-state='".htmlspecialchars($row['state'],ENT_QUOTES)."'
                            data-penyebab='".htmlspecialchars($row['penyebab'] ?? '',ENT_QUOTES)."'
                            data-tindak_lanjut='".htmlspecialchars($row['tindak_lanjut'] ?? '',ENT_QUOTES)."'
                            data-penanggungjawab='".htmlspecialchars($row['penanggungjawab'] ?? '',ENT_QUOTES)."'
                            >Edit</button>
                         <button class='btn btn-sm btn-danger btnHapusLalin' data-id='".intval($row['id'])."' data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)."'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
           <div class="modal fade" id="modalTambahLalin" tabindex="-1" aria-labelledby="modalTambahLalinLabel" aria-hidden="true">
              <div class="modal-dialog modal-lg">
                <form method="POST" id="formTambahLalin" enctype="multipart/form-data">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Tambah Lalu Lintas</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="kategori_id" id="kategori_id_lalin" required>
                          <option value="">- Pilih Kategori -</option>
                          <?php
                            $kat = $pdo->query("SELECT id, nama FROM lalin_kategoris WHERE status=1 ORDER BY nama ASC");
                            while($k = $kat->fetch()) {
                              echo "<option value='{$k['id']}'>".htmlspecialchars($k['nama'])."</option>";
                            }
                          ?>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Jenis Jalan</label>
                        <select class="form-select" name="jenis_jalan_id" id="jenis_jalan_id_lalin" >
                          <option value="">- Pilih Jenis Jalan -</option>
                          <?php
                            $kat = $pdo->query("SELECT id, nama FROM jenis_jalans WHERE status=1 ORDER BY nama ASC");
                            while($k = $kat->fetch()) {
                              echo "<option value='{$k['id']}'>".htmlspecialchars($k['nama'])."</option>";
                            }
                          ?>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Nama Lokasi</label>
                        <input type="text" class="form-control" name="namaLalin" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Kabupaten</label>
                        <select class="form-select" name="kabupaten_id" id="kabupaten_id_lalin" required>
                          <option value="">- Pilih Kabupaten -</option>
                           <?php
                            if($akses == "POLRES") {
                              $kabA = $pdo->prepare("SELECT id, nama, kode FROM kabupatens WHERE status=1 AND polres_id=? ORDER BY nama");
                              $kabA->execute([$polres_id]);
                            } else if($akses=="POLSEK") {
                              $kabA = $pdo->prepare("SELECT kabupatens.* FROM kabupatens join polress on kabupatens.polres_id = polress.id join polseks on polress.id = polseks.polres_id WHERE kabupatens.status=1 AND polseks.id=? ORDER BY kabupatens.nama");
                              $kabA->execute([$polsek_id]);
                            } else {
                              $kabA = $pdo->query("SELECT id, nama, kode FROM kabupatens WHERE status=1 ORDER BY nama");
                            }
                            while($kab = $kabA->fetch()) {
                              echo "<option value='{$kab['id']}'>".htmlspecialchars($kab['kode'])." - ".htmlspecialchars($kab['nama'])."</option>";
                            }
                            ?>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Kecamatan</label>
                        <select class="form-select" name="kecamatan_id" id="kecamatan_id_lalin" required disabled>
                          <option value="">- Pilih Kecamatan -</option>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Desa</label>
                        <select class="form-select" name="desa_id" id="desa_id_lalin" required disabled>
                          <option value="">- Pilih Desa -</option>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Tentukan Lokasi di Map</label>
                        <div id="lalinMapTambah"></div>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Latitude</label>
                        <input type="text" class="form-control" name="latitude" id="latitude" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Longitude</label>
                        <input type="text" class="form-control" name="longitude" id="longitude" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Foto (JPG/PNG, opsional)</label>
                        <input type="file" class="form-control" name="fotoLalin" accept=".jpg,.jpeg,.png">
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" name="keterangan"></textarea>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Penyebab</label>
                        <textarea class="form-control" name="penyebab"></textarea>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Tindak Lanjut</label>
                        <textarea class="form-control" name="tindak_lanjut"></textarea>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Penanggung Jawab</label>
                        <input type="text" class="form-control" name="penanggungjawab">
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Sumber Dokumen</label>
                        <select class="form-select" name="sumber_id" required>
                          <option value="">- Pilih Sumber -</option>
                          <?php foreach($sumberList as $s): ?>
                            <option value="<?=$s['id']?>"><?=htmlspecialchars($s['nama'])?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="mb-3">
                          <label class="form-label">State</label>
                          <select class="form-select form-select-sm" name="state" required>
                            <option value="PROSES" selected>PROSES</option>
                            <option value="SELESAI">SELESAI</option>                     
                          </select>
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
          <!-- Modal Edit Lokasi -->
          <div class="modal fade" id="modalEditLalin" tabindex="-1" aria-labelledby="modalEditLalinLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <form method="POST" id="formEditLalin" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                 <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Edit Lalu Lintas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label>Kategori</label>
                      <select class="form-select" name="edit_kategori_id" id="edit_kategori_id" required>
                        <option value="">- Pilih Kategori -</option>
                        <?php foreach($katList as $kat): ?>
                           <option value="<?= $kat['id'] ?>"><?= htmlspecialchars($kat['nama']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label>Jenis Jalan</label>
                      <select class="form-select" name="edit_jenis_jalan_id" id="edit_jenis_jalan_id">
                        <option value="">- Pilih Jenis Jalan -</option>
                        <?php foreach($jenisJalanList as $j): ?>
                           <option value="<?= $j['id'] ?>"><?= htmlspecialchars($j['nama']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Nama Lokasi</label>
                      <input type="text" class="form-control" name="edit_nama" id="edit_nama" required>
                    </div>
                    <div class="mb-3">
                      <label>Kabupaten</label>
                      <select class="form-select" name="edit_kabupaten_id" id="edit_kabupaten_id" required>
                        <option value="">- Pilih Kabupaten -</option>
                        <?php foreach($kabList as $kab): ?>
                           <option value="<?= $kab['id'] ?>"><?= htmlspecialchars($kab['nama']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label>Kecamatan</label>
                      <select class="form-select" name="edit_kecamatan_id" id="edit_kecamatan_id" required>
                        <option value="">- Pilih Kecamatan -</option>
                        <?php foreach($kecList as $kec): ?>
                          <option value="<?= $kec['id'] ?>" <?= $data['kecamatan_id']==$kec['id']?'selected':'' ?>>
                            <?= htmlspecialchars($kec['nama']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label>Desa</label>
                      <select class="form-select" name="edit_desa_id" id="edit_desa_id" required>
                        <option value="">- Pilih Desa -</option>
                        <?php foreach($desaList as $d): ?>
                          <option value="<?= $d['id'] ?>" <?= $data['desa_id']==$d['id']?'selected':'' ?>>
                            <?= htmlspecialchars($d['nama']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Tentukan Lokasi di Map</label>
                      <div id="lalinMapEdit"></div>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Latitude</label>
                      <input type="text" class="form-control" name="edit_latitude" id="edit_latitude" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Longitude</label>
                      <input type="text" class="form-control" name="edit_longitude" id="edit_longitude" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Foto (JPG/PNG, opsional)</label>
                      <input type="file" class="form-control" name="edit_fotoLalin" accept=".jpg,.jpeg,.png">
                    </div>
                    <div class="mb-2" id="previewFotoEditWrapper">
                      <label class="form-label">Foto Saat Ini:</label><br>
                      <img src="" id="previewFotoEdit" class="img-thumbnail">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Penyebab</label>
                      <textarea class="form-control" name="edit_penyebab" id="edit_penyebab" rows="3"></textarea>
                    </div> 
                    <div class="mb-3">
                      <label class="form-label">Tindak Lanjut</label>
                      <textarea class="form-control" name="edit_tindak_lanjut" id="edit_tindak_lanjut" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Penanggung Jawab</label>
                      <input type="text" class="form-control" name="edit_penanggungjawab" id="edit_penanggungjawab">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Keterangan</label>
                      <textarea class="form-control" name="edit_keterangan" id="edit_keterangan" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Sumber Dokumen</label>
                      <select class="form-select" name="edit_sumber_id" id="edit_sumber_id" required>
                        <option value="">- Pilih Sumber -</option>
                        <?php foreach($sumberList as $s): ?>
                          <option value="<?=$s['id']?>"><?=htmlspecialchars($s['nama'])?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">State</label>
                      <select class="form-select form-select-sm" name="edit_state" id="edit_state" required>
                        <option value="PROSES">PROSES</option>
                        <option value="SELESAI">SELESAI</option>
                      </select>
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

           <!-- Modal Hapus Lalin -->
          <div class="modal fade" id="modalHapusLalin" tabindex="-1" aria-labelledby="modalHapusLalinLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formHapusLalin">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="hapus_id" id="hapus_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalHapusLalinLabel">Konfirmasi Hapus</h5>
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
    <script src="../vendors/popper/popper.min.js"></script>
    <script src="../vendors/bootstrap/bootstrap.min.js"></script>
    <script src="../assets/js/lalu-lintas.js"></script>
    <script src="../vendors/anchorjs/anchor.min.js"></script>
    <script src="../vendors/is/is.min.js"></script>
    <script src="../vendors/prism/prism.js"></script>
    
    <script src="../vendors/lodash/lodash.min.js"></script>
    <script src="../vendors/list.js/list.min.js"></script>
    <script src="../assets/js/theme.js"></script>
  </body>
</html>
 