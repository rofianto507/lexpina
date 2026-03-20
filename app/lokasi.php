<?php
session_start();
include("../config/configuration.php");
if($_SESSION["nama"]!="" && $_SESSION["id"]!=""){
  $_SESSION["menu"]="lokasi";
  $menu=$_SESSION["menu"];
  $nama=$_SESSION["nama"];
  $id=$_SESSION["id"];
  $username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
  $foto=$_SESSION["foto"];
  $last_login=$_SESSION["last_login"];
    $query_provinsi = $pdo->query("SELECT * FROM provinsis WHERE status=1 limit 1");
  $data_provinsi = $query_provinsi->fetch();
  $nama_provinsi = $data_provinsi["nama"];
  $kode_provinsi = $data_provinsi["kode"];
  $lat_provinsi = $data_provinsi["lat"];
  $lng_provinsi = $data_provinsi["lng"];
  if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kategori_id'], $_POST['namaLokasi'])) {
    $kategori_id = intval($_POST['kategori_id']);
    $namaLokasi = trim($_POST['namaLokasi']);
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
    $sumber_id = intval($_POST['sumber_id'] ?? 0);
    $user_id = $id_user;

    if($kategori_id && $namaLokasi){
      $stmtInsert = $pdo->prepare("INSERT INTO lokasis (kategori_id, nama, alamat, hp, foto, keterangan, latitude, longitude, user_id, sumber_id, status) VALUES (:kategori_id, :nama, :alamat, :hp, :foto, :keterangan, :latitude, :longitude, :user_id, :sumber_id, 1)");
      $stmtInsert->execute([
        ':kategori_id'=>$kategori_id,
        ':nama'=>$namaLokasi,
        ':alamat'=>$alamat,
        ':hp'=>$hp,
        ':foto'=>$foto,
        ':keterangan'=>$keterangan,
        ':latitude'=>$latitude,
        ':longitude'=>$longitude,
        ':user_id'=>$user_id,
        ':sumber_id'=>$sumber_id
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
    $edit_sumber_id = intval($_POST['edit_sumber_id'] ?? 0);
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
        $stmtUpdate = $pdo->prepare("UPDATE lokasis SET kategori_id=:kategori_id, nama=:nama, alamat=:alamat, hp=:hp, keterangan=:keterangan, latitude=:latitude, longitude=:longitude, foto=:foto, sumber_id=:sumber_id, updated_at=NOW() WHERE id=:id");
        $stmtUpdate->execute([
          ':kategori_id'=>$edit_kategori_id,
          ':nama'=>$edit_nama,
          ':alamat'=>$edit_alamat,
          ':hp'=>$edit_hp,
          ':keterangan'=>$edit_keterangan,
          ':latitude'=>$edit_latitude,
          ':longitude'=>$edit_longitude,
          ':foto'=>$edit_foto,
          ':sumber_id'=>$edit_sumber_id,
          ':id'=>$edit_id
        ]);
      } else {
        // Jika tidak upload foto baru
        $stmtUpdate = $pdo->prepare("UPDATE lokasis SET kategori_id=:kategori_id, nama=:nama, alamat=:alamat, hp=:hp, keterangan=:keterangan, latitude=:latitude, longitude=:longitude, sumber_id=:sumber_id, updated_at=NOW() WHERE id=:id");
        $stmtUpdate->execute([
          ':kategori_id'=>$edit_kategori_id,
          ':nama'=>$edit_nama,
          ':alamat'=>$edit_alamat,
          ':hp'=>$edit_hp,
          ':keterangan'=>$edit_keterangan,
          ':latitude'=>$edit_latitude,
          ':longitude'=>$edit_longitude,
          ':sumber_id'=>$edit_sumber_id,
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
  $sumberList = [];
  $qSumber = $pdo->query("SELECT id, nama FROM sumbers WHERE status=1 AND tipe='LOKASI PENTING' ORDER BY nama ASC");
  while($s = $qSumber->fetch(PDO::FETCH_ASSOC)){
    $sumberList[] = $s;
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
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css"/>
    <link href="../vendors/prism/prism-okaidia.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">
    <link href="../vendors/overlayscrollbars/OverlayScrollbars.min.css" rel="stylesheet">
    <link href="../assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
    <link href="../assets/css/theme.min.css" rel="stylesheet" id="style-default">
    <link href="../assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
    <link href="../assets/css/user.min.css" rel="stylesheet" id="user-style-default">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
      var isRTL = JSON.parse(localStorage.getItem('isRTL'));
      if (isRTL) {
        var linkDefault = document.getElementById('style-default');
        var userLinkDefault = document.getElementById('user-style-default');
        linkDefault.setAttribute('disabled', true);
        userLinkDefault.setAttribute('disabled', true);
        document.querySelector('html').setAttribute('dir', 'rtl');
      } else {
        var linkRTL = document.getElementById('style-rtl');
        var userLinkRTL = document.getElementById('user-style-rtl');
        linkRTL.setAttribute('disabled', true);
        userLinkRTL.setAttribute('disabled', true);
      }
    </script>
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
                <?php endif; ?>
          <div class="card mb-3">
            <div class="card-header">
              <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                  <h5 class="fs-0 mb-0"><span class="fas fa-map-signs me-2 fs-0"></span> Lokasi Penting</h5>
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
                <table id="LokasiTable" class="display table table-striped table-bordered table-sm" style="width:100%">
                  <thead class="bg-primary text-white">
                    <tr>
                      <th>ID</th>
                      <th>Kategori</th>
                      <th>Nama</th>
                      <th>Alamat</th>
                      <th>HP/Kontak</th>
                      <th>Latitude</th>
                      <th>Longitude</th>
                      <th>Sumber</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php
                    $q = "SELECT l.*, k.nama as kategori_nama, s.nama as sumber_nama FROM lokasis l 
                    LEFT JOIN lokasi_kategoris k ON l.kategori_id=k.id
                    LEFT JOIN sumbers s ON l.sumber_id=s.id WHERE l.status=1 ORDER BY l.created_at DESC";
                    $stmt = $pdo->query($q);
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      echo "<tr>
                        <td>".$row['id']."</td>
                        <td>".$row['kategori_nama']."</td>
                        <td>".$row['nama']."</td>
                         <td>".(mb_strlen($row['alamat']) > 100 ? mb_substr($row['alamat'], 0, 100).'...' : $row['alamat'])."</td>
                        <td>".$row['hp']."</td>
                        <td>".$row['latitude']."</td>
                        <td>".$row['longitude']."</td>
                 
                        <td>".$row['sumber_nama']."</td>
                        <td>
                          <button class='btn btn-sm btn-info btnEditLokasi'
                            data-id='{$row['id']}'
                            data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)."'
                            data-kategori-id='{$row['kategori_id']}'
                            data-alamat='".htmlspecialchars($row['alamat'],ENT_QUOTES)."'
                            data-hp='".htmlspecialchars($row['hp'],ENT_QUOTES)."'
                            data-keterangan='".htmlspecialchars($row['keterangan'],ENT_QUOTES)."'
                            data-latitude='".htmlspecialchars($row['latitude'],ENT_QUOTES)."'
                            data-longitude='".htmlspecialchars($row['longitude'],ENT_QUOTES)."'
                            data-foto='".htmlspecialchars($row['foto'],ENT_QUOTES)."'
                            data-sumber-id='{$row['sumber_id']}'
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
                      <label class="form-label">Alamat</label>
                      <input type="text" class="form-control form-control-sm" name="alamat">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">HP/Kontak</label>
                      <input type="text" class="form-control form-control-sm" name="hp">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Tentukan Lokasi di Map</label>
                      <div id="lokasiMapTambah" style="height: 350px; border:1px solid #ccc"></div>
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
                    <div class="mb-3">
                      <label class="form-label">Sumber Dokumen</label>
                      <select class="form-select" name="sumber_id" required>
                        <option value="">- Pilih Sumber -</option>
                        <?php foreach($sumberList as $s): ?>
                          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama']) ?></option>
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
          <div class="modal fade" id="modalEditLokasi" tabindex="-1" aria-labelledby="modalEditLokasiLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <form method="POST" id="formEditLokasi" enctype="multipart/form-data">
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
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Nama Lokasi</label>
                      <input type="text" class="form-control form-control-sm" id="edit_nama" name="edit_nama" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Alamat</label>
                      <input type="text" class="form-control form-control-sm" id="edit_alamat" name="edit_alamat">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">HP/Kontak</label>
                      <input type="text" class="form-control form-control-sm" id="edit_hp" name="edit_hp">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Tentukan Lokasi di Map</label>
                      <div id="lokasiMapEdit" style="height: 350px; border:1px solid #ccc"></div>
                      
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
                    <div class="mb-2" id="previewFotoEditWrapper" style="display:none">
                      <label class="form-label">Foto Saat Ini:</label><br>
                      <img src="" id="previewFotoEdit" class="img-thumbnail" style="max-width:120px;">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Keterangan</label>
                      <textarea class="form-control form-control-sm" id="edit_keterangan" name="edit_keterangan" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Sumber Dokumen</label>
                      <select class="form-select" name="edit_sumber_id" id="edit_sumber_id" required>
                        <option value="">- Pilih Sumber -</option>
                        <?php foreach($sumberList as $s): ?>
                          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama']) ?></option>
                        <?php endforeach; ?>
                      </select>
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
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
      $(document).ready(function() {
        $('#LokasiTable').DataTable({
          "autoWidth": false,
          "order": [[ 0, "desc" ]]
        });
      });
      // Handle tombol Edit (reload data di modal)
      $(document).on('click', '.btnEditLokasi', function() {
        var id = $(this).data('id');
        $('#edit_id').val(id);
        $('#edit_nama').val($(this).data('nama'));
        $('#edit_kategori_id').val($(this).data('kategori-id'));
        $('#edit_alamat').val($(this).data('alamat'));
        $('#edit_hp').val($(this).data('hp'));
        $('#edit_keterangan').val($(this).data('keterangan'));
        $('#edit_latitude').val($(this).data('latitude'));
        $('#edit_longitude').val($(this).data('longitude'));
        $('#foto_lama').val($(this).data('foto'));
        $('#edit_sumber_id').val($(this).data('sumber-id'));
        var foto = $(this).data('foto');
        if(foto) {
          $('#previewFotoEdit').attr('src', '../public/upload/lokasi/' + foto);
          $('#previewFotoEditWrapper').show();
        } else {
          $('#previewFotoEditWrapper').hide();
        }
        $('#modalEditLokasi').modal('show');
      });

      // Modal Hapus
      $(document).on('click', '.btnHapusLokasi', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#hapus_id').val(id);
        $('#hapus_nama').text(nama);
        $('#modalHapusLokasi').modal('show');
      });
    var map, marker;

    var mapTambah, mapEdit, markerTambah, markerEdit;
    function initMapTambah(lat, lng) {
      if(!mapTambah) {
        mapTambah = L.map('lokasiMapTambah').setView([lat, lng], 8);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19}).addTo(mapTambah);
      } else {
        mapTambah.setView([lat, lng], 8);
        if(markerTambah) { mapTambah.removeLayer(markerTambah); markerTambah = null; }
      }
      mapTambah.invalidateSize();
    }

    function enableMapPickTambah() {
      if(!mapTambah) return;
      mapTambah.off('click');
      mapTambah.on('click', function(e){
          var lat = e.latlng.lat.toFixed(6);
          var lng = e.latlng.lng.toFixed(6);
          $('input[name="latitude"]').val(lat);
          $('input[name="longitude"]').val(lng);
          if(markerTambah) mapTambah.removeLayer(markerTambah);
         // markerTambah = L.marker(e.latlng).addTo(mapTambah);
          markerTambah = L.marker(e.latlng, {
          icon: L.icon({
            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            shadowSize: [41, 41]
          })
        }).addTo(mapTambah);
          markerTambah.bindPopup('Koordinat:<br>'+lat+', '+lng).openPopup();
      });
    }

    function initMapEdit(lat, lng) {
      if(!mapEdit) {
        mapEdit = L.map('lokasiMapEdit').setView([lat, lng], 8);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19}).addTo(mapEdit);
      } else {
        mapEdit.setView([lat, lng], 8);
        if(markerEdit) { mapEdit.removeLayer(markerEdit); markerEdit = null; }
      }
      mapEdit.invalidateSize();
    }

    function enableMapPickEdit() {
      if(!mapEdit) return;
      mapEdit.off('click');
      mapEdit.on('click', function(e){
          var lat = e.latlng.lat.toFixed(6);
          var lng = e.latlng.lng.toFixed(6);
          $('#edit_latitude').val(lat);
          $('#edit_longitude').val(lng);
          if(markerEdit) mapEdit.removeLayer(markerEdit);
         // markerEdit = L.marker(e.latlng).addTo(mapEdit);
          markerEdit = L.marker(e.latlng, {
            icon: L.icon({
              iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
              iconSize: [25, 41],
              iconAnchor: [12, 41],
              popupAnchor: [1, -34],
              shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
              shadowSize: [41, 41]
            })
          }).addTo(mapEdit);
          markerEdit.bindPopup('Koordinat:<br>'+lat+', '+lng).openPopup();
      });
    }
    // Modal Tambah
    $('#modalTambahLokasi').on('shown.bs.modal', function (e) {
      var lat = $('input[name="latitude"]').val() || <?php  echo $lat_provinsi; ?>;
      var lng = $('input[name="longitude"]').val() || <?php  echo $lng_provinsi; ?>;
      lat = parseFloat(lat); lng = parseFloat(lng);
      setTimeout(function(){
        initMapTambah(lat, lng);
        enableMapPickTambah();
        if(lat && lng && lat != <?php  echo $lat_provinsi; ?>) {
          if(markerTambah) mapTambah.removeLayer(markerTambah);
          markerTambah = L.marker([lat, lng]).addTo(mapTambah);
          markerTambah.bindPopup('Koordinat:<br>'+lat+', '+lng).openPopup();
        }
      }, 350);
    });
    $('#modalTambahLokasi').on('hidden.bs.modal', function(){
      if(mapTambah){ mapTambah.remove(); mapTambah = null; markerTambah = null; }
      $('#lokasiMapTambah').html('');
    });

    // Modal Edit
    $('#modalEditLokasi').on('shown.bs.modal', function (e) {
      var lat = $('#edit_latitude').val() || <?php  echo $lat_provinsi; ?>;
      var lng = $('#edit_longitude').val() || <?php  echo $lng_provinsi; ?>;
      lat = parseFloat(lat); lng = parseFloat(lng);
      setTimeout(function(){
        initMapEdit(lat, lng);
        enableMapPickEdit();
        if(lat && lng && lat != <?php  echo $lat_provinsi; ?>) {
          if(markerEdit) mapEdit.removeLayer(markerEdit);
          markerEdit = L.marker([lat, lng], {
            icon: L.icon({
              iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
              iconSize: [25, 41],
              iconAnchor: [12, 41],
              popupAnchor: [1, -34],
              shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
              shadowSize: [41, 41]
            })
          }).addTo(mapEdit);
          markerEdit.bindPopup('Koordinat:<br>'+lat+', '+lng).openPopup();
        }
      }, 350);
    });
    $('#modalEditLokasi').on('hidden.bs.modal', function(){
      if(mapEdit){ mapEdit.remove(); mapEdit = null; markerEdit = null; }
      $('#lokasiMapEdit').html('');
    });
    </script>
    <script src="../vendors/popper/popper.min.js"></script>
    <script src="../vendors/bootstrap/bootstrap.min.js"></script>
    <script src="../vendors/anchorjs/anchor.min.js"></script>
    <script src="../vendors/is/is.min.js"></script>
    <script src="../vendors/prism/prism.js"></script>
    <script src="../vendors/fontawesome/all.min.js"></script>
    <script src="../vendors/lodash/lodash.min.js"></script>
    <script src="../vendors/list.js/list.min.js"></script>
    <script src="../assets/js/theme.js"></script>
  </body>
</html>
<?php
}else{
  header("Location: ../index");
}
?>