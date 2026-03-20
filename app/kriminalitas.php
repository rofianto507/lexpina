<?php
session_start();
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://*.tile.openstreetmap.org;");
include("../config/configuration.php");
require_once("log_helper.php");
if(!isset($_SESSION["id"]) || !isset($_SESSION["nama"])) {
  header("Location: ../index");
  exit;
}
if(empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
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
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
      ) {
        header("Location: kriminalitas?status=csrf_failed");
        exit;
    }
  }
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
      $tanggal_selesai = !empty($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : null;

    if($sub_kategori_id && $desa_id && $polres_id ){
        try {
            $stmt = $pdo->prepare("INSERT INTO kriminals (sub_kategori_id, desa_id, polres_id, polsek_id, sumber_id, user_id, tanggal, keterangan, 
            penanggungjawab, state, sub_state, poin, status, created_at, no_lp, jenis_tkp_id, lokasi, penyebab, tujuan, latitude, longitude, pelapor, terlapor, korban, tanggal_laporan, tindak_pidana, saksi, barang_bukti, uraian, tanggal_selesai)
                VALUES (:sub_kategori_id, :desa_id, :polres_id, :polsek_id, :sumber_id, :user_id, :tanggal, :keterangan, :penanggungjawab, 
                :state, :sub_state, :poin, 1, NOW(), :no_lp, :jenis_tkp_id, :lokasi, :penyebab, :tujuan, :latitude, :longitude, :pelapor, :terlapor, :korban, :tanggal_laporan, :tindak_pidana, :saksi, :barang_bukti, :uraian, :tanggal_selesai)");
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
                ':uraian'=>$uraian,
                ':tanggal_selesai'=>$tanggal_selesai
            ]);
            $data_id = $pdo->lastInsertId();
            logUser($pdo, $id, 'add', 'kriminalitas', $data_id, 'Tambah oleh '.$username);
            header("Location: kriminalitas?status=sukses");
            exit;
        } catch (PDOException $e) {
            // Simpan error log ke file
            $logDir = __DIR__ . '/../logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);
            }
            $logFile = $logDir . '/kriminalitas_error.log';
            $logMessage = "[" . date('Y-m-d H:i:s') . "] Error tambah kriminalitas oleh $username: " . $e->getMessage() . "\n";
            file_put_contents($logFile, $logMessage, FILE_APPEND);
            
            header("Location: kriminalitas?status=error&log=" . urlencode($e->getMessage()));
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
    <link rel="stylesheet" type="text/css" href="../vendors/datatables/datatables.min.css"/>
    <link rel="stylesheet" type="text/css" href="../assets/icon/font-awesome/css/font-awesome.min.css">
    <link href="../vendors/prism/prism-okaidia.css" rel="stylesheet">
     <link href="../vendors/overlayscrollbars/OverlayScrollbars.min.css" rel="stylesheet">
    <link href="../assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
    <link href="../assets/css/theme.min.css" rel="stylesheet" id="style-default">
    <link href="../assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
    <link href="../assets/css/user.min.css" rel="stylesheet" id="user-style-default">
    <link rel="stylesheet" href="../assets/css/kriminalitas.css">
    <link href="../vendors/choices/choices.min.css" rel="stylesheet" />
    <script src="../vendors/choices/choices.min.js"></script>
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
            Data kriminalitas berhasil ditambahkan.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          <?php if(isset($_GET['status']) && $_GET['status']=='edit_sukses'): ?>
          <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            Data kriminalitas berhasil diedit.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          
          <?php if(isset($_GET['status']) && $_GET['status']=='hapus_sukses'): ?>
          <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            Data kriminalitas berhasil dihapus.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
           <?php if(isset($_GET['status']) && $_GET['status']=='error'): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
              Terjadi kesalahan saat menyimpan data kriminalitas. Silakan coba lagi. Jika masalah berlanjut, hubungi administrator.
              <br>
              <strong>Detail Kesalahan:</strong> <?php echo htmlspecialchars($_GET['log']); ?>
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
                    <div class="col-md-auto p-3" id="card-stats">
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
                        <div class="col-lg-4 border-lg-end border-bottom border-lg-0 pb-3 pb-lg-0">
                          <div class="d-flex flex-between-center mb-3">
                            <div class="d-flex align-items-center">
                              <div class="icon-item icon-item-sm bg-soft-warning shadow-none me-2 bg-soft-warning"><span class="fs--2 fa fa-file-word-o text-warning"></span></div>
                              <h6 class="mb-0" id="stat-label-1">Label 1</h6>
                            </div>                               
                          </div>
                          <div class="d-flex">
                            <div class="d-flex">
                              <p class="font-sans-serif lh-1 mb-1 fs-4 pe-2" id="stat-value-1">0</p>                    
                            </div>                                 
                          </div>
                        </div>
                        <div class="col-lg-4 border-lg-end border-bottom border-lg-0 pb-3 pb-lg-0">
                          <div class="d-flex flex-between-center mb-3">
                            <div class="d-flex align-items-center">
                              <div class="icon-item icon-item-sm bg-soft-success shadow-none me-2 bg-soft-success"><span class="fs--2 fa fa-file-word-o text-success"></span></div>
                              <h6 class="mb-0" id="stat-label-2">Label 2</h6>
                            </div>
                          </div>
                          <div class="d-flex">
                            <div class="d-flex">
                              <p class="font-sans-serif lh-1 mb-1 fs-4 pe-2" id="stat-value-2">0</p>                    
                            </div>                                 
                          </div>
                        </div>
                        <div class="col-lg-4 border-lg-end border-bottom border-lg-0 pb-3 pb-lg-0">
                          <div class="d-flex flex-between-center mb-3">
                            <div class="d-flex align-items-center">
                              <div class="icon-item icon-item-sm bg-soft-secondary shadow-none me-2 bg-soft-secondary"><span class="fs--2 fa fa-question-circle text-secondary"></span></div>
                              <h6 class="mb-0" id="stat-label-3">Label 3</h6>
                            </div>                  
                          </div>
                          <div class="d-flex">
                            <div class="d-flex">
                              <p class="font-sans-serif lh-1 mb-1 fs-4 pe-2" id="stat-value-3">0</p>                    
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
                  <h5 class="fs-0 mb-0"><span class="fa fa-user-secret me-2 fs-0"></span>Data Kriminalitas</h5>  
                </div>
                <div class="col-auto ms-auto">
                    <button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#modalImportWordA">
                    <i class="fa fa-file-word-o me-1"></i> Import LP A
                  </button>
                   <button class="btn btn-sm btn-success me-1" data-bs-toggle="modal" data-bs-target="#modalImportWordB">
                    <i class="fa fa-file-word-o me-1"></i> Import LP B
                  </button>
                
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahkriminalitas">
                  Tambah Data
                </button>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="kriminalitasTable" class="display table table-striped table-bordered table-sm">
                  <thead class="bg-primary text-white">
                    <tr>
                      <th>ID</th>
                      <th>No LP</th>
                      <th>Kategori</th>
                      <th>Sub Kategori</th>
                      <th>Tempat Kejadian</th>    
                       <th>Tanggal Kejadian</th>          
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
                      k.tanggal,
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
                      k.tanggal,
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
                      k.tanggal,
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
                      k.tanggal,
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
                        <td>".mb_strimwidth($row['lokasi']?? '', 0, 100, '...')."</td>
                        <td>".$row['tanggal']."</td>
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
                  <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
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
        <h5 class="modal-title"><i class="fa fa-file-word-o me-2"></i>Import Laporan Polisi Model A</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <!-- STEP 1: Upload -->
        <div id="stepUploadA">
          <div class="alert alert-warning">
            <strong><i class="fa fa-info-circle"></i> LP Model A - Tindak Pidana Yang Ditemukan</strong><br>
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
            <i class="fa fa-cog me-1"></i> Parse Dokumen
          </button>
          <div id="parseLoadingA" class="mt-2">
            <div class="spinner-border spinner-border-sm text-warning"></div>
            <span class="ms-2">Sedang memproses file...</span>
          </div>
          <div id="parseErrorA" class="alert alert-danger mt-2"></div>
        </div>

        <!-- STEP 2: Form -->
        <div id="stepFormA">
          <div class="alert alert-success">
            <i class="fa fa-check-circle"></i> File LP A berhasil di-parse! Periksa data dan lengkapi field yang kosong.
          </div>

          <form method="POST" id="formImportWordA">
              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="row">
              <!-- KOLOM KIRI: Data dari Word -->
              <div class="col-md-6">
                <h6 class="fw-bold text-success border-bottom pb-2 mb-3">
                  <i class="fa fa-file-alt me-1"></i> Data dari File Word (Otomatis)
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
                  <i class="fa fa-table me-1"></i> Data dari Tabel Bawah Dokumen
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
                  <i class="fa fa-edit me-1"></i> Data Manual (Harus Dilengkapi)
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
                <input type="hidden" name="penyebab" value="">
              </div>
            </div>

            <hr>
            <div class="d-flex justify-content-between">
              <button type="button" class="btn btn-secondary" id="btnBackToUploadA">
                <i class="fa fa-arrow-left me-1"></i> Upload Ulang
              </button>
              <button type="submit" class="btn btn-primary">
                <i class="fa fa-save me-1"></i> Simpan ke Database
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
                  <h5 class="modal-title"><i class="fa fa-file-word-o me-2"></i>Import Laporan Polisi Model B</h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                  <!-- STEP 1: Upload File -->
                  <div id="stepUpload">
                    <div class="alert alert-info">
                      <strong><i class="fa fa-info-circle"></i> Petunjuk:</strong><br>
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
                      <i class="fa fa-cog me-1"></i> Parse Dokumen
                    </button>
                    <div id="parseLoading" class="mt-2">
                      <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                      <span class="ms-2">Sedang memproses file...</span>
                    </div>
                    <div id="parseError" class="alert alert-danger mt-2"></div>
                  </div>

                  <!-- STEP 2: Form Lengkap (Muncul setelah parse berhasil) -->
                  <div id="stepForm">
                    <div class="alert alert-success">
                      <i class="fa fa-check-circle"></i> File berhasil di-parse! Silakan periksa data di bawah dan lengkapi field yang kosong.
                    </div>

                    <form method="POST" id="formImportWord">
                      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                      <div class="row">
                        <!-- KOLOM KIRI: Data dari File Word (otomatis) -->
                        <div class="col-md-6">
                          <h6 class="fw-bold text-success border-bottom pb-2 mb-3">
                            <i class="fa fa-file-alt me-1"></i> Data dari File Word (Otomatis)
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
                              <input type="datetime-local" class="form-control form-control-sm" name="tanggal" id="imp_waktu_kejadian">
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
                            <i class="fa fa-table me-1"></i> Data dari Tabel Bawah Dokumen
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
                            <i class="fa fa-edit me-1"></i> Data Manual (Harus Dilengkapi)
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
                          <i class="fa fa-arrow-left me-1"></i> Upload Ulang
                        </button>
                        <button type="submit" class="btn btn-primary">
                          <i class="fa fa-save me-1"></i> Simpan ke Database
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
                  <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
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
                      <select class="form-select form-select-sm" name="kategori_id" id="kategori_id" required data-options='{"removeItemButton":true,"placeholder":true}'>
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
                      <select class="form-select form-select-sm js-choice" name="sub_kategori_id" id="sub_kategori_id" required data-options='{"removeItemButton":true,"placeholder":true}'>
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
                        <div id="kriminalitasMapTambah"></div>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Latitude</label>
                        <input type="text" class="form-control" name="latitude" id="latitude" >
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Longitude</label>
                        <input type="text" class="form-control" name="longitude" id="longitude" >
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
                      </select>
                    </div>
                    <div class="mb-3" id="formTanggalSelesai">
                      <label class="form-label">Tanggal Selesai</label>
                      <input type="datetime-local" class="form-control form-control-sm" name="tanggal_selesai" id="tanggal_selesai">
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
    <script src="../vendors/jquery/jquery-3.7.0.min.js"></script>
    <script src="../vendors/datatables/datatables.min.js"></script>
    <script src="../assets/js/kriminalitas.js"></script>
  
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
 