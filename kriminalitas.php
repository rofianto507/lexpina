<?php
session_start();
include("../config/configuration.php");
require_once("log_helper.php");
if($_SESSION["nama"]!="" && $_SESSION["id"]!=""){
  $_SESSION["menu"]="kriminalitas";
  $menu=$_SESSION["menu"];
  $nama=$_SESSION["nama"];
  $id=$_SESSION["id"];
  $username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
  $polres_id=$_SESSION["polres_id"] ?? null;
  $polsek_id=$_SESSION["polsek_id"] ?? null;
  $foto=$_SESSION["foto"];
  $last_login=$_SESSION["last_login"];
  $query_provinsi = $pdo->query("SELECT * FROM provinsis WHERE status=1 limit 1");
  $data_provinsi = $query_provinsi->fetch();
  $nama_provinsi = $data_provinsi["nama"];
  $kode_provinsi = $data_provinsi["kode"];
  $lat_provinsi = $data_provinsi["lat"];
  $lng_provinsi = $data_provinsi["lng"];
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_id'])) {
    $hapus_id = intval($_POST['hapus_id']);
    if($hapus_id){
        // Soft Delete: ubah status = 0
        $pdo->prepare("UPDATE kriminals SET status=0, updated_at=NOW() WHERE id=?")->execute([$hapus_id]);
        // Jika ingin hard delete, gunakan query: DELETE FROM kriminals WHERE id=?
        header("Location: kriminalitas?status=hapus_sukses");
        exit;
    }
}
// proses tambah data kriminalitas
if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['sub_kategori_id'],$_POST['desa_id'],$_POST['polres_id'])) {
    $sub_kategori_id = intval($_POST['sub_kategori_id']);
  
    $desa_id = intval($_POST['desa_id']);
    if($akses=="POLRES") {
      $polres_id = $_SESSION["polres_id"]; // dari session
    } else {
       $polres_id = intval($_POST['polres_id']);
    }
    if($akses=="POLSEK") {
      $polsek_id = $_SESSION["polsek_id"]; // dari session
    } else {
      $polsek_id = intval($_POST['polsek_id']);
    }
    $sumber_id = intval($_POST['sumber_id']);
    $user_id = $id; // dari session login
    $tanggal = !empty($_POST['tanggal']) ? $_POST['tanggal'] : null;
    $keterangan = trim($_POST['keterangan'] ?? '');
    $penanggungjawab = trim($_POST['penanggungjawab'] ?? '');
    $state = trim($_POST['state'] ?? 'PROSES');
    $sub_state = trim($_POST['sub_state'] ?? '');
    $poin = intval($_POST['poin'] ?? 1);
    $no_lp = trim($_POST['no_lp'] ?? '');
      $jenis_tkp_id = intval($_POST['jenis_tkp_id'] ?? 0);
      $lokasi = trim($_POST['lokasi'] ?? '');
      $penyebab = trim($_POST['penyebab'] ?? '');
      $tujuan = trim($_POST['tujuan'] ?? '');
      $latitude = floatval($_POST['latitude'] ?? 0);
      $longitude = floatval($_POST['longitude'] ?? 0);
      $pelapor = trim($_POST['pelapor'] ?? '');
      $terlapor = trim($_POST['terlapor'] ?? '');
      $korban = trim($_POST['korban'] ?? '');
      $tanggal_laporan = !empty($_POST['tanggal_laporan']) ? $_POST['tanggal_laporan'] : null;
      $tindak_pidana = trim($_POST['tindak_pidana'] ?? '');
      $saksi= trim($_POST['saksi'] ?? '');
      $barang_bukti = trim($_POST['barang_bukti'] ?? '');
      $uraian = trim($_POST['uraian'] ?? '');

    if($sub_kategori_id && $desa_id && $polres_id ){
        $stmt = $pdo->prepare("INSERT INTO kriminals (sub_kategori_id, desa_id, polres_id, polsek_id, sumber_id, user_id, tanggal, keterangan, 
        penanggungjawab, state, sub_state, poin, status, created_at, no_lp, jenis_tkp_id, lokasi, penyebab, tujuan, latitude, longitude, pelapor, terlapor, korban, tanggal_laporan, tindak_pidana, saksi, barang_bukti, uraian)
            VALUES (:sub_kategori_id, :desa_id, :polres_id, :polsek_id, :sumber_id, :user_id, :tanggal, :keterangan, :penanggungjawab, 
            :state, :sub_state, :poin, 1, NOW(), :no_lp, :jenis_tkp_id, :lokasi, :penyebab, :tujuan, :latitude, :longitude, :pelapor, :terlapor, :korban, :tanggal_laporan, :tindak_pidana, :saksi, :barang_bukti, :uraian)");
        $stmt->execute([
            ':sub_kategori_id'=>$sub_kategori_id,
            ':desa_id'=>$desa_id,
            ':polres_id'=>$polres_id,
            ':polsek_id'=>$polsek_id,
            ':sumber_id'=>$sumber_id,
            ':user_id'=>$user_id,
            ':tanggal'=>$tanggal,
            ':keterangan'=>$keterangan,
            ':penanggungjawab'=>$penanggungjawab,
            ':state'=>$state,
            ':sub_state'=>$sub_state,
            ':poin'=>$poin,
            ':no_lp'=>$no_lp,
            ':jenis_tkp_id'=>$jenis_tkp_id,
            ':lokasi'=>$lokasi,
            ':penyebab'=>$penyebab,
            ':tujuan'=>$tujuan,
            ':latitude'=>$latitude,
            ':longitude'=>$longitude,
            ':pelapor'=>$pelapor,
            ':terlapor'=>$terlapor,
            ':korban'=>$korban,
            ':tanggal_laporan'=>$tanggal_laporan,
            ':tindak_pidana'=>$tindak_pidana,
            ':saksi'=>$saksi,
            ':barang_bukti'=>$barang_bukti,
            ':uraian'=>$uraian
        ]);
        $data_id = $pdo->lastInsertId();
        logUser($pdo, $id, 'add', 'kriminalitas', $data_id, 'Tambah oleh '.$username);
        header("Location: kriminalitas?status=sukses");
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
    <title>Data Kriminalitas | Peta Digital</title>

    <!-- Favicons, CSS, dan Theme -->
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/img/favicons/favicon-16x16.png">
    <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicons/favicon.ico">
    <link rel="manifest" href="../assets/img/favicons/manifest.json">
    <meta name="msapplication-TileImage" content="../assets/img/favicons/mstile-150x150.png">
    <meta name="theme-color" content="#ffffff">
    <script src="../assets/js/config.js"></script>
    <script src="../vendors/overlayscrollbars/OverlayScrollbars.min.js"></script>
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
            Data kriminalitas berhasil ditambahkan.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          
          <?php if(isset($_GET['status']) && $_GET['status']=='hapus_sukses'): ?>
          <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            Data kriminalitas berhasil dihapus.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          <div class="card mb-3">
            <div class="card-header">
              <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                  <h5 class="fs-0 mb-0"><span class="fas fa-user-secret me-2 fs-0"></span>Data Kriminalitas</h5>  
                </div>
                <div class="col-auto ms-auto">
                    <button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#modalImportWordA">
                    <i class="fas fa-file-word me-1"></i> Import LP A
                  </button>
                   <button class="btn btn-sm btn-success me-1" data-bs-toggle="modal" data-bs-target="#modalImportWordB">
                    <i class="fas fa-file-word me-1"></i> Import LP B
                  </button>
                
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahkriminalitas">
                  Tambah Data
                </button>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="kriminalitasTable" class="display table table-striped table-bordered table-sm" style="width:100%">
                  <thead class="bg-primary text-white">
                    <tr>
                      <th>ID</th>
                      <th>No LP</th>
                      <th>Kategori</th>
                      <th>Sub Kategori</th>
                      <th>Tempat Kejadian</th>              
                      <th>Polres</th>
                      <th>Kabupaten</th>
                      <th>Sumber</th>
                     
                      <th>State</th>
                      <th>Sub State</th>
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
                      
                      <th>State</th>
                      <th>Sub State</th>
                      <th>Aksi</th>
                    </tr>
                  </tfoot>
                  <tbody>
                  <?php
                  if($akses == "POLRES") {
                     $query = "SELECT
                      k.id,
                      k.tanggal,
                      k.sub_kategori_id,
                      k.penanggungjawab,
                      k.lokasi,
                      k.state, 
                      k.sub_state,
                      k.poin,
                      k.no_lp,
                      po.nama AS polres_nama,
                      kab.nama AS kabupaten_nama,
                      s.nama AS sumber_nama,
                      ka.nama as sub_kategori_nama,
                      kat.nama as kategori_nama 
                    FROM kriminals k
                    left JOIN desas d ON k.desa_id = d.id
                    left JOIN kecamatans kc ON d.kecamatan_id = kc.id
                    left JOIN kabupatens kab ON kc.kabupaten_id = kab.id
                    LEFT JOIN kriminal_sub_kategoris ka ON k.sub_kategori_id = ka.id
                    LEFT JOIN kriminal_kategoris kat ON ka.kategori_id = kat.id
                    LEFT JOIN polress po ON k.polres_id = po.id
                    LEFT JOIN sumbers s ON k.sumber_id = s.id
                    WHERE k.status=1 AND k.polres_id={$polres_id}
                    ORDER BY k.id DESC";
                  } else if($akses == "POLSEK") {
                     $query = "SELECT
                      k.id,
                      k.tanggal,
                      k.sub_kategori_id,
                      k.penanggungjawab,
                      k.lokasi,
                      k.state,
                      k.sub_state,
                      k.poin,
                      k.no_lp,
                      po.nama AS polres_nama,
                      kab.nama AS kabupaten_nama,
                      s.nama AS sumber_nama,
                      ka.nama as sub_kategori_nama,
                      kat.nama as kategori_nama
                    FROM kriminals k
                    left JOIN desas d ON k.desa_id = d.id
                    left JOIN kecamatans kc ON d.kecamatan_id = kc.id
                    left JOIN kabupatens kab ON kc.kabupaten_id = kab.id
                    LEFT JOIN kriminal_sub_kategoris ka ON k.sub_kategori_id = ka.id
                    LEFT JOIN kriminal_kategoris kat ON ka.kategori_id = kat.id
                    LEFT JOIN polress po ON k.polres_id = po.id
                    LEFT JOIN polseks ps ON k.polsek_id = ps.id
                    LEFT JOIN sumbers s ON k.sumber_id = s.id
                    WHERE k.status=1 AND k.polsek_id={$polsek_id}
                    ORDER BY k.id DESC";
                  } else if($akses == "POLDA") {
                    $query = "SELECT
                      k.id,
                      k.tanggal,
                      k.sub_kategori_id,
                      k.penanggungjawab,
                      k.lokasi,
                      k.state,
                      k.sub_state,
                      k.poin,
                      k.no_lp,
                      po.nama AS polres_nama,
                      kab.nama AS kabupaten_nama,
                      s.nama AS sumber_nama,
                      ka.nama as sub_kategori_nama,
                      kat.nama as kategori_nama
                    FROM kriminals k
                    left JOIN desas d ON k.desa_id = d.id
                    left JOIN kecamatans kc ON d.kecamatan_id = kc.id
                    left JOIN kabupatens kab ON kc.kabupaten_id = kab.id
                    LEFT JOIN kriminal_sub_kategoris ka ON k.sub_kategori_id = ka.id
                    LEFT JOIN kriminal_kategoris kat ON ka.kategori_id = kat.id                 
                    LEFT JOIN polress po ON k.polres_id = po.id
           
                    LEFT JOIN sumbers s ON k.sumber_id = s.id
                    WHERE k.status=1
                    ORDER BY k.id DESC";
                  }else{
                    $query = "SELECT
                      k.id,
                      k.tanggal,
                      k.sub_kategori_id,
                      k.penanggungjawab,
                      k.lokasi,
                      k.state,
                      k.sub_state,
                      k.poin,
                      k.no_lp,
                      po.nama AS polres_nama,
                      kab.nama AS kabupaten_nama,
                      s.nama AS sumber_nama,
                      ka.nama as sub_kategori_nama,
                      kat.nama as kategori_nama
                    FROM kriminals k
                    left JOIN desas d ON k.desa_id = d.id
                    left JOIN kecamatans kc ON d.kecamatan_id = kc.id
                    left JOIN kabupatens kab ON kc.kabupaten_id = kab.id
                    LEFT JOIN kriminal_sub_kategoris ka ON k.sub_kategori_id = ka.id
                    LEFT JOIN kriminal_kategoris kat ON ka.kategori_id = kat.id                 
                    LEFT JOIN polress po ON k.polres_id = po.id
           
                    LEFT JOIN sumbers s ON k.sumber_id = s.id
                    WHERE k.status=1 and k.tujuan='{$akses}'
                    ORDER BY k.id DESC";
                  }
                    $stmt = $pdo->query($query);
                    
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      echo "<tr>
                        <td>".$row['id']."</td>
                        <td>".$row['no_lp']."</td>
                        <td>".$row['kategori_nama']."</td>
                        <td>".$row['sub_kategori_nama']."</td>
                        <td>".$row['lokasi']."</td>
                        <td>".$row['polres_nama']."</td>
                        <td>".$row['kabupaten_nama']."</td>
                         
                        <td>".$row['sumber_nama']."</td>

                        <td>".$row['state']."</td>
                        <td>".$row['sub_state']."</td>

                        <td>
                          <a href='kriminalitas-edit?id=".$row['id']."' class='btn btn-sm btn-info'>Edit</a>
                          <button class='btn btn-sm btn-danger btnHapuskriminalitas' data-id='".$row['id']."' data-sub-kategori='".htmlspecialchars($row['sub_kategori_nama'],ENT_QUOTES)."'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <!-- Modal Konfirmasi Hapus kriminalitas -->
            <div class="modal fade" id="modalHapuskriminalitas" tabindex="-1" aria-labelledby="modalHapuskriminalitasLabel" aria-hidden="true">
              <div class="modal-dialog">
                <form method="POST" id="formHapuskriminalitas">
                  <input type="hidden" name="hapus_id" id="hapus_id_kriminalitas">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalHapuskriminalitasLabel">Konfirmasi Hapus</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <p>Yakin ingin menghapus data kriminalitas ini ?</p>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                      <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
            <!-- ===================== MODAL IMPORT LP A ===================== -->
<div class="modal fade" id="modalImportWordA" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title"><i class="fas fa-file-word me-2"></i>Import Laporan Polisi Model A</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <!-- STEP 1: Upload -->
        <div id="stepUploadA">
          <div class="alert alert-warning">
            <strong><i class="fas fa-info-circle"></i> LP Model A - Tindak Pidana Yang Ditemukan</strong><br>
            1. Upload file <strong>Laporan Polisi Model A (.docx)</strong><br>
            2. Data otomatis ter-parse (No LP, Peristiwa, Terlapor, Korban, dll)<br>
            3. Lengkapi: Kategori, Sub Kategori, Desa, Polres, Polsek<br>
            4. Klik <strong>Simpan</strong>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Pilih File LP Model A (.docx)</label>
            <input type="file" class="form-control" id="fileWordImportA" accept=".docx">
          </div>
          <button type="button" class="btn btn-warning" id="btnParseWordA" disabled>
            <i class="fas fa-cog me-1"></i> Parse Dokumen
          </button>
          <div id="parseLoadingA" class="mt-2" style="display:none;">
            <div class="spinner-border spinner-border-sm text-warning"></div>
            <span class="ms-2">Sedang memproses file...</span>
          </div>
          <div id="parseErrorA" class="alert alert-danger mt-2" style="display:none;"></div>
        </div>

        <!-- STEP 2: Form -->
        <div id="stepFormA" style="display:none;">
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> File LP A berhasil di-parse! Periksa data dan lengkapi field yang kosong.
          </div>

          <form method="POST" id="formImportWordA">
            <div class="row">
              <!-- KOLOM KIRI: Data dari Word -->
              <div class="col-md-6">
                <h6 class="fw-bold text-success border-bottom pb-2 mb-3">
                  <i class="fas fa-file-alt me-1"></i> Data dari File Word (Otomatis)
                </h6>
                <div class="mb-2">
                  <label class="form-label">No. LP</label>
                  <input type="text" class="form-control form-control-sm" name="no_lp" id="impA_no_lp">
                </div>
                 
                <div class="mb-2">
                  <label class="form-label">Waktu Kejadian</label>
                  <input type="datetime-local" class="form-control form-control-sm" name="tanggal" id="impA_tanggal">
                </div>
                <div class="mb-2">
                  <label class="form-label">Tempat Kejadian</label>
                  <textarea class="form-control form-control-sm" name="lokasi" id="impA_lokasi" rows="3"></textarea>
                </div>
                <div class="mb-2">
                  <label class="form-label">Apa yang Terjadi?</label>
                  <textarea class="form-control form-control-sm" name="keterangan" id="impA_keterangan" rows="2"></textarea>
                </div>
                <div class="mb-2">
                  <label class="form-label">Terlapor</label>
                  <textarea class="form-control form-control-sm" name="terlapor" id="impA_terlapor" rows="4"></textarea>
                </div>
                <div class="mb-2">
                  <label class="form-label">Korban</label>
                  <textarea class="form-control form-control-sm" name="korban" id="impA_korban" rows="4"></textarea>
                </div>
                <div class="mb-2">
                  <label class="form-label">Bagaimana Terjadi (Kronologi)</label>
                  <textarea class="form-control form-control-sm" name="uraian" id="impA_uraian_kronologi" rows="5"></textarea>
                </div>
                <div class="mb-2">
                  <label class="form-label">Pelapor</label>
                  <textarea class="form-control form-control-sm" name="pelapor" id="impA_pelapor" rows="2"></textarea>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-2">
                    <label class="form-label">Tanggal Laporan</label>
                    <input type="datetime-local" class="form-control form-control-sm" name="tanggal_laporan" id="impA_tanggal_laporan">
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-2">
                    <label class="form-label">Latitude</label>
                    <input type="text" class="form-control form-control-sm" name="latitude" id="impA_latitude">
                  </div>
                  <div class="col-md-6 mb-2">
                    <label class="form-label">Longitude</label>
                    <input type="text" class="form-control form-control-sm" name="longitude" id="impA_longitude">
                  </div>
                </div>

                <h6 class="fw-bold text-warning border-bottom pb-2 mb-3 mt-3">
                  <i class="fas fa-table me-1"></i> Data dari Tabel Bawah Dokumen
                </h6>
                <div class="mb-2">
                  <label class="form-label">Tindak Pidana</label>
                  <textarea class="form-control form-control-sm" name="tindak_pidana" id="impA_tindak_pidana" rows="3"></textarea>
                </div>
                <div class="mb-2">
                  <label class="form-label">Saksi-Saksi</label>
                  <textarea class="form-control form-control-sm" name="saksi" id="impA_saksi" rows="3"></textarea>
                </div>
                <div class="mb-2">
                  <label class="form-label">Barang Bukti</label>
                  <textarea class="form-control form-control-sm" name="barang_bukti" id="impA_barang_bukti" rows="3"></textarea>
                </div>
                <div class="mb-2">
                  <label class="form-label">Uraian Singkat</label>
                  <textarea class="form-control form-control-sm" name="uraian_singkat" id="impA_uraian_singkat" rows="5"></textarea>
                </div>
              </div>

              <!-- KOLOM KANAN: Data Manual -->
              <div class="col-md-6">
                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                  <i class="fas fa-edit me-1"></i> Data Manual (Harus Dilengkapi)
                </h6>
                <div class="mb-2">
                  <label class="form-label">Jumlah</label>
                  <input type="number" class="form-control form-control-sm" value="1" name="poin" required>
                </div>
                <div class="mb-2">
                  <label class="form-label">Kategori <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm" name="kategori_id" id="impA_kategori_id" required>
                    <option value="">- Pilih Kategori -</option>
                    <?php
                    $katA = $pdo->query("SELECT id, nama FROM kriminal_kategoris WHERE status=1 ORDER BY nama");
                    while($k = $katA->fetch()) {
                      echo "<option value='{$k['id']}'>".htmlspecialchars($k['nama'])."</option>";
                    }
                    ?>
                  </select>
                </div>
                <div class="mb-2">
                  <label class="form-label">Sub Kategori <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm" name="sub_kategori_id" id="impA_sub_kategori_id" required>
                    <option value="">- Pilih Sub Kategori -</option>
                  </select>
                </div>
                <div class="mb-2">
                  <label class="form-label">Kabupaten <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm" name="kabupaten_id" id="impA_kabupaten_id" required>
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
                <div class="mb-2">
                  <label class="form-label">Kecamatan <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm" name="kecamatan_id" id="impA_kecamatan_id" required disabled>
                    <option value="">- Pilih Kecamatan -</option>
                  </select>
                </div>
                <div class="mb-2">
                  <label class="form-label">Desa <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm" name="desa_id" id="impA_desa_id" required disabled>
                    <option value="">- Pilih Desa -</option>
                  </select>
                </div>
                <div class="mb-2">
                  <label class="form-label">Polres <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm" name="polres_id" id="impA_polres_id" required>
                    <option value="">- Pilih Polres -</option>
                    <?php
                    if($akses == "POLRES") {
                      $polA = $pdo->prepare("SELECT id, nama FROM polress WHERE status=1 AND id=? ORDER BY nama");
                      $polA->execute([$polres_id]);
                    } else if($akses == "POLSEK") {
                      $polA = $pdo->prepare("SELECT polress.id, polress.nama FROM polress left join polseks on polress.id = polseks.polres_id WHERE polress.status=1 AND polseks.id=? ORDER BY polress.nama");
                      $polA->execute([$polsek_id]);
                    } else {
                      $polA = $pdo->query("SELECT id, nama FROM polress WHERE status=1 ORDER BY nama");
                    }
                    while($p = $polA->fetch()) {
                      echo "<option value='{$p['id']}'>".htmlspecialchars($p['nama'])."</option>";
                    }
                    ?>
                  </select>
                </div>
                <div class="mb-2">
                  <label class="form-label">Polsek</label>
                  <select class="form-select form-select-sm" name="polsek_id" id="impA_polsek_id" disabled>
                    <option value="">- Pilih Polsek -</option>
                  </select>
                </div>
                <div class="mb-2">
                  <label class="form-label">Jenis Lokasi</label>
                  <select class="form-select form-select-sm" name="jenis_tkp_id">
                    <option value="">- Pilih Jenis Lokasi -</option>
                    <?php
                    $jlA = $pdo->query("SELECT id, nama FROM jenis_tkps WHERE status=1 ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
                    foreach($jlA as $jl) {
                      echo "<option value='{$jl['id']}'>".htmlspecialchars($jl['nama'])."</option>";
                    }
                    ?>
                  </select>
                </div>
                <div class="mb-2">
                  <label class="form-label">Sumber Dokumen <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm" name="sumber_id" required>
                    <option value="">- Pilih Sumber -</option>
                    <?php
                    $smbA = $pdo->query("SELECT id, nama FROM sumbers WHERE status=1 and tipe='KRIMINALITAS' ORDER BY nama");
                    while($s = $smbA->fetch()) {
                      echo "<option value='{$s['id']}'>".htmlspecialchars($s['nama'])."</option>";
                    }
                    ?>
                  </select>
                </div>
                <div class="mb-2">
                  <label class="form-label">State</label>
                  <select class="form-select form-select-sm" name="state" id="impA_state" required>
                    <option value="PROSES">PROSES</option>
                    <option value="SELESAI">SELESAI</option>
                  </select>
                </div>
                <div class="mb-2">
                  <label class="form-label">Sub State</label>
                  <select class="form-select form-select-sm" name="sub_state" id="impA_sub_state" required></select>
                </div>
                <div class="mb-2">
                  <label class="form-label">Tujuan</label>
                  <select class="form-select form-select-sm" name="tujuan">
                    <option value="">- Pilih Tujuan -</option>
                    <option value="DITNARKOBA">DITNARKOBA</option>
                    <option value="DITRESKRIMSUS">DITRESKRIMSUS</option>
                    <option value="DITRESKRIMUM">DITRESKRIMUM</option>
                    <option value="DITINTELKAM">DITINTELKAM</option>
                    <option value="DITPOLAIRUD">DITPOLAIRUD</option>
                  </select>
                </div>
                <div class="mb-2">
                  <label class="form-label">Penanggung Jawab</label>
                  <input type="text" class="form-control form-control-sm" name="penanggungjawab">
                </div>
                <input type="hidden" name="penyebab" value="">
              </div>
            </div>

            <hr>
            <div class="d-flex justify-content-between">
              <button type="button" class="btn btn-secondary" id="btnBackToUploadA">
                <i class="fas fa-arrow-left me-1"></i> Upload Ulang
              </button>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Simpan ke Database
              </button>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
</div>
<!-- ===================== END MODAL IMPORT LP A ===================== -->
          <div class="modal fade" id="modalImportWordB" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
              <div class="modal-content">
                <div class="modal-header bg-success text-white">
                  <h5 class="modal-title"><i class="fas fa-file-word me-2"></i>Import Laporan Polisi Model B</h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                  <!-- STEP 1: Upload File -->
                  <div id="stepUpload">
                    <div class="alert alert-info">
                      <strong><i class="fas fa-info-circle"></i> Petunjuk:</strong><br>
                      1. Upload file <strong>Laporan Polisi Model B (.docx)</strong><br>
                      2. Data akan otomatis ter-parse (No LP, Pelapor, Peristiwa, dll)<br>
                      3. Lengkapi data yang tidak ada di file (Kategori, Desa, Polres, dll)<br>
                      4. Klik <strong>Simpan</strong>
                    </div>
                    <div class="mb-3">
                      <label class="form-label fw-bold">Pilih File Laporan Polisi (.docx)</label>
                      <input type="file" class="form-control" id="fileWordImport" accept=".docx">
                    </div>
                    <button type="button" class="btn btn-success" id="btnParseWord" disabled>
                      <i class="fas fa-cog me-1"></i> Parse Dokumen
                    </button>
                    <div id="parseLoading" class="mt-2" style="display:none;">
                      <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                      <span class="ms-2">Sedang memproses file...</span>
                    </div>
                    <div id="parseError" class="alert alert-danger mt-2" style="display:none;"></div>
                  </div>

                  <!-- STEP 2: Form Lengkap (Muncul setelah parse berhasil) -->
                  <div id="stepForm" style="display:none;">
                    <div class="alert alert-success">
                      <i class="fas fa-check-circle"></i> File berhasil di-parse! Silakan periksa data di bawah dan lengkapi field yang kosong.
                    </div>

                    <form method="POST" id="formImportWord">
                      <div class="row">
                        <!-- KOLOM KIRI: Data dari File Word (otomatis) -->
                        <div class="col-md-6">
                          <h6 class="fw-bold text-success border-bottom pb-2 mb-3">
                            <i class="fas fa-file-alt me-1"></i> Data dari File Word (Otomatis)
                          </h6>
                          <div class="mb-2">
                            <label class="form-label">No. LP</label>
                            <input type="text" class="form-control form-control-sm" name="no_lp" id="imp_no_lp">
                          </div>
                          <div class="mb-2">
                            <label class="form-label">Pelapor</label>
                            <textarea class="form-control form-control-sm" name="pelapor" id="imp_pelapor" rows="5"></textarea>
                          </div>
                          <div class="mb-2">
                            <label class="form-label">Tempat Kejadian</label>
                            <textarea class="form-control form-control-sm" name="lokasi" id="imp_lokasi" rows="3"></textarea>
                          </div>
                          <div class="mb-2">
                            <label class="form-label">Apa yang Terjadi?</label>
                            <textarea class="form-control form-control-sm" name="keterangan" id="imp_keterangan" rows="2"></textarea>
                          </div>
                        
                          <div class="mb-2">
                            <label class="form-label">Terlapor</label>
                            <textarea class="form-control form-control-sm" name="terlapor" id="imp_terlapor" rows="2"></textarea>
                          </div>
                          <div class="mb-2">
                            <label class="form-label">Korban</label>
                            <textarea class="form-control form-control-sm" name="korban" id="imp_korban" rows="4"></textarea>
                          </div>
                          <div class="row">
                            <div class="col-md-6 mb-2">
                              <label class="form-label">Tanggal Kejadian</label>
                              <input type="datetime-local" class="form-control form-control-sm" name="tanggal" id="imp_tanggal">
                            </div>
                            <div class="col-md-6 mb-2">
                              <label class="form-label">Tanggal Laporan</label>
                              <input type="datetime-local" class="form-control form-control-sm" name="tanggal_laporan" id="imp_tanggal_laporan">
                            </div>
                          </div>
                          <div class="row">
                            <div class="col-md-6 mb-2">
                              <label class="form-label">Latitude</label>
                              <input type="text" class="form-control form-control-sm" name="latitude" id="imp_latitude">
                            </div>
                            <div class="col-md-6 mb-2">
                              <label class="form-label">Longitude</label>
                              <input type="text" class="form-control form-control-sm" name="longitude" id="imp_longitude">
                            </div>
                          </div>
                          <h6 class="fw-bold text-warning border-bottom pb-2 mb-3 mt-3">
                            <i class="fas fa-table me-1"></i> Data dari Tabel Bawah Dokumen
                          </h6>
                          <div class="mb-2">
                              <label class="form-label">Tindak Pidana</label>
                              <textarea class="form-control form-control-sm" name="tindak_pidana" id="imp_tindak_pidana" rows="3"></textarea>
                          </div>
                          <div class="mb-2">
                              <label class="form-label">Saksi-Saksi</label>
                              <textarea class="form-control form-control-sm" name="saksi" id="imp_saksi" rows="3"></textarea>
                          </div>
                          <div class="mb-2">
                              <label class="form-label">Barang Bukti</label>
                              <textarea class="form-control form-control-sm" name="barang_bukti" id="imp_barang_bukti" rows="3"></textarea>
                          </div>
                          <div class="mb-2">
                              <label class="form-label">Uraian Singkat</label>
                              <textarea class="form-control form-control-sm" name="uraian" id="imp_uraian" rows="5"></textarea>
                          </div>
                        </div>
                        

                        <!-- KOLOM KANAN: Data Manual (harus diisi user) -->
                        <div class="col-md-6">
                          <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                            <i class="fas fa-edit me-1"></i> Data Manual (Harus Dilengkapi)
                          </h6>
                          <div class="mb-2">
                            <label class="form-label">Jumlah</label>
                            <input type="number" class="form-control form-control-sm" value="1" name="poin" required>
                          </div>
                          <div class="mb-2">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="kategori_id" id="imp_kategori_id" required>
                              <option value="">- Pilih Kategori -</option>
                              <?php
                              $kategori = $pdo->query("SELECT id, nama FROM kriminal_kategoris WHERE status=1 ORDER BY nama");
                              while($k = $kategori->fetch()) {
                                echo "<option value='{$k['id']}'>".htmlspecialchars($k['nama'])."</option>";
                              }
                              ?>
                            </select>
                          </div>
                          <div class="mb-2">
                            <label class="form-label">Sub Kategori <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="sub_kategori_id" id="imp_sub_kategori_id" required>
                              <option value="">- Pilih Sub Kategori -</option>
                            </select>
                          </div>
                          <div class="mb-2">
                            <label class="form-label">Kabupaten <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="kabupaten_id" id="imp_kabupaten_id" required>
                              <option value="">- Pilih Kabupaten -</option>
                              <?php
                              if($akses == "POLRES") {
                                $kab2 = $pdo->prepare("SELECT id, nama, kode FROM kabupatens WHERE status=1 AND polres_id=? ORDER BY nama");
                                $kab2->execute([$polres_id]);
                              } else if($akses=="POLSEK") {
                                $kab2 = $pdo->prepare("SELECT kabupatens.* FROM kabupatens join polress on kabupatens.polres_id = polress.id join polseks on polress.id = polseks.polres_id WHERE kabupatens.status=1 AND polseks.id=? ORDER BY kabupatens.nama");
                                $kab2->execute([$polsek_id]);
                              } else {
                                $kab2 = $pdo->query("SELECT id, nama, kode FROM kabupatens WHERE status=1 ORDER BY nama");
                              }
                              while($kab = $kab2->fetch()) {
                                echo "<option value='{$kab['id']}'>".htmlspecialchars($kab['kode'])." - ".htmlspecialchars($kab['nama'])."</option>";
                              }
                              ?>
                            </select>
                          </div>
                          <div class="mb-2">
                            <label class="form-label">Kecamatan <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="kecamatan_id" id="imp_kecamatan_id" required disabled>
                              <option value="">- Pilih Kecamatan -</option>
                            </select>
                          </div>
                          <div class="mb-2">
                            <label class="form-label">Desa <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="desa_id" id="imp_desa_id" required disabled>
                              <option value="">- Pilih Desa -</option>
                            </select>
                          </div>
                          <div class="mb-2">
                            <label class="form-label">Polres <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="polres_id" id="imp_polres_id" required>
                              <option value="">- Pilih Polres -</option>
                              <?php
                              if($akses == "POLRES") {
                                $pol2 = $pdo->prepare("SELECT id, nama FROM polress WHERE status=1 AND id=? ORDER BY nama");
                                $pol2->execute([$polres_id]);
                              } else if($akses == "POLSEK") {
                                $pol2 = $pdo->prepare("SELECT polress.id, polress.nama FROM polress left join polseks on polress.id = polseks.polres_id WHERE polress.status=1 AND polseks.id=? ORDER BY polress.nama");
                                $pol2->execute([$polsek_id]);
                              } else {
                                $pol2 = $pdo->query("SELECT id, nama FROM polress WHERE status=1 ORDER BY nama");
                              }
                              while($p = $pol2->fetch()) {
                                echo "<option value='{$p['id']}'>".htmlspecialchars($p['nama'])."</option>";
                              }
                              ?>
                            </select>
                          </div>
                          <div class="mb-2">
                            <label class="form-label">Polsek</label>
                            <select class="form-select form-select-sm" name="polsek_id" id="imp_polsek_id" disabled>
                              <option value="">- Pilih Polsek -</option>
                            </select>
                          </div>
                          <div class="mb-2">
                            <label class="form-label">Jenis Lokasi</label>
                            <select class="form-select form-select-sm" name="jenis_tkp_id">
                              <option value="">- Pilih Jenis Lokasi -</option>
                              <?php
                              $jl2 = $pdo->query("SELECT id, nama FROM jenis_tkps WHERE status=1 ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
                              foreach($jl2 as $jl) {
                                echo "<option value='{$jl['id']}'>".htmlspecialchars($jl['nama'])."</option>";
                              }
                              ?>
                            </select>
                          </div>
                          <div class="mb-2">
                            <label class="form-label">Sumber Dokumen <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="sumber_id" required>
                              <option value="">- Pilih Sumber -</option>
                              <?php
                              $smb2 = $pdo->query("SELECT id, nama FROM sumbers WHERE status=1 and tipe='KRIMINALITAS' ORDER BY nama");
                              while($s = $smb2->fetch()) {
                                echo "<option value='{$s['id']}'>".htmlspecialchars($s['nama'])."</option>";
                              }
                              ?>
                            </select>
                          </div>
                          <div class="mb-2">
                            <label class="form-label">State</label>
                            <select class="form-select form-select-sm" name="state" id="imp_state" required>
                              <option value="PROSES">PROSES</option>
                              <option value="SELESAI">SELESAI</option>
                            </select>
                          </div>
                          <div class="mb-2">
                            <label class="form-label">Sub State</label>
                            <select class="form-select form-select-sm" name="sub_state" id="imp_sub_state" required></select>
                          </div>
                          <div class="mb-2">
                            <label class="form-label">Tujuan</label>
                            <select class="form-select form-select-sm" name="tujuan">
                              <option value="">- Pilih Tujuan -</option>
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
                          <div class="mb-2">
                            <label class="form-label">Penanggung Jawab</label>
                            <input type="text" class="form-control form-control-sm" name="penanggungjawab">
                          </div>
 
                        </div>
                      </div>

                      <hr>
                      <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" id="btnBackToUpload">
                          <i class="fas fa-arrow-left me-1"></i> Upload Ulang
                        </button>
                        <button type="submit" class="btn btn-primary">
                          <i class="fas fa-save me-1"></i> Simpan ke Database
                        </button>
                      </div>
                    </form>
                  </div>

                </div>
              </div>
            </div>
          </div>
          <!-- Modal Tambah Data kriminalitas -->
          <div class="modal fade" id="modalTambahkriminalitas" tabindex="-1" aria-labelledby="modalTambahkriminalitasLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <form method="POST" id="formTambahkriminalitas">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahkriminalitasLabel">Tambah Data kriminalitas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label class="form-label">Jumlah</label>
                      <input type="number" class="form-control form-control-sm" value="1" name="poin" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Kategori</label>
                      <select class="form-select form-select-sm" name="kategori_id" id="kategori_id" required>
                        <option value="">- Pilih Kategori -</option>
                        <?php
                        $kategori = $pdo->query("SELECT id, nama FROM kriminal_kategoris WHERE status=1 ORDER BY nama");
                        while($k = $kategori->fetch()) {
                          echo "<option value='{$k['id']}'>".htmlspecialchars($k['nama'])."</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Sub Kategori</label>
                      <select class="form-select form-select-sm" name="sub_kategori_id" id="sub_kategori_id" required>
                        <option value="">- Pilih Sub Kategori -</option>
                        
                      </select>
                    </div>
                     
                    <div class="mb-3">
                      <label class="form-label">Kabupaten</label>
                      <select class="form-select form-select-sm" name="kabupaten_id" id="kabupaten_id" required>
                        <option value="">- Pilih Kabupaten -</option>
                        <?php
                        if($akses == "POLRES") {
                          $kabupaten = $pdo->prepare("SELECT id, nama, kode FROM kabupatens WHERE status=1 AND polres_id=? ORDER BY nama");
                          $kabupaten->execute([$polres_id]);
                        }else if($akses=="POLSEK"){
                          $kabupaten = $pdo->prepare("SELECT kabupatens.* 
                          FROM kabupatens 
                          join polress on kabupatens.polres_id = polress.id
                          join polseks on polress.id = polseks.polres_id
                          WHERE kabupatens.status=1 AND polseks.id=? ORDER BY kabupatens.nama");
                          $kabupaten->execute([$polsek_id]);
                        }else{
                          $kabupaten = $pdo->query("SELECT id, nama, kode FROM kabupatens WHERE status=1 ORDER BY nama");
                        }
                          while($kab = $kabupaten->fetch()) {
                            echo "<option value='{$kab['id']}'>".htmlspecialchars($kab['kode'])." - ".htmlspecialchars($kab['nama'])."</option>";
                          }
                        ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Kecamatan</label>
                      <select class="form-select form-select-sm" name="kecamatan_id" id="kecamatan_id" required disabled>
                        <option value="">- Pilih Kecamatan -</option>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Desa</label>
                      <select class="form-select form-select-sm" name="desa_id" id="desa_id" required disabled>
                        <option value="">- Pilih Desa -</option>
                      </select>
                    </div>
                    <!-- Polres & Polsek Cascade -->
                    <div class="mb-3">
                      <label class="form-label">Polres</label>
                      <select class="form-select form-select-sm" name="polres_id" id="polres_id" required>
                        <option value="">- Pilih Polres -</option>
                        <?php
                        if($akses == "POLRES") {
                          $polres = $pdo->prepare("SELECT id, nama FROM polress WHERE status=1 AND id=? ORDER BY nama");
                          $polres->execute([$polres_id]);
                        } else if($akses == "POLSEK") {
                          $polres = $pdo->prepare("SELECT polress.id, polress.nama FROM polress left join polseks on polress.id = polseks.polres_id WHERE polress.status=1 AND polseks.id=? ORDER BY polress.nama");
                          $polres->execute([$polsek_id]);
                        } else {
                        $polres = $pdo->query("SELECT id, nama FROM polress WHERE status=1 ORDER BY nama");
                        }
                        while($p = $polres->fetch()) {
                          echo "<option value='{$p['id']}'> ".htmlspecialchars($p['nama'])."</option>";
                        }
                        ?>
                      </select>
                    </div>
                  
                    <div class="mb-3">
                      <label class="form-label">Polsek</label>
                      <select class="form-select form-select-sm" name="polsek_id" id="polsek_id" disabled>
                        <option value="">- Pilih Polsek -</option>
                      </select>
                    </div>
                   
                    <div class="mb-3">
                      <label class="form-label">No. LP</label>
                      <input type="text" class="form-control form-control-sm" name="no_lp" >
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Jenis Lokasi</label>
                      <select class="form-select form-select-sm" name="jenis_tkp_id">
                        <option value="">- Pilih Jenis Lokasi -</option>
                        <?php
                        $jenis_lokasi = $pdo->query("SELECT id, nama FROM jenis_tkps WHERE status=1 ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
                        foreach($jenis_lokasi as $jl) {
                          echo "<option value='{$jl['id']}'>".htmlspecialchars($jl['nama'])."</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Tempat Kejadian</label>
                      <textarea class="form-control form-control-sm" name="lokasi" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tentukan Lokasi di Map</label>
                        <div id="kriminalitasMapTambah" style="height: 350px; border:1px solid #ccc"></div>
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
                      <label class="form-label">Tanggal Kejadian</label>
                      <input type="datetime-local" class="form-control form-control-sm" name="tanggal">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Pelapor</label>
                      <textarea class="form-control form-control-sm" name="pelapor" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Apa yang terjadi?</label>
                      <textarea class="form-control form-control-sm" name="keterangan" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Terlapor</label>
                      <textarea class="form-control form-control-sm" name="terlapor" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Korban</label>
                      <textarea class="form-control form-control-sm" name="korban" rows="3"></textarea>
                    </div>
                     <div class="mb-3">
                      <label class="form-label">Tanggal Laporan</label>
                      <input type="datetime-local" class="form-control form-control-sm" name="tanggal_laporan">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Tindak Pidana</label>
                      <textarea class="form-control form-control-sm" name="tindak_pidana" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Saksi</label>
                      <textarea class="form-control form-control-sm" name="saksi" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Barang Bukti</label>
                      <textarea class="form-control form-control-sm" name="barang_bukti" rows="3"></textarea>
                    </div>
                     <div class="mb-3">
                      <label class="form-label">Uraian Singkat</label>
                      <textarea class="form-control form-control-sm" name="uraian" rows="5"></textarea>
                    </div>
                     
                    <div class="mb-3">
                      <label class="form-label">Sumber Dokumen</label>
                      <select class="form-select form-select-sm" name="sumber_id" required>
                        <option value="">- Pilih Sumber -</option>
                        <?php
                        $sumber = $pdo->query("SELECT id, nama FROM sumbers WHERE status=1 and tipe='KRIMINALITAS' ORDER BY nama");
                        while($s = $sumber->fetch()) {
                          echo "<option value='{$s['id']}'>".htmlspecialchars($s['nama'])."</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">State</label>
                      <select class="form-select form-select-sm" name="state" id="state" required>
                        <option value="PROSES">PROSES</option>
                        <option value="SELESAI">SELESAI</option>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Sub State</label>
                      <select class="form-select form-select-sm" name="sub_state" id="sub_state" required>
                        <!-- Pilihan sub_state nanti terisi via JS -->
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Tujuan</label>
                      <select class="form-select form-select-sm" name="tujuan" >
                        <option value="" selected>- Pilih Tujuan -</option>
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
                    <div class="mb-3">
                      <label class="form-label">Penanggung Jawab</label>
                      <input type="text" class="form-control form-control-sm" name="penanggungjawab" >
                    </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                  </div>
                </div>
              </form>
            </div>
          </div>
          <?php // include_once("footer.php") ?>
        </div>
      </div>
    </main>
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
    $(document).ready(function() {
      $('#kriminalitasTable').DataTable({
        "autoWidth": false,
        "order": [[ 0, "desc" ]],
        "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
        initComplete: function () {
            this.api().columns([2,3,5,6,8,9]).every( function () {
              var column = this;
              var select = $('<select class="form-control form-control-sm"><option value="">- Semua -</option></select>')
                .appendTo( $(column.footer()).empty() )
                .on( 'change', function () {
                  var val = $.fn.dataTable.util.escapeRegex($(this).val());
                  column
                    .search( val ? '^'+val+'$' : '', true, false )
                    .draw();
                } );
              // Populate options: ambil unik dan urut
              column.data().unique().sort().each( function ( d, j ) {
                if(d) select.append( '<option value="'+d+'">'+d+'</option>' );
              });
            });
          }
        
      });
    });
     $('#kategori_id').change(function() {
      var kategoriId = $(this).val();
      $('#sub_kategori_id').prop('disabled', true).html('<option value="">- Pilih Sub Kategori -</option>');
      if(kategoriId) {
        $.get('get_sub_kategori.php', {kategori_id: kategoriId}, function(data) {
          var opt = '<option value="">- Pilih Sub Kategori -</option>';
          $.each(data, function(i, v) { opt += '<option value="'+v.id+'">'+v.nama+'</option>'; });
          $('#sub_kategori_id').html(opt).prop('disabled', false);
        }, 'json');
      }
    });
     $('#kabupaten_id').change(function() {
      var kabupatenId = $(this).val();
      var polsekId = <?php echo json_encode($_SESSION["polsek_id"] ?? ''); ?>;
      $('#kecamatan_id').prop('disabled', true).html('<option value="">- Pilih Kecamatan -</option>');
      $('#desa_id').prop('disabled', true).html('<option value="">- Pilih Desa -</option>');
      if(kabupatenId) {
        $.get('get_kecamatan.php', {kabupaten_id: kabupatenId, polsek_id: polsekId}, function(data) {
          var opt = '<option value="">- Pilih Kecamatan -</option>';
          var firstId = ""; // simpan id kecamatan pertama
          $.each(data, function(i, v) {
            if(i === 0) firstId = v.id;
            opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>';
          });
          $('#kecamatan_id').html(opt).prop('disabled', false);
          // **Pilih otomatis kecamatan pertama jika ada**
          if(firstId) {
            $('#kecamatan_id').val(firstId).trigger('change');
          }
        }, 'json');
      }
    });
    $('#kecamatan_id').change(function() {
      var kecamatanId = $(this).val();
      $('#desa_id').prop('disabled', true).html('<option value="">- Pilih Desa -</option>');
      if(kecamatanId) {
        $.get('get_desa.php', {kecamatan_id: kecamatanId}, function(data) {
          var opt = '<option value="">- Pilih Desa -</option>';
          var firstId = "";
          $.each(data, function(i, v) {
            if(i === 0) firstId = v.id;
            opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>';
          });
          $('#desa_id').html(opt).prop('disabled', false);
          // **Pilih otomatis desa pertama jika ada**
          if(firstId) {
            $('#desa_id').val(firstId);
          }
        }, 'json');
      }
    });
    // Polres → Polsek
    $('#polres_id').change(function() {
      var polresId = $(this).val();
      var polsekId = <?php echo json_encode($_SESSION["polsek_id"] ?? ''); ?>;
      $('#polsek_id').prop('disabled', true).html('<option value="">- Pilih Polsek -</option>');
      if(polresId) {
        $.get('get_polsek.php', {polres_id: polresId, polsek_id: polsekId}, function(data) {
          var opt = '<option value="">- Pilih Polsek -</option>';
          var firstId = "";
          $.each(data, function(i, v) {
            if(i === 0) firstId = v.id;
            opt += '<option value="'+v.id+'">'+v.nama+'</option>';
          });
          $('#polsek_id').html(opt).prop('disabled', false);
          // **Pilih otomatis polsek pertama jika ada**
          if(firstId) {
            $('#polsek_id').val(firstId);
          }
        }, 'json');
      }
    });
    // ============================================
// CASCADE: Modal Import LP A
// ============================================
$('#impA_kategori_id').change(function() {
    var kategoriId = $(this).val();
    $('#impA_sub_kategori_id').prop('disabled', true).html('<option value="">- Pilih Sub Kategori -</option>');
    if(kategoriId) {
        $.get('get_sub_kategori.php', {kategori_id: kategoriId}, function(data) {
            var opt = '<option value="">- Pilih Sub Kategori -</option>';
            $.each(data, function(i, v) { opt += '<option value="'+v.id+'">'+v.nama+'</option>'; });
            $('#impA_sub_kategori_id').html(opt).prop('disabled', false);
        }, 'json');
    }
});

$('#impA_kabupaten_id').change(function() {
    var kabupatenId = $(this).val();
    var polsekId = <?php echo json_encode($_SESSION["polsek_id"] ?? ''); ?>;
    $('#impA_kecamatan_id').prop('disabled', true).html('<option value="">- Pilih Kecamatan -</option>');
    $('#impA_desa_id').prop('disabled', true).html('<option value="">- Pilih Desa -</option>');
    if(kabupatenId) {
        $.get('get_kecamatan.php', {kabupaten_id: kabupatenId, polsek_id: polsekId}, function(data) {
            var opt = '<option value="">- Pilih Kecamatan -</option>';
            var firstId = "";
            $.each(data, function(i, v) { if(i===0) firstId=v.id; opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>'; });
            $('#impA_kecamatan_id').html(opt).prop('disabled', false);
            if(firstId) $('#impA_kecamatan_id').val(firstId).trigger('change');
        }, 'json');
    }
});

$('#impA_kecamatan_id').change(function() {
    var kecamatanId = $(this).val();
    $('#impA_desa_id').prop('disabled', true).html('<option value="">- Pilih Desa -</option>');
    if(kecamatanId) {
        $.get('get_desa.php', {kecamatan_id: kecamatanId}, function(data) {
            var opt = '<option value="">- Pilih Desa -</option>';
            var firstId = "";
            $.each(data, function(i, v) { if(i===0) firstId=v.id; opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>'; });
            $('#impA_desa_id').html(opt).prop('disabled', false);
            if(firstId) $('#impA_desa_id').val(firstId);
        }, 'json');
    }
});

$('#impA_polres_id').change(function() {
    var polresId = $(this).val();
    var polsekId = <?php echo json_encode($_SESSION["polsek_id"] ?? ''); ?>;
    $('#impA_polsek_id').prop('disabled', true).html('<option value="">- Pilih Polsek -</option>');
    if(polresId) {
        $.get('get_polsek.php', {polres_id: polresId, polsek_id: polsekId}, function(data) {
            var opt = '<option value="">- Pilih Polsek -</option>';
            var firstId = "";
            $.each(data, function(i, v) { if(i===0) firstId=v.id; opt += '<option value="'+v.id+'">'+v.nama+'</option>'; });
            $('#impA_polsek_id').html(opt).prop('disabled', false);
            if(firstId) $('#impA_polsek_id').val(firstId);
        }, 'json');
    }
});

  
    // ============================================
      // CASCADE: Modal Import Word (BARU)
      // ============================================
      $('#imp_kategori_id').change(function() {
        var kategoriId = $(this).val();
        $('#imp_sub_kategori_id').prop('disabled', true).html('<option value="">- Pilih Sub Kategori -</option>');
        if(kategoriId) {
          $.get('get_sub_kategori.php', {kategori_id: kategoriId}, function(data) {
            var opt = '<option value="">- Pilih Sub Kategori -</option>';
            $.each(data, function(i, v) { opt += '<option value="'+v.id+'">'+v.nama+'</option>'; });
            $('#imp_sub_kategori_id').html(opt).prop('disabled', false);
          }, 'json');
        }
      });

      $('#imp_kabupaten_id').change(function() {
        var kabupatenId = $(this).val();
        var polsekId = <?php echo json_encode($_SESSION["polsek_id"] ?? ''); ?>;
        $('#imp_kecamatan_id').prop('disabled', true).html('<option value="">- Pilih Kecamatan -</option>');
        $('#imp_desa_id').prop('disabled', true).html('<option value="">- Pilih Desa -</option>');
        if(kabupatenId) {
          $.get('get_kecamatan.php', {kabupaten_id: kabupatenId, polsek_id: polsekId}, function(data) {
            var opt = '<option value="">- Pilih Kecamatan -</option>';
            var firstId = "";
            $.each(data, function(i, v) { if(i===0) firstId=v.id; opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>'; });
            $('#imp_kecamatan_id').html(opt).prop('disabled', false);
            if(firstId) $('#imp_kecamatan_id').val(firstId).trigger('change');
          }, 'json');
        }
      });

      $('#imp_kecamatan_id').change(function() {
        var kecamatanId = $(this).val();
        $('#imp_desa_id').prop('disabled', true).html('<option value="">- Pilih Desa -</option>');
        if(kecamatanId) {
          $.get('get_desa.php', {kecamatan_id: kecamatanId}, function(data) {
            var opt = '<option value="">- Pilih Desa -</option>';
            var firstId = "";
            $.each(data, function(i, v) { if(i===0) firstId=v.id; opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>'; });
            $('#imp_desa_id').html(opt).prop('disabled', false);
            if(firstId) $('#imp_desa_id').val(firstId);
          }, 'json');
        }
      });

      $('#imp_polres_id').change(function() {
        var polresId = $(this).val();
        var polsekId = <?php echo json_encode($_SESSION["polsek_id"] ?? ''); ?>;
        $('#imp_polsek_id').prop('disabled', true).html('<option value="">- Pilih Polsek -</option>');
        if(polresId) {
          $.get('get_polsek.php', {polres_id: polresId, polsek_id: polsekId}, function(data) {
            var opt = '<option value="">- Pilih Polsek -</option>';
            var firstId = "";
            $.each(data, function(i, v) { if(i===0) firstId=v.id; opt += '<option value="'+v.id+'">'+v.nama+'</option>'; });
            $('#imp_polsek_id').html(opt).prop('disabled', false);
            if(firstId) $('#imp_polsek_id').val(firstId);
          }, 'json');
        }
      });
      $('#fileWordImport').change(function() {
        $('#btnParseWord').prop('disabled', !this.files.length);
        // Reset
        $('#stepForm').hide();
        $('#stepUpload').show();
        $('#parseError').hide();
      });

      $('#btnParseWord').click(function() {
        var fileInput = $('#fileWordImport')[0];
        if(!fileInput.files.length) return;

        var formData = new FormData();
        formData.append('file_word', fileInput.files[0]);

        $('#parseLoading').show();
        $('#parseError').hide();
        $('#btnParseWord').prop('disabled', true);

        $.ajax({
          url: 'parse_word_lp.php',
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          dataType: 'json',
          success: function(response) {
            $('#parseLoading').hide();
            $('#btnParseWord').prop('disabled', false);

            if(response.error) {
              $('#parseError').text(response.error).show();
              return;
            }

            if(response.success && response.data) {
              var d = response.data;

              // Isi field otomatis dari hasil parse
              $('#imp_no_lp').val(d.no_lp || '');
              $('#imp_pelapor').val(d.pelapor || '');
              $('#imp_lokasi').val(d.tempat_kejadian || '');
              $('#imp_keterangan').val(d.apa_yang_terjadi || '');
              $('#imp_tindak_pidana').val(d.tindak_pidana || '');
              $('#imp_terlapor').val(d.terlapor || '');
              $('#imp_korban').val(d.korban || '');
              $('#imp_latitude').val(d.latitude || '');
              $('#imp_longitude').val(d.longitude || '');
              $('#imp_saksi').val(d.saksi || '');
              $('#imp_barang_bukti').val(d.barang_bukti || '');
              $('#imp_uraian').val(d.uraian || '');

              // Tanggal laporan
              if(d.tanggal_laporan_formatted) {
                $('#imp_tanggal_laporan').val(d.tanggal_laporan_formatted);
              }

              // Tampilkan form
              $('#stepUpload').hide();
              $('#stepForm').show();
            }
          },
          error: function(xhr, status, errorMsg) {
            $('#parseLoading').hide();
            $('#btnParseWord').prop('disabled', false);

            var detail = 'Terjadi kesalahan saat memproses file.';
            try {
                var resp = JSON.parse(xhr.responseText);
                if(resp.error) {
                    detail = resp.error;
                    if(resp.file) detail += '\nFile: ' + resp.file;
                    if(resp.line) detail += '\nLine: ' + resp.line;
                }
            } catch(e) {
                if(xhr.responseText) {
                    detail += '\n\nServer response:\n' + xhr.responseText.substring(0, 500);
                }
                detail += '\nHTTP Status: ' + status + ' ' + errorMsg;
            }

            $('#parseError').html('<strong>Error:</strong><pre style="white-space:pre-wrap;font-size:12px;margin-top:5px;">' + 
                $('<span>').text(detail).html() + '</pre>').show();
        }
        });
      });

      // Tombol Upload Ulang
      $('#btnBackToUpload').click(function() {
        $('#stepForm').hide();
        $('#stepUpload').show();
        $('#fileWordImport').val('');
        $('#btnParseWord').prop('disabled', true);
      });

    // Hapus kriminalitas
    $(document).on('click', '.btnHapuskriminalitas', function() {
      var id = $(this).data('id');
      var subKategori = $(this).data('sub-kategori');
      $('#hapus_id_kriminalitas').val(id);
      $('#modalHapuskriminalitas').modal('show');
    });
     const subStateOptions = {
      "PROSES": [
        {value:"PROSES LIDIK", label:"PROSES LIDIK"},
        {value:"PROSES SIDIK", label:"PROSES SIDIK"}
      ],
      "SELESAI": [
        {value:"P21", label:"P21"},
        {value:"SP3 - TDK CUKUP BUKTI", label:"SP3 - TDK CUKUP BUKTI"},
        {value:"SP3 - BUKAN PKR PIDANA", label:"SP3 - BUKAN PKR PIDANA"},
        {value:"SP3 - ADUAN DICABUT", label:"SP3 - ADUAN DICABUT"},
        {value:"SP3 - NEBIS IN IDEM", label:"SP3 - NEBIS IN IDEM"},
        {value:"SP3 - TSK MATI", label:"SP3 - TSK MATI"},
        {value:"SP3 - TSK GILA", label:"SP3 - TSK GILA"},
        {value:"SP3 - KADALUARSA/LIMPAH", label:"SP3 - KADALUARSA/LIMPAH"},
        {value:"DILIMPAHKAN INSTANSI LAIN", label:"DILIMPAHKAN INSTANSI LAIN"},
        {value:"RESORATIF JUSTICE", label:"RESORATIF JUSTICE"}
      ]
    };
    function updateImportSubState() {
        var state = $('#imp_state').val();
        var sel = $('#imp_sub_state');
        sel.empty();
        if(subStateOptions[state]) {
          subStateOptions[state].forEach(function(opt) {
            sel.append('<option value="'+opt.value+'">'+opt.label+'</option>');
          });
        }
      }
      updateImportSubState();
      $('#imp_state').change(updateImportSubState);
    function updateSubState() {
      const state = document.getElementById('state').value;
      const subStateSel = document.getElementById('sub_state');
      subStateSel.innerHTML = ""; // clear opsi lama
      if (subStateOptions[state]) {
        subStateOptions[state].forEach(opt => {
          const o = document.createElement('option');
          o.value = opt.value; o.textContent = opt.label;
          subStateSel.appendChild(o);
        });
      } else {
        subStateSel.innerHTML = '<option value="">- tidak tersedia -</option>';
      }
    }

    // Onload & onChange state
    document.addEventListener('DOMContentLoaded', updateSubState);
    document.getElementById('state').addEventListener('change', updateSubState);
    // ============================================
  // IMPORT LP A: Parse File
  // ============================================
  $('#fileWordImportA').change(function() {
      $('#btnParseWordA').prop('disabled', !this.files.length);
      $('#stepFormA').hide();
      $('#stepUploadA').show();
      $('#parseErrorA').hide();
  });

  $('#btnParseWordA').click(function() {
      var fileInput = $('#fileWordImportA')[0];
      if(!fileInput.files.length) return;

      var formData = new FormData();
      formData.append('file_word', fileInput.files[0]);

      $('#parseLoadingA').show();
      $('#parseErrorA').hide();
      $('#btnParseWordA').prop('disabled', true);

      $.ajax({
          url: 'parse_word_lp_a.php',
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          dataType: 'json',
          success: function(response) {
              $('#parseLoadingA').hide();
              $('#btnParseWordA').prop('disabled', false);

              if(response.error) {
                  $('#parseErrorA').text(response.error).show();
                  return;
              }

              if(response.success && response.data) {
                  var d = response.data;
                  console.log(d);
                  // Data otomatis
                  $('#impA_no_lp').val(d.no_lp || '');
                  $('#impA_lokasi').val(d.tempat_kejadian || '');
                  $('#impA_keterangan').val(d.apa_yang_terjadi || '');
                  $('#impA_terlapor').val(d.terlapor || '');
                  $('#impA_korban').val(d.korban || '');
                  $('#impA_latitude').val(d.latitude || '');
                  $('#impA_longitude').val(d.longitude || '');
                  $('#impA_pelapor').val(d.pelapor || '');

                   
                  if(d.tanggal_formatted) {
                      $('#impA_tanggal').val(d.tanggal_formatted);
                  }

                  // Tanggal laporan
                  if(d.tanggal_laporan_formatted) {
                      $('#impA_tanggal_laporan').val(d.tanggal_laporan_formatted);
                  }

                  // Bagaimana terjadi → uraian kronologi
                  // Gabungkan "bagaimana_terjadi" + "uraian" dari tabel bawah
                  var kronologi = d.bagaimana_terjadi || '';
                  $('#impA_uraian_kronologi').val(kronologi);

                  // Tabel bawah
                  $('#impA_tindak_pidana').val(d.tindak_pidana || '');
                  $('#impA_saksi').val(d.saksi || '');
                  $('#impA_barang_bukti').val(d.barang_bukti || '');
                  $('#impA_uraian_singkat').val(d.uraian || '');

                  // Tampilkan form
                  $('#stepUploadA').hide();
                  $('#stepFormA').show();
              }
          },
          error: function(xhr, status, errorMsg) {
            $('#parseLoadingA').hide();
            $('#btnParseWordA').prop('disabled', false);

            var detail = 'Terjadi kesalahan saat memproses file.';
            try {
                var resp = JSON.parse(xhr.responseText);
                if(resp.error) {
                    detail = resp.error;
                    if(resp.file) detail += '\nFile: ' + resp.file;
                    if(resp.line) detail += '\nLine: ' + resp.line;
                }
            } catch(e) {
                if(xhr.responseText) {
                    detail += '\n\nServer response:\n' + xhr.responseText.substring(0, 500);
                }
                detail += '\nHTTP Status: ' + status + ' ' + errorMsg;
            }

            $('#parseErrorA').html('<strong>Error:</strong><pre style="white-space:pre-wrap;font-size:12px;margin-top:5px;">' + 
                $('<span>').text(detail).html() + '</pre>').show();
          }
      });
  });

  // Tombol Upload Ulang
  $('#btnBackToUploadA').click(function() {
      $('#stepFormA').hide();
      $('#stepUploadA').show();
      $('#fileWordImportA').val('');
      $('#btnParseWordA').prop('disabled', true);
  });

  // Sub State LP A
  function updateImportASubState() {
      var state = $('#impA_state').val();
      var sel = $('#impA_sub_state');
      sel.empty();
      if(subStateOptions[state]) {
          subStateOptions[state].forEach(function(opt) {
              sel.append('<option value="'+opt.value+'">'+opt.label+'</option>');
          });
      }
  }
  updateImportASubState();
  $('#impA_state').change(updateImportASubState);
     var map, marker;
    var mapTambah, mapEdit, markerTambah, markerEdit;
    function initMapTambah(lat, lng) {
      if(!mapTambah) {
        mapTambah = L.map('kriminalitasMapTambah').setView([lat, lng], 8);
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
          //markerTambah = L.marker(e.latlng).addTo(mapTambah);
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
     // Modal Tambah
    $('#modalTambahkriminalitas').on('shown.bs.modal', function (e) {
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
    $('#modalTambahkriminalitas').on('hidden.bs.modal', function(){
      if(mapTambah){ mapTambah.remove(); mapTambah = null; markerTambah = null; }
      $('#kriminalitasMapTambah').html('');
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