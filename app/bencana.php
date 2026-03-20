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
  $_SESSION["menu"]="bencana";
  $menu=$_SESSION["menu"];
  $nama=$_SESSION["nama"];
  $id=$_SESSION["id"];
  $username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
  $polres_id=$_SESSION["polres_id"]??0;
  $polsek_id=$_SESSION["polsek_id"]??0;
  $foto=$_SESSION["foto"];
  $last_login=$_SESSION["last_login"];
    $query_provinsi = $pdo->query("SELECT * FROM provinsis WHERE status=1 limit 1");
  $data_provinsi = $query_provinsi->fetch();
  $nama_provinsi = $data_provinsi["nama"];
  $kode_provinsi = $data_provinsi["kode"];
  $lat_provinsi = $data_provinsi["lat"];
  $lng_provinsi = $data_provinsi["lng"];
  if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['namaBencana'])) {
  $nama = trim($_POST['namaBencana']);
  $desa_id = intval($_POST['desa_id']);
  $foto = "";
  // Proses upload foto
  if(!empty($_FILES['fotoBencana']['name'])) {
    $finfo = pathinfo($_FILES['fotoBencana']['name']);
    $ext = strtolower($finfo['extension']);
    $allowed = ['jpg','jpeg','png'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    if(in_array($ext, $allowed)) {
      if($_FILES['fotoBencana']['size'] > $maxSize) {
         header("Location: lalu-lintas?status=file_too_large");
        exit;
      }
      $new_name = uniqid('bencana_').'.'.$ext;
      $upload_dir = __DIR__ . '/../public/upload/bencana';
      if(!is_dir($upload_dir)) mkdir($upload_dir,0777,true);
      $target = $upload_dir . '/' . $new_name;
      if(move_uploaded_file($_FILES['fotoBencana']['tmp_name'], $target)) {
        $foto = $new_name;
      }
    }
  }
  $kategori_id = intval($_POST['kategori_id']);
  $penyebab = trim($_POST['penyebab'] ?? '');
  $tindaklanjut = trim($_POST['tindaklanjut'] ?? '');
  $latitude = trim($_POST['latitude'] ?? '');
  $longitude = trim($_POST['longitude'] ?? '');
  $sumber_id = intval($_POST['sumber_id'] ?? 0);
  $user_id = $id;

  if($nama && $desa_id){
    $stmtInsert = $pdo->prepare("INSERT INTO bencanas (nama, penyebab, tindaklanjut, foto, desa_id, kategori_id, latitude, longitude, user_id,sumber_id, status)
        VALUES (:nama, :penyebab, :tindaklanjut, :foto, :desa_id, :kategori_id, :latitude, :longitude, :user_id, :sumber_id, 1)");
    $stmtInsert->execute([
      ':nama'=>$nama,
      ':penyebab'=>$penyebab,
      ':tindaklanjut'=>$tindaklanjut,
      ':foto'=>$foto,
      ':desa_id'=>$desa_id,
      ':kategori_id'=>$kategori_id,
      ':latitude'=>$latitude,
      ':longitude'=>$longitude,
      ':user_id'=>$user_id,
      ':sumber_id'=>$sumber_id
    ]);
    header("Location: bencana?status=sukses");
    exit;
  }
}

// Edit/update bencana
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_id'], $_POST['edit_nama'])) {
  $edit_id = intval($_POST['edit_id']);
  $edit_nama = trim($_POST['edit_nama']);
  $edit_desa_id = intval($_POST['edit_desa_id']);
  $edit_kategori_id = intval($_POST['edit_kategori_id']);
  $edit_penyebab = trim($_POST['edit_penyebab'] ?? '');
  $edit_tindaklanjut = trim($_POST['edit_tindaklanjut'] ?? '');
  $edit_latitude = trim($_POST['edit_latitude'] ?? '');
  $edit_longitude = trim($_POST['edit_longitude'] ?? '');
  $edit_sumber_id = intval($_POST['edit_sumber_id'] ?? 0);
  $edit_foto = "";
  // Proses upload foto

  if(!empty($_FILES['edit_fotoBencana']['name'])) {
    $finfo = pathinfo($_FILES['edit_fotoBencana']['name']);
    $ext = strtolower($finfo['extension']);
    $allowed = ['jpg','jpeg','png'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    if(in_array($ext, $allowed)) {
      if($_FILES['edit_fotoBencana']['size'] > $maxSize) {
         header("Location: lalu-lintas?status=file_too_large");
        exit;
      }
      $new_name = uniqid('bencana_').'.'.$ext;
      $upload_dir = __DIR__ . '/../public/upload/bencana';
      if(!is_dir($upload_dir)) mkdir($upload_dir,0777,true);
      $target = $upload_dir . '/' . $new_name;
      if(move_uploaded_file($_FILES['edit_fotoBencana']['tmp_name'], $target)) {
        $edit_foto = $new_name;
        if(!empty($_POST['foto_lama']) && file_exists($upload_dir.'/'.$_POST['foto_lama'])) {
          @unlink($upload_dir.'/'.$_POST['foto_lama']);
        }
      }
    }
  }
  if($edit_id && $edit_nama){
    if(!empty($edit_foto)){
      $stmtUpdate = $pdo->prepare("UPDATE bencanas SET nama=:nama, penyebab=:penyebab, tindaklanjut=:tindaklanjut, foto=:foto, desa_id=:desa_id, kategori_id=:kategori_id, latitude=:latitude, longitude=:longitude, sumber_id=:sumber_id, updated_at=NOW() WHERE id=:id");
      $stmtUpdate->execute([
        ':nama'=>$edit_nama,
        ':penyebab'=>$edit_penyebab,
        ':tindaklanjut'=>$edit_tindaklanjut,
        ':foto'=>$edit_foto,
        ':desa_id'=>$edit_desa_id,
        ':kategori_id'=>$edit_kategori_id,
        ':latitude'=>$edit_latitude,
        ':longitude'=>$edit_longitude,
        ':sumber_id'=>$edit_sumber_id,
        ':id'=>$edit_id
      ]);
    } else {
      $stmtUpdate = $pdo->prepare("UPDATE bencanas SET nama=:nama, penyebab=:penyebab, tindaklanjut=:tindaklanjut, desa_id=:desa_id, kategori_id=:kategori_id, latitude=:latitude, longitude=:longitude, sumber_id=:sumber_id, updated_at=NOW() WHERE id=:id");
      $stmtUpdate->execute([
        ':nama'=>$edit_nama,
        ':penyebab'=>$edit_penyebab,
        ':tindaklanjut'=>$edit_tindaklanjut,
        ':desa_id'=>$edit_desa_id,
        ':kategori_id'=>$edit_kategori_id,
        ':latitude'=>$edit_latitude,
        ':longitude'=>$edit_longitude,
        ':sumber_id'=>$edit_sumber_id,
        ':id'=>$edit_id
      ]);
    }
    header("Location: bencana?status=edit_sukses");
    exit;
  }
}

// Hapus bencana
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['hapus_id'])) {
  $hapus_id = intval($_POST['hapus_id']);
  if($hapus_id) {
    $stmtDel = $pdo->prepare("DELETE FROM bencanas WHERE id=:id");
    $stmtDel->execute([':id'=>$hapus_id]);
    header("Location: bencana?status=hapus_sukses");
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
$qKat = $pdo->query("SELECT id, nama FROM bencana_kategoris WHERE status=1 ORDER BY nama ASC");
while($k = $qKat->fetch(PDO::FETCH_ASSOC)) {
    $katList[] = $k;
}
$sumberList = [];
$qSumber = $pdo->query("SELECT id, nama FROM sumbers WHERE status=1 AND tipe='BENCANA' ORDER BY nama ASC");
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
    <title>Bencana | Peta Digital</title>
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
    <link href="../assets/css/bencana.css" rel="stylesheet">
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
                  Ukuran file terlalu besar. Maksimal 2MB.
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

          <div class="card mb-3">
            <div class="card-header">
              <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                  <h5 class="fs-0 mb-0"><span class="fas fa-fire-alt me-2 fs-0"></span> Bencana</h5>
                </div>
                <div class="col-auto ms-auto">
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahBencana">
                    Tambah Data
                  </button>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="BencanaTable" class="display table table-striped table-bordered table-sm" >
                   <thead class="bg-primary text-white">
                    <tr>
                      <th>ID</th>
                      <th>Nama</th>
                      <th>Desa</th>
                      <th>Kecamatan</th>
                      <th>Kabupaten</th>
                      <th>Kategori</th>
                      <th>Penyebab</th>                    
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
                  if($akses=="POLDA") {
                      
                    $q = "SELECT l.*, c.nama as kategori_nama, d.nama as desa_nama, k.nama as kecamatan_nama, b.nama as kabupaten_nama, d.id as desa_id, 
                    d.kecamatan_id, k.kabupaten_id,s.nama as sumber_nama FROM bencanas l 
                    LEFT JOIN desas d ON l.desa_id=d.id
                    LEFT JOIN kecamatans k ON d.kecamatan_id = k.id
                    LEFT JOIN kabupatens b ON k.kabupaten_id = b.id
                    LEFT JOIN bencana_kategoris c ON l.kategori_id = c.id
                    LEFT JOIN sumbers s ON l.sumber_id = s.id
                    WHERE l.status=1 ORDER BY l.created_at DESC";
                    $stmt = $pdo->query($q);
                    $stmt->execute();
                  }else if($akses=="POLRES"){
                    $q = "SELECT l.*, c.nama as kategori_nama, d.nama as desa_nama, k.nama as kecamatan_nama, b.nama as kabupaten_nama, d.id as desa_id, 
                    d.kecamatan_id, k.kabupaten_id,s.nama as sumber_nama FROM bencanas l 
                    LEFT JOIN desas d ON l.desa_id=d.id
                    LEFT JOIN kecamatans k ON d.kecamatan_id = k.id
                    LEFT JOIN kabupatens b ON k.kabupaten_id = b.id
                    LEFT JOIN bencana_kategoris c ON l.kategori_id = c.id
                    LEFT JOIN sumbers s ON l.sumber_id = s.id
                    WHERE l.status=1 AND b.polres_id=? ORDER BY l.created_at DESC";
                    $stmt = $pdo->prepare($q);
                    $stmt->execute([$polres_id]);
                  }else if($akses=="POLSEK"){
                    $q = "SELECT l.*, c.nama as kategori_nama, d.nama as desa_nama, k.nama as kecamatan_nama, b.nama as kabupaten_nama, d.id as desa_id,
                    d.kecamatan_id, k.kabupaten_id,s.nama as sumber_nama FROM bencanas l 
                    LEFT JOIN desas d ON l.desa_id=d.id
                    LEFT JOIN kecamatans k ON d.kecamatan_id = k.id
                    LEFT JOIN kabupatens b ON k.kabupaten_id = b.id
                    LEFT JOIN bencana_kategoris c ON l.kategori_id = c.id
                    LEFT JOIN sumbers s ON l.sumber_id = s.id
                    WHERE l.status=1 AND k.polsek_id=? ORDER BY l.created_at DESC";
                    $stmt = $pdo->prepare($q);
                    $stmt->execute([$polsek_id]);
                  }
                  while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>
                      <td>".$row['id']."</td>
                      <td>".$row['nama']."</td>
                      <td>".$row['desa_nama']."</td>
                        <td>".$row['kecamatan_nama']."</td>
                        <td>".$row['kabupaten_nama']."</td>
                        <td>".$row['kategori_nama']."</td>
                         <td>".(mb_strlen($row['penyebab']??'') > 100 ? mb_substr($row['penyebab']??'', 0, 100).'...' : $row['penyebab'])."</td>
                         
                        <td>
                          <button class='btn btn-sm btn-info btnEditBencana'
                            data-id='{$row['id']}'
                            data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)."'
                            data-desa-id='{$row['desa_id']}' 
                            data-kecamatan-id='{$row['kecamatan_id']}'
                            data-kabupaten-id='{$row['kabupaten_id']}' 
                            data-kategori-id='{$row['kategori_id']}' 
                            data-latitude='".htmlspecialchars($row['latitude'],ENT_QUOTES)."'
                            data-longitude='".htmlspecialchars($row['longitude'],ENT_QUOTES)."' 
                            data-sumber-id='{$row['sumber_id']}'              
                            data-penyebab='".htmlspecialchars($row['penyebab']??'',ENT_QUOTES)."'
                            data-tindaklanjut='".htmlspecialchars($row['tindaklanjut']??'',ENT_QUOTES)."'
                            data-foto='".htmlspecialchars($row['foto']??'',ENT_QUOTES)."'
                            >Edit</button>
                         <button class='btn btn-sm btn-danger btnHapusBencana' data-id='{$row['id']}' data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)."'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
           <div class="modal fade" id="modalTambahBencana" tabindex="-1" aria-labelledby="modalTambahBencanaLabel" aria-hidden="true">
              <div class="modal-dialog modal-lg">
                <form method="POST" id="formTambahBencana" enctype="multipart/form-data">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Tambah Bencana</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="kategori_id" id="kategori_id_bencana" required>
                          <option value="">- Pilih Kategori -</option>
                          <?php
                            $kat = $pdo->query("SELECT id, nama FROM bencana_kategoris WHERE status=1 ORDER BY nama ASC");
                            while($k = $kat->fetch()) {
                              echo "<option value='{$k['id']}'>".htmlspecialchars($k['nama'])."</option>";
                            }
                          ?>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" class="form-control" name="namaBencana" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Kabupaten</label>
                        <select class="form-select" name="kabupaten_id" id="kabupaten_id_bencana" required>
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
                        <select class="form-select" name="kecamatan_id" id="kecamatan_id_bencana" required disabled>
                          <option value="">- Pilih Kecamatan -</option>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Desa</label>
                        <select class="form-select" name="desa_id" id="desa_id_bencana" required disabled>
                          <option value="">- Pilih Desa -</option>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Tentukan Lokasi di Map</label>
                        <div id="bencanaMapTambah"  ></div>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Latitude</label>
                        <input type="text" class="form-control" name="latitude" readonly>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Longitude</label>
                        <input type="text" class="form-control" name="longitude" readonly>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Foto (JPG/PNG, opsional)</label>
                        <input type="file" class="form-control" name="fotoBencana" accept=".jpg,.jpeg,.png">
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Penyebab</label>
                        <textarea class="form-control" name="penyebab" rows="5"></textarea>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Tindak Lanjut</label>
                        <textarea class="form-control" name="tindaklanjut" rows="5"></textarea>
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
          <div class="modal fade" id="modalEditBencana" tabindex="-1" aria-labelledby="modalEditBencanaLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <form method="POST" id="formEditBencana" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                 <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Edit Bencana</h5>
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
                      <label class="form-label">Nama</label>
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
                      <div id="bencanaMapEdit" ></div>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Latitude</label>
                      <input type="text" class="form-control" name="edit_latitude" id="edit_latitude" readonly>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Longitude</label>
                      <input type="text" class="form-control" name="edit_longitude" id="edit_longitude" readonly>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Foto (JPG/PNG, opsional)</label>
                      <input type="file" class="form-control" name="edit_fotoBencana" accept=".jpg,.jpeg,.png">
                    </div>
                    <div class="mb-2" id="previewFotoEditWrapper" >
                      <label class="form-label">Foto Saat Ini:</label><br>
                      <img src="" id="previewFotoEdit" class="img-thumbnail" >
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Penyebab</label>
                      <textarea class="form-control" name="edit_penyebab" id="edit_penyebab" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Tindak Lanjut</label>
                      <textarea class="form-control" name="edit_tindaklanjut" id="edit_tindaklanjut" rows="5"></textarea>
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
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                  </div>
                </div>
              </form>
            </div>
          </div>

           <!-- Modal Hapus Bencana -->
          <div class="modal fade" id="modalHapusBencana" tabindex="-1" aria-labelledby="modalHapusBencanaLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formHapusBencana">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="hapus_id" id="hapus_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalHapusBencanaLabel">Konfirmasi Hapus</h5>
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
  
    <script src="../vendors/jquery/jquery-3.7.0.min.js"></script>
    <script src="../vendors/datatables/datatables.min.js"></script>
     
    <script src="../vendors/popper/popper.min.js"></script>
    <script src="../vendors/bootstrap/bootstrap.min.js"></script>
    <script src="../assets/js/bencana.js"></script>
    <script src="../vendors/anchorjs/anchor.min.js"></script>
    <script src="../vendors/is/is.min.js"></script>
    <script src="../vendors/prism/prism.js"></script>
 
    <script src="../vendors/lodash/lodash.min.js"></script>
    <script src="../vendors/list.js/list.min.js"></script>
    <script src="../assets/js/theme.js"></script>
  </body>
</html>
 