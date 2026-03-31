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
  $_SESSION["menu"]="lokasi";
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
        header("Location: lokasi?status=csrf_failed");
        exit;
    }
  }
    $query_provinsi = $pdo->query("SELECT * FROM provinsis WHERE status=1 limit 1");
  $data_provinsi = $query_provinsi->fetch();
  $nama_provinsi = $data_provinsi["nama"];
  $kode_provinsi = $data_provinsi["kode"];
  $lat_provinsi = $data_provinsi["lat"];
  $lng_provinsi = $data_provinsi["lng"];
  if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kategori_id'], $_POST['namaLokasi'])) {
    $kategori_id = intval($_POST['kategori_id']);
    $namaLokasi = trim($_POST['namaLokasi']);
      $desa_id = intval($_POST['desa_id']);
    $alamat = trim($_POST['alamat'] ?? '');
    $hp = trim($_POST['hp'] ?? '');
    $foto = ""; 
    if(!empty($_FILES['fotoLokasi']['name'])) {
      $finfo = pathinfo($_FILES['fotoLokasi']['name']);
      $ext = strtolower($finfo['extension']);
      $allowed = ['jpg','jpeg','png'];
      if(in_array($ext, $allowed)) {
        $new_name = uniqid('lokasi_').'.'.$ext;
        $upload_dir = __DIR__ . '/../public/upload/lokasi';
        if(!is_dir($upload_dir)) mkdir($upload_dir,0777,true);
        $target = $upload_dir . '/' . $new_name;
        if(move_uploaded_file($_FILES['fotoLokasi']['tmp_name'], $target)) {
          $foto = $new_name;
        }
      }
    }
    $keterangan = trim($_POST['keterangan'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
 
    $user_id = $id_user;

    if($kategori_id && $namaLokasi){
      $stmtInsert = $pdo->prepare("INSERT INTO lokasis (desa_id, kategori_id, nama, alamat, hp, foto, keterangan, latitude, longitude, user_id, status) VALUES (:desa_id, :kategori_id, :nama, :alamat, :hp, :foto, :keterangan, :latitude, :longitude, :user_id, 1)");
      $stmtInsert->execute([
        ':desa_id'=>$desa_id,
        ':kategori_id'=>$kategori_id,
        ':nama'=>$namaLokasi,
        ':alamat'=>$alamat,
        ':hp'=>$hp,
        ':foto'=>$foto,
        ':keterangan'=>$keterangan,
        ':latitude'=>$latitude,
        ':longitude'=>$longitude,
        ':user_id'=>$user_id,
   
      ]);
      header("Location: lokasi?status=sukses");
      exit;
    }
  }

  // Edit/update lokasi
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_id'], $_POST['edit_nama'])) {
    $edit_id = intval($_POST['edit_id']);
    $edit_nama = trim($_POST['edit_nama']);
    $edit_kategori_id = intval($_POST['edit_kategori_id']);
    $edit_alamat = trim($_POST['edit_alamat'] ?? '');
    $edit_hp = trim($_POST['edit_hp'] ?? '');
    $edit_keterangan = trim($_POST['edit_keterangan'] ?? '');
    $edit_latitude = trim($_POST['edit_latitude'] ?? '');
    $edit_longitude = trim($_POST['edit_longitude'] ?? '');
    $edit_desa_id = intval($_POST['edit_desa_id']);
 
    $edit_foto = "";
    if(!empty($_FILES['edit_fotoLokasi']['name'])) {
      $finfo = pathinfo($_FILES['edit_fotoLokasi']['name']);
      $ext = strtolower($finfo['extension']);
      $allowed = ['jpg','jpeg','png'];
      if(in_array($ext, $allowed)) {
        $new_name = uniqid('lokasi_').'.'.$ext;
        $upload_dir = __DIR__ . '/../public/upload/lokasi';
        if(!is_dir($upload_dir)) mkdir($upload_dir,0777,true);
        $target = $upload_dir . '/' . $new_name;
        if(move_uploaded_file($_FILES['edit_fotoLokasi']['tmp_name'], $target)) {
          $edit_foto = $new_name;
          // hapus foto lama jika ada
          if(!empty($_POST['foto_lama']) && file_exists($upload_dir.'/'.$_POST['foto_lama'])) {
            @unlink($upload_dir.'/'.$_POST['foto_lama']);
          }
        }
      }
    }

    if($edit_id && $edit_nama){
      if(!empty($edit_foto)){
        $stmtUpdate = $pdo->prepare("UPDATE lokasis SET desa_id=:desa_id, kategori_id=:kategori_id, nama=:nama, alamat=:alamat, hp=:hp, keterangan=:keterangan, latitude=:latitude, longitude=:longitude, foto=:foto, updated_at=NOW() WHERE id=:id");
        $stmtUpdate->execute([
          ':desa_id'=>$edit_desa_id,
          ':kategori_id'=>$edit_kategori_id,
          ':nama'=>$edit_nama,
          ':alamat'=>$edit_alamat,
          ':hp'=>$edit_hp,
          ':keterangan'=>$edit_keterangan,
          ':latitude'=>$edit_latitude,
          ':longitude'=>$edit_longitude,
          ':foto'=>$edit_foto,
          ':id'=>$edit_id
        ]);
      } else {
        // Jika tidak upload foto baru
        $stmtUpdate = $pdo->prepare("UPDATE lokasis SET desa_id=:desa_id, kategori_id=:kategori_id, nama=:nama, alamat=:alamat, hp=:hp, keterangan=:keterangan, latitude=:latitude, longitude=:longitude, updated_at=NOW() WHERE id=:id");
        $stmtUpdate->execute([
          ':desa_id'=>$edit_desa_id,
          ':kategori_id'=>$edit_kategori_id,
          ':nama'=>$edit_nama,
          ':alamat'=>$edit_alamat,
          ':hp'=>$edit_hp,
          ':keterangan'=>$edit_keterangan,
          ':latitude'=>$edit_latitude,
          ':longitude'=>$edit_longitude,
          ':id'=>$edit_id
        ]);
      }
      header("Location: lokasi?status=edit_sukses");
      exit;
    }
  }

  // Hapus lokasi
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['hapus_id'])) {
    $hapus_id = intval($_POST['hapus_id']);
    if($hapus_id) {
      $stmtDel = $pdo->prepare("DELETE FROM lokasis WHERE id=:id");
      $stmtDel->execute([':id'=>$hapus_id]);
      header("Location: lokasi?status=hapus_sukses");
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
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lokasi Penting | Peta Digital</title>
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
    <link href="../vendors/prism/prism-okaidia.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="../vendors/datatables/datatables.min.css"/>
    <link rel="stylesheet" type="text/css" href="../assets/icon/font-awesome/css/font-awesome.min.css">
    <link href="../vendors/overlayscrollbars/OverlayScrollbars.min.css" rel="stylesheet">
    <link href="../assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
    <link href="../assets/css/theme.min.css" rel="stylesheet" id="style-default">
    <link href="../assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
    <link href="../assets/css/user.min.css" rel="stylesheet" id="user-style-default">
    <link href="../assets/css/lokasi.css" rel="stylesheet">
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
                  <h5 class="fs-0 mb-0"><span class="fa fa-map-signs me-2 fs-0"></span> Lokasi Penting</h5>
                </div>
                <div class="col-auto ms-auto">
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahLokasi">
                    Tambah Data
                  </button>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="LokasiTable" class="display table table-striped table-bordered table-sm"  >
                  <thead class="bg-primary text-white">
                    <tr>
                      <th>ID</th>
                      
                      <th>Nama</th>
                      <th>Alamat</th>
                       <th>Desa</th>
                      <th>Kecamatan</th>
                      <th>Kabupaten</th>
                      <th>Kategori</th>
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
                  <tbody>
                  <?php
                    $q = "SELECT l.*, c.nama as kategori_nama, c.warna as kategori_warna,c.icon as kategori_icon, d.nama as desa_nama, k.nama as kecamatan_nama, kb.nama as kabupaten_nama
                    , d.id as desa_id, d.kecamatan_id, k.kabupaten_id FROM lokasis l 
                    LEFT JOIN lokasi_kategoris c ON l.kategori_id=c.id
                    LEFT JOIN desas d ON l.desa_id=d.id
                    LEFT JOIN kecamatans k ON d.kecamatan_id = k.id
                    LEFT JOIN kabupatens kb ON k.kabupaten_id = kb.id
                    WHERE l.status=1 ORDER BY l.created_at DESC";
                    $stmt = $pdo->query($q);
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      echo "<tr>
                        <td>".$row['id']."</td>
                        <td>".$row['nama']."</td>
                         <td>".(mb_strlen($row['alamat']) > 100 ? mb_substr($row['alamat'], 0, 100).'...' : $row['alamat'])."</td>
                        <td>".$row['desa_nama']."</td>
                        <td>".$row['kecamatan_nama']."</td>
                        <td>".$row['kabupaten_nama']."</td>
                        <td>".$row['kategori_nama']."</td>
                         
                     
                        <td>
                          <button class='btn btn-sm btn-info btnEditLokasi'
                            data-id='{$row['id']}'
                            data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)."'
                            data-desa-id='".intval($row['desa_id'] ?? 0)."'
                            data-kecamatan-id='".intval($row['kecamatan_id'] ?? 0)."'
                            data-kabupaten-id='".intval($row['kabupaten_id'] ?? 0)."'
                            data-kategori-id='{$row['kategori_id']}'
                            data-kategori-warna='{$row['kategori_warna']}'
                            data-kategori-icon='{$row['kategori_icon']}'
                            data-alamat='".htmlspecialchars($row['alamat'],ENT_QUOTES)."'
                            data-hp='".htmlspecialchars($row['hp'],ENT_QUOTES)."'
                            data-keterangan='".htmlspecialchars($row['keterangan'],ENT_QUOTES)."'
                            data-latitude='".htmlspecialchars($row['latitude'],ENT_QUOTES)."'
                            data-longitude='".htmlspecialchars($row['longitude'],ENT_QUOTES)."'
                            data-foto='".htmlspecialchars($row['foto'],ENT_QUOTES)."'
                          
                            >Edit</button>
                          <button class='btn btn-sm btn-danger btnHapusLokasi' data-id='{$row['id']}' data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)."'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
           <div class="modal fade" id="modalTambahLokasi" tabindex="-1" aria-labelledby="modalTambahLokasiLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <form method="POST" id="formTambahLokasi" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahLokasiLabel">Tambah Lokasi Penting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label for="kategori_id" class="form-label">Kategori</label>
                      <select class="form-select form-select-sm" name="kategori_id" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php
                          $stmtKat = $pdo->query("SELECT id, nama FROM lokasi_kategoris WHERE status=1 ORDER BY nama ASC");
                          while($rowKat = $stmtKat->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='".$rowKat['id']."'>".$rowKat['nama']."</option>";
                          }
                        ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="namaLokasi" class="form-label">Nama</label>
                      <input type="text" class="form-control form-control-sm" id="namaLokasi" name="namaLokasi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kabupaten</label>
                        <select class="form-select" name="kabupaten_id" id="kabupaten_id_lokasi" required>
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
                        <select class="form-select" name="kecamatan_id" id="kecamatan_id_lokasi" required disabled>
                          <option value="">- Pilih Kecamatan -</option>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Desa</label>
                        <select class="form-select" name="desa_id" id="desa_id_lokasi" required disabled>
                          <option value="">- Pilih Desa -</option>
                        </select>
                      </div>
                    <div class="mb-3">
                      <label class="form-label">Alamat Lengkap</label>
                      <input type="text" class="form-control form-control-sm" name="alamat">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">HP/Kontak</label>
                      <input type="text" class="form-control form-control-sm" name="hp">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Tentukan Lokasi di Map</label>
                      <div id="lokasiMapTambah" ></div>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Latitude</label>
                      <input type="text" class="form-control form-control-sm" name="latitude" readonly>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Longitude</label>
                      <input type="text" class="form-control form-control-sm" name="longitude" readonly>
                    </div>
                    <div class="mb-3">
                    <label class="form-label">Foto (opsional, JPG/PNG)</label>
                    <input type="file" class="form-control form-control-sm" name="fotoLokasi" accept=".jpg,.jpeg,.png">
                  </div>
                    <div class="mb-3">
                      <label class="form-label">Keterangan</label>
                      <textarea class="form-control form-control-sm" name="keterangan" rows="5"></textarea>
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
          <div class="modal fade" id="modalEditLokasi" tabindex="-1" aria-labelledby="modalEditLokasiLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <form method="POST" id="formEditLokasi" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalEditLokasiLabel">Edit Lokasi Penting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label class="form-label">Kategori</label>
                      <select class="form-select form-select-sm" name="edit_kategori_id" id="edit_kategori_id" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php
                          $stmtKat = $pdo->query("SELECT id, nama FROM lokasi_kategoris WHERE status=1 ORDER BY nama ASC");
                          while($rowKat = $stmtKat->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='".$rowKat['id']."'>".$rowKat['nama']."</option>";
                          }
                        ?>
                      </select>
                      <input type="hidden" id="edit_kategori_warna"> <!-- untuk menyimpan warna kategori -->
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Nama Lokasi</label>
                      <input type="text" class="form-control form-control-sm" id="edit_nama" name="edit_nama" required>
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
                      <label class="form-label">Alamat Lengkap</label>
                      <input type="text" class="form-control form-control-sm" id="edit_alamat" name="edit_alamat">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">HP/Kontak</label>
                      <input type="text" class="form-control form-control-sm" id="edit_hp" name="edit_hp">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Tentukan Lokasi di Map</label>
                      <div id="lokasiMapEdit" ></div>
                      
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Latitude</label>
                      <input type="text" class="form-control form-control-sm" id="edit_latitude" name="edit_latitude" readonly>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Longitude</label>
                      <input type="text" class="form-control form-control-sm" id="edit_longitude" name="edit_longitude" readonly>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Foto Baru (opsional, JPG/PNG)</label>
                      <input type="file" class="form-control" name="edit_fotoLokasi" accept=".jpg,.jpeg,.png">
                    </div>
                    <div class="mb-2" id="previewFotoEditWrapper" >
                      <label class="form-label">Foto Saat Ini:</label><br>
                      <img src="" id="previewFotoEdit" class="img-thumbnail" >
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Keterangan</label>
                      <textarea class="form-control form-control-sm" id="edit_keterangan" name="edit_keterangan" rows="5"></textarea>
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

           <!-- Modal Hapus Lokasi -->
          <div class="modal fade" id="modalHapusLokasi" tabindex="-1" aria-labelledby="modalHapusLokasiLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formHapusLokasi">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="hapus_id" id="hapus_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalHapusLokasiLabel">Konfirmasi Hapus</h5>
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
    <script src="../assets/js/lokasi.js"></script>
    <script src="../vendors/bootstrap/bootstrap.min.js"></script>
    <script src="../vendors/anchorjs/anchor.min.js"></script>
    <script src="../vendors/is/is.min.js"></script>
    <script src="../vendors/prism/prism.js"></script>
    <script src="../vendors/lodash/lodash.min.js"></script>
    <script src="../vendors/list.js/list.min.js"></script>
    <script src="../assets/js/theme.js"></script>
  </body>
</html>
 