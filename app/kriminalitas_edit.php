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
function getKriminalitasEditLog($old, $new, $username) {
    $fields = [
        'sub_kategori_id'=>'Sub Kategori',
        'desa_id'=>'Desa',
        'polres_id'=>'Polres',
        'polsek_id'=>'Polsek',
        'state'=>'State',
        'poin'=>'Jumlah',
        'no_lp'=>'No LP',
        'tanggal'=>'Tanggal',
        'penanggungjawab'=>'Penanggung Jawab',
        'lokasi'=>'Lokasi',
        'penyebab'=>'Penyebab',
        'jenis_tkp_id'=>'Jenis TKP',
        'tujuan'=>'Tujuan'
    ];
    $log = "Edit data oleh $username: ";
    $changes = [];
    foreach($fields as $f=>$label){
      $oldVal = isset($old[$f]) ? $old[$f] : null;
      $newVal = isset($new[$f]) ? $new[$f] : null;
       if($f === 'tanggal') {
          $oldValNorm = $oldVal ? date('Y-m-d\TH:i', strtotime($oldVal)) : '';
          $newValNorm = $newVal ? date('Y-m-d\TH:i', strtotime($newVal)) : '';
          if($oldValNorm != $newValNorm) {
              $changes[] = "Ubah '$label' dari ".htmlspecialchars($oldValNorm)." menjadi ".htmlspecialchars($newValNorm);
          }
          continue;
      }
      if($oldVal != $newVal){
          $changes[] = "Ubah '$label' dari ".htmlspecialchars($oldVal)." menjadi ".htmlspecialchars($newVal);
      }
    }
    $log .= implode("; ", $changes);
    return $log;
}
if(!isset($_SESSION["id"]) || !isset($_SESSION["nama"])) {
  header("Location: ../index");
  exit;
}
 if(empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  $_SESSION["menu"]="kriminalitas";
  $menu=$_SESSION["menu"];
  $id_user=$_SESSION["id"];
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
  $id_kriminalitas = isset($_GET['id']) ? intval($_GET['id']) : 0;



   if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
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
      $polsek_id = !empty($_POST['polsek_id']) ? intval($_POST['polsek_id']) : null;
    }
    $tanggal = trim(!empty($_POST['tanggal']) ? $_POST['tanggal'] : null);
    $state = trim($_POST['state'] ?? '');
    $sub_state = trim($_POST['sub_state'] ?? '');
    $poin = trim($_POST['poin'] ?? '');
    $penanggungjawab = trim($_POST['penanggungjawab'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $sumber_id = intval($_POST['sumber_id']);
    $lokasi = trim($_POST['lokasi'] ?? '');
    $penyebab = trim($_POST['penyebab'] ?? '');
    $jenis_tkp_id = !empty($_POST['jenis_tkp_id']) ? intval($_POST['jenis_tkp_id']) : null;
    $no_lp = trim($_POST['no_lp'] ?? '');
    $tujuan = trim($_POST['tujuan'] ?? '');
    $latitude = trim($_POST['latitude'] ?? 0.0);
    $longitude = trim($_POST['longitude'] ?? 0.0);
    $pelapor = trim($_POST['pelapor'] ?? '');
    $terlapor = trim($_POST['terlapor'] ?? '');
    $korban = trim($_POST['korban'] ?? '');
    $tanggal_laporan = !empty($_POST['tanggal_laporan']) ? $_POST['tanggal_laporan'] : null;
    $tindak_pidana = trim($_POST['tindak_pidana'] ?? '');
    $saksi= trim($_POST['saksi'] ?? '');
    $barang_bukti = trim($_POST['barang_bukti'] ?? '');
    $uraian = trim($_POST['uraian'] ?? '');
    $tanggal_selesai = !empty($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : null;

    $stmtOld = $pdo->prepare("SELECT * FROM kriminals WHERE id=?");
    $stmtOld->execute([$edit_id]);
    $dataOld = $stmtOld->fetch(PDO::FETCH_ASSOC);
    
    $kategoriMap = [];
    $stmtKategori = $pdo->query("SELECT id, nama FROM kriminal_kategoris WHERE status=1");
    while($rowKat = $stmtKategori->fetch(PDO::FETCH_ASSOC)) {
        $kategoriMap[$rowKat['id']] = $rowKat['nama'];
    }  

    $setPolsek = is_null($polsek_id) ? "polsek_id=NULL" : "polsek_id=:polsek_id";
    $stmt = $pdo->prepare("UPDATE kriminals SET sub_kategori_id=:sub_kategori_id, desa_id=:desa_id, polres_id=:polres_id, $setPolsek,
      lokasi=:lokasi, penyebab=:penyebab, no_lp=:no_lp, jenis_tkp_id=:jenis_tkp_id,
     tanggal=:tanggal, state=:state, sub_state=:sub_state, poin=:poin, penanggungjawab=:penanggungjawab, keterangan=:keterangan, 
     sumber_id=:sumber_id, updated_at=NOW(), tujuan=:tujuan, latitude=:latitude, longitude=:longitude, 
     pelapor=:pelapor, terlapor=:terlapor, korban=:korban, tanggal_laporan=:tanggal_laporan, tindak_pidana=:tindak_pidana, saksi=:saksi, barang_bukti=:barang_bukti, uraian=:uraian, tanggal_selesai=:tanggal_selesai  
     WHERE id=:edit_id");
    $params = [
      ':sub_kategori_id'=>$sub_kategori_id,
      ':desa_id'=>$desa_id,
      ':polres_id'=>$polres_id,
      ':lokasi'=>$lokasi,
      ':penyebab'=>$penyebab,
      ':no_lp'=>$no_lp,
      ':jenis_tkp_id'=>$jenis_tkp_id,
      ':tanggal'=>$tanggal,
      ':state'=>$state,
      ':sub_state'=>$sub_state,
      ':poin'=>$poin,
      ':penanggungjawab'=>$penanggungjawab,
      ':keterangan'=>$keterangan,
      ':sumber_id'=>$sumber_id,
      ':tujuan'=>$tujuan,
      ':edit_id'=>$edit_id,
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
    ];
    if(!is_null($polsek_id)) { $params[':polsek_id'] = $polsek_id; }
    
    try {
      $stmt->execute($params);
    } catch (PDOException $e) {
      // Simpan error log ke file
      $logDir = __DIR__ . '/../logs';
      if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
      }
      $logFile = $logDir . '/kriminalitas_error.log';
      $logMessage = "[" . date('Y-m-d H:i:s') . "] Error update kriminalitas ID $edit_id oleh $username: " . $e->getMessage() . "\n";
      file_put_contents($logFile, $logMessage, FILE_APPEND);

      header("Location: kriminalitas?status=error&log=" . urlencode($e->getMessage()));
      exit;
    }
    
    $detailLog = getKriminalitasEditLog($dataOld, [
      'sub_kategori_id'=>$sub_kategori_id,
      'desa_id'=>$desa_id,
      'polres_id'=>$polres_id,
      'polsek_id'=>$polsek_id,
      'state'=>$state,
      'sub_state'=>$sub_state,
      'poin'=>$poin,
      'no_lp'=>$no_lp,
      'tanggal'=>$tanggal,
      'penanggungjawab'=>$penanggungjawab,
      'lokasi'=>$lokasi,
      'penyebab'=>$penyebab,
      'jenis_tkp_id'=>$jenis_tkp_id,
      'tujuan'=>$tujuan
    ], $username );

    logUser($pdo, $id_user, 'update', 'kriminalitas', $edit_id, $detailLog);
    header("Location: kriminalitas?status=edit_sukses");
    exit;
  }

  // --- Ambil data kriminalitas untuk diedit
  $q = $pdo->prepare("
    SELECT
                      k.*,
                      po.nama AS polres_nama,
                      d.kecamatan_id,
                      ke.kabupaten_id,
                      s.nama AS sumber_nama,
                      ka.nama as sub_kategori_nama,
                      ka.kategori_id,
                      kat.nama as kategori_nama
                    FROM kriminals k
                    LEFT JOIN kriminal_sub_kategoris ka ON k.sub_kategori_id = ka.id
                    LEFT JOIN kriminal_kategoris kat ON ka.kategori_id = kat.id                 
                    LEFT JOIN polress po ON k.polres_id = po.id
                     LEFT JOIN desas d ON k.desa_id = d.id
                    LEFT JOIN kecamatans ke ON d.kecamatan_id = ke.id
                    LEFT JOIN sumbers s ON k.sumber_id = s.id
    WHERE k.id=:id
  ");
  $q->execute([':id'=>$id_kriminalitas]);
  $data = $q->fetch(PDO::FETCH_ASSOC);

  // Jika data tidak ditemukan, redirect/bisa beri pesan error
  if(!$data){ header("Location: kriminalitas?status=tak_ada");exit; }

  // --- Load pilihan dropdown terkait untuk pre-fill
  $kategoriList = $pdo->query("SELECT id, nama FROM kriminal_kategoris WHERE status=1 ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
 $subKategoriList = [];
  if(isset($data['kategori_id'])) {
    $stmt = $pdo->prepare("SELECT id, nama FROM kriminal_sub_kategoris WHERE status=1 AND kategori_id=? ORDER BY nama");
    $stmt->execute([$data['kategori_id']]);
    $subKategoriList = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
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
 

  $kecList = [];
  if($data['kabupaten_id']){
    if($akses=="POLSEK") {
      $stmt_kec = $pdo->prepare("SELECT id, nama, kode FROM kecamatans WHERE status=1 AND kabupaten_id=? AND polsek_id=? ORDER BY nama");
      $stmt_kec->execute([$data['kabupaten_id'], $polsek_id]);
    } else {
      $stmt_kec = $pdo->prepare("SELECT id, nama, kode FROM kecamatans WHERE status=1 AND kabupaten_id=? ORDER BY nama");
      $stmt_kec->execute([$data['kabupaten_id']]);
    }
    $kecList = $stmt_kec->fetchAll(PDO::FETCH_ASSOC);
  }
  $desaList = [];
  if($data['kecamatan_id']){
    $stmt_desa = $pdo->prepare("SELECT id, nama, kode FROM desas WHERE status=1 AND kecamatan_id=? ORDER BY nama");
    $stmt_desa->execute([$data['kecamatan_id']]);
    $desaList = $stmt_desa->fetchAll(PDO::FETCH_ASSOC);
  }
  if($akses == "POLRES") {
    $stmt_polres = $pdo->prepare("SELECT id, nama FROM polress WHERE status=1 AND id=? ORDER BY nama");
    $stmt_polres->execute([$polres_id]);
    $polresList = $stmt_polres->fetchAll(PDO::FETCH_ASSOC);
  } else if($akses == "POLSEK") {
    $stmt_polres = $pdo->prepare("SELECT polress.id, polress.nama FROM polress left join polseks on polress.id = polseks.polres_id WHERE polress.status=1 AND polseks.id=? ORDER BY polress.nama");
    $stmt_polres->execute([$polsek_id]);
    $polresList = $stmt_polres->fetchAll(PDO::FETCH_ASSOC);
  } else {
   $polresList = $pdo->query("SELECT id, nama FROM polress WHERE status=1 ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
  }
  $polsekList = [];
  if($data['polres_id']){
    if($akses == "POLSEK") {
      $stmt_polsek = $pdo->prepare("SELECT id, nama FROM polseks WHERE status=1 AND id=? ORDER BY nama");
      $stmt_polsek->execute([$polsek_id]);
    } else {
      $stmt_polsek = $pdo->prepare("SELECT id, nama FROM polseks WHERE status=1 AND polres_id=? ORDER BY nama");
     $stmt_polsek->execute([$data['polres_id']]);
    }
    $polsekList = $stmt_polsek->fetchAll(PDO::FETCH_ASSOC);
  }
  $sumberList = $pdo->query("SELECT id, nama FROM sumbers WHERE status=1 AND tipe='KRIMINALITAS' ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
  $jenisTkpList = $pdo->query("SELECT id, nama FROM jenis_tkps WHERE status=1 ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
  $logHist = $pdo->prepare("
    SELECT l.*, u.nama AS user_nama, u.username
    FROM user_logs l
    LEFT JOIN users u ON l.user_id = u.id
    WHERE l.modul = 'kriminalitas' AND l.data_id = ?
    ORDER BY l.created_at DESC
");
$logHist->execute([$data['id']]);
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Kriminalitas | Peta Digital</title>
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
    <link rel="stylesheet" type="text/css" href="../assets/icon/font-awesome/css/font-awesome.min.css"><link href="../vendors/overlayscrollbars/OverlayScrollbars.min.css" rel="stylesheet">
    <link href="../assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
    <link href="../assets/css/theme.min.css" rel="stylesheet" id="style-default">
    <link href="../assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
    <link href="../assets/css/user.min.css" rel="stylesheet" id="user-style-default">
    <link href="../assets/css/kriminalitas-edit.css" rel="stylesheet">
    <link rel="stylesheet" href="../vendors/leaflet/leaflet.css" />
    <script src="../vendors/leaflet/leaflet.js"></script>
    
</head>
  <body data-polsek-id="<?php echo htmlspecialchars($_SESSION['polsek_id'] ?? '', ENT_QUOTES); ?>"
        data-lat-provinsi="<?php echo htmlspecialchars($lat_provinsi, ENT_QUOTES); ?>"
        data-lng-provinsi="<?php echo htmlspecialchars($lng_provinsi, ENT_QUOTES); ?>">
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
                                <h5 class="fs-0 mb-0"><span class="fa fa-edit me-2 fs-0"></span> Edit Data Kriminalitas</h5>
                            </div>
                            <div class="col-auto ms-auto">
                                <a href="kriminalitas" class="btn btn-falcon-default btn-sm">Kembali ke Daftar Kriminalitas</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                          <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>">
                          <input type="hidden" name="edit_id" value="<?=$data['id']?>">
                          <div class="mb-3">
                            <label class="form-label">Jumlah</label>
                            <input type="number" class="form-control form-control-sm" name="poin" value="<?=htmlspecialchars($data['poin'])?>" required>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select class="form-select form-select-sm" name="kategori_id" required>
                              <option value="">- Pilih Kategori -</option>
                              <?php foreach($kategoriList as $k): ?>
                                <option value="<?=$k['id']?>" <?=$data['kategori_id']==$k['id']?'selected':''?>><?=htmlspecialchars($k['nama'])?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Sub Kategori</label>
                            <select class="form-select form-select-sm" name="sub_kategori_id" id="sub_kategori_id" required>
                              <option value="">- Pilih Sub Kategori -</option>
                              <?php foreach($subKategoriList as $sk): ?>
                                <option value="<?=$sk['id']?>" <?=$data['sub_kategori_id']==$sk['id']?'selected':''?>><?=htmlspecialchars($sk['nama'])?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                           
                          <div class="mb-3">
                            <label class="form-label">Kabupaten</label>
                            <select class="form-select form-select-sm" name="kabupaten_id" id="kabupaten_id" required>
                              <option value="">- Pilih Kabupaten -</option>
                              <?php foreach($kabList as $kab): ?>
                                <option value="<?=$kab['id']?>" <?=$data['kabupaten_id']==$kab['id']?'selected':''?>><?=htmlspecialchars($kab['kode'])?> - <?=htmlspecialchars($kab['nama'])?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Kecamatan</label>
                            <select class="form-select form-select-sm" name="kecamatan_id" id="kecamatan_id" required>
                              <option value="">- Pilih Kecamatan -</option>
                              <?php foreach($kecList as $kec): ?>
                                <option value="<?=$kec['id']?>" <?=$data['kecamatan_id']==$kec['id']?'selected':''?>><?=htmlspecialchars($kec['kode'])?> - <?=htmlspecialchars($kec['nama'])?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Desa</label>
                            <select class="form-select form-select-sm" name="desa_id" id="desa_id" required>
                              <option value="">- Pilih Desa -</option>
                              <?php foreach($desaList as $d): ?>
                                <option value="<?=$d['id']?>" <?=$data['desa_id']==$d['id']?'selected':''?>><?=htmlspecialchars($d['kode'])?> - <?=htmlspecialchars($d['nama'])?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Polres</label>
                            <select class="form-select form-select-sm" name="polres_id" id="polres_id" required>
                              <option value="">- Pilih Polres -</option>
                              <?php foreach($polresList as $pr): ?>
                                <option value="<?=$pr['id']?>" <?=$data['polres_id']==$pr['id']?'selected':''?>><?=htmlspecialchars($pr['nama'])?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Polsek <small class="text-muted">(boleh dikosongkan)</small></label>
                            <select class="form-select form-select-sm" name="polsek_id" id="polsek_id">
                              <option value="">- Pilih Polsek -</option>
                              <?php foreach($polsekList as $psk): ?>
                                <option value="<?=$psk['id']?>" <?=$data['polsek_id']==$psk['id']?'selected':''?>><?=htmlspecialchars($psk['nama'])?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">No. LP</label>
                            <input type="text" class="form-control form-control-sm" name="no_lp" value="<?=htmlspecialchars($data['no_lp'] ?? '')?>" >
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Jenis Lokasi</label>
                            <select class="form-select form-select-sm" name="jenis_tkp_id" id="jenis_tkp_id">
                              <option value="">- Pilih Jenis Lokasi -</option>
                              <?php foreach($jenisTkpList as $jl): ?>
                                <option value="<?=$jl['id']?>" <?=$data['jenis_tkp_id']==$jl['id']?'selected':''?>><?=htmlspecialchars($jl['nama'])?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Tempat Kejadian</label>
                            <Textarea class="form-control form-control-sm" name="lokasi" rows="3"><?=htmlspecialchars($data['lokasi'] ?? '')?></textarea>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Tentukan Lokasi di Map</label>
                            <div id="kriminalitasMapEdit"></div>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Latitude</label>
                            <input type="text" class="form-control" value="<?=htmlspecialchars($data['latitude'] ?? '')?>" name="latitude" id="latitude" >
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Longitude</label>
                            <input type="text" class="form-control" value="<?=htmlspecialchars($data['longitude'] ?? '')?>" name="longitude" id="longitude" >
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Tanggal Kejadian</label>
                            <input type="datetime-local" class="form-control form-control-sm" name="tanggal" value="<?=htmlspecialchars($data['tanggal'])?>" >
                          </div>
                          
                          <div class="mb-3">
                            <label class="form-label">Pelapor</label>
                            <Textarea class="form-control form-control-sm" name="pelapor" rows="3"><?=htmlspecialchars($data['pelapor'] ?? '')?></textarea>
                          </div> 
                          <div class="mb-3">
                            <label class="form-label">Apa yang terjadi?</label>
                            <textarea class="form-control form-control-sm" name="keterangan" rows="3"><?=htmlspecialchars($data['keterangan'])?></textarea>
                          </div>
                           <div class="mb-3">
                            <label class="form-label">Terlapor</label>
                            <Textarea class="form-control form-control-sm" name="terlapor" rows="3"><?=htmlspecialchars($data['terlapor'] ?? '')?></textarea>
                          </div>
                           <div class="mb-3">
                            <label class="form-label">Korban</label>
                            <Textarea class="form-control form-control-sm" name="korban" rows="3"><?=htmlspecialchars($data['korban'] ?? '')?></textarea>
                          </div> 
                           <div class="mb-3">
                            <label class="form-label">Tanggal Laporan</label>
                            <input type="datetime-local" class="form-control form-control-sm" name="tanggal_laporan" value="<?=htmlspecialchars($data['tanggal_laporan'])?>" >
                          </div> 
                           <div class="mb-3">
                            <label class="form-label">Tindak Pidana</label>
                            <Textarea class="form-control form-control-sm" name="tindak_pidana" rows="3"><?=htmlspecialchars($data['tindak_pidana'] ?? '')?></textarea>
                          </div> 
                          <div class="mb-3">
                            <label class="form-label">Saksi</label>
                            <Textarea class="form-control form-control-sm" name="saksi" rows="3"><?=htmlspecialchars($data['saksi'] ?? '')?></textarea>
                          </div> 
                          <div class="mb-3">
                            <label class="form-label">Barang Bukti</label>
                            <Textarea class="form-control form-control-sm" name="barang_bukti" rows="3"><?=htmlspecialchars($data['barang_bukti'] ?? '')?></textarea>
                          </div> 
                          <div class="mb-3">
                            <label class="form-label">Uraian Singkat</label>
                            <Textarea class="form-control form-control-sm" name="uraian" rows="3"><?=htmlspecialchars($data['uraian'] ?? '')?></textarea>
                          </div> 
                          <div class="mb-3">
                            <label class="form-label">Sumber Dokumen</label>
                            <select class="form-select form-select-sm" name="sumber_id" required>
                              <option value="">- Pilih Sumber -</option>
                              <?php foreach($sumberList as $s): ?>
                                <option value="<?=$s['id']?>" <?=$data['sumber_id']==$s['id']?'selected':''?>><?=htmlspecialchars($s['nama'])?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">State</label>
                            <select class="form-select form-select-sm" name="state" id="state" required>
                              <option value="">- Pilih State -</option>
                              <option value="PROSES" <?=$data['state']=='PROSES'?'selected':''?>>PROSES</option>
                              <option value="SELESAI" <?=$data['state']=='SELESAI'?'selected':''?>>SELESAI</option>
                     
                            </select>
                          </div>
                          <div class="mb-3">
                              <label class="form-label">Sub State</label>
                              <select class="form-select form-select-sm" name="sub_state" id="sub_state" required>
                              </select>
                          </div>
                        <div class="mb-3" id="formTanggalSelesai">
                          <label class="form-label">Tanggal Selesai</label>
                          <input type="datetime-local" class="form-control form-control-sm" name="tanggal_selesai" id="tanggal_selesai" value="<?=htmlspecialchars($data['tanggal_selesai'])?>">
                        </div>
                          <div class="mb-3">
                            <label class="form-label">Tujuan</label>
                            <select class="form-select form-select-sm" name="tujuan" >
                              <option value="">- Pilih Tujuan -</option>
                              <option value="DITNARKOBA" <?=$data['tujuan']=='DITNARKOBA'?'selected':''?>>DITNARKOBA</option>
                              <option value="DITRESKRIMSUS" <?=$data['tujuan']=='DITRESKRIMSUS'?'selected':''?>>DITRESKRIMSUS</option>
                              <option value="DITRESKRIMUM" <?=$data['tujuan']=='DITRESKRIMUM'?'selected':''?>>DITRESKRIMUM</option>
                              <option value="DITINTELKAM" <?=$data['tujuan']=='DITINTELKAM'?'selected':''?>>DITINTELKAM</option>
                              <option value="DITPOLAIRUD" <?=$data['tujuan']=='DITPOLAIRUD'?'selected':''?>>DITPOLAIRUD</option>
                              <option value="DITLANTAS" <?=$data['tujuan']=='DITLANTAS'?'selected':''?>>DITLANTAS</option>
                              <option value="DITSAMAPTA" <?=$data['tujuan']=='DITSAMAPTA'?'selected':''?>>DITSAMAPTA</option>
                              <option value="DITSABHARA" <?=$data['tujuan']=='DITSABHARA'?'selected':''?>>DITSABHARA</option>
                              <option value="PPA" <?=$data['tujuan']=='PPA'?'selected':''?>>PPA</option>
                              <option value="PPO" <?=$data['tujuan']=='PPO'?'selected':''?>>PPO</option>
                              <option value="SPKT" <?=$data['tujuan']=='SPKT'?'selected':''?>>SPKT</option>
                            </select>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Penanggung Jawab</label>
                            <input type="text" class="form-control form-control-sm" name="penanggungjawab" value="<?=htmlspecialchars($data['penanggungjawab'])?>">
                          </div>
                          <button type="submit" class="btn btn-primary">Update</button>
                          <a href="kriminalitas" class="btn btn-secondary">Batal</a>
                        </form>
                    </div>
                </div>
            </div>
            <?php  
            // Folder simpan upload
                $upload_dir = realpath(__DIR__ . '/../public/upload/kriminal');
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true); // hanya sekali, lalu cek permission
                }
                if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['upload_attachment'])) {
                    $kriminal_id = $data['id'];
                    $file_name = $_FILES['attachment_file']['name'];
                    $tmp_name = $_FILES['attachment_file']['tmp_name'];
                    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed = ['pdf','jpg','jpeg','png'];
                    $keterangan = trim($_POST['attachment_keterangan']??'');

                    if(in_array($ext,$allowed) && is_uploaded_file($tmp_name)) {
                        $new_name = uniqid('kriminalitas_'.$kriminal_id.'_').'.'.$ext;
                        $move_path = $upload_dir . '/' . $new_name;
                        if(move_uploaded_file($tmp_name, $move_path)) {
                            $stmt = $pdo->prepare("INSERT INTO kriminal_attachments (nama, keterangan, jenis, file, kriminal_id, status, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
                            $stmt->execute([$file_name, $keterangan, $ext, $new_name, $kriminal_id]);
                            header("Location: kriminalitas-edit?id=$kriminal_id&upload=sukses");
                            echo "<meta http-equiv='refresh' content='0; url=kriminalitas-edit?id=$kriminal_id&upload=sukses'>";
                            exit;
                        } else {
                            $error_upload = "Upload gagal.";
                        }
                    } else {
                        $error_upload = "File harus PDF/JPG/PNG dan ukuran sesuai server.";
                    }
                }

                // Proses hapus file attachment
                if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['delete_attachment_id'])) {
                    $del_id = intval($_POST['delete_attachment_id']);
                    $q = $pdo->prepare("SELECT file, kriminal_id FROM kriminal_attachments WHERE id=?");
                    $q->execute([$del_id]);
                    $f = $q->fetch();
                    if($f) {
                        @unlink($upload_dir . '/' . $f['file']);
                        $pdo->prepare("UPDATE kriminal_attachments SET status=0, updated_at=NOW() WHERE id=?")->execute([$del_id]);
                        //header("Location: kriminalitas_edit.php?id=".$f['kriminalitas_id']."&delete=ok");
                        echo "<meta http-equiv='refresh' content='0; url=kriminalitas-edit?id=".$f['kriminal_id']."&delete=ok'>";
                        exit;
                    }
                }
            ?>
            <div class="col-lg-4">
                <?php if(!empty($error_upload)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert"><?=$error_upload?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php elseif(isset($_GET['upload']) && $_GET['upload']=='sukses'): ?>
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">Upload berkas berhasil!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php elseif(isset($_GET['delete']) && $_GET['delete']=='ok'): ?>
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">Lampiran berhasil dihapus!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php endif; ?>
                    <div class="card-header bg-light">
                    <h5 class="fs-0 mb-0"><span class="fa fa-folder me-2 fs-0"></span> Data Berkas</h5>
                    </div>
                  <div class="card mb-3 border-success">                
                    <div class="card-body">
                    <!-- Form upload berkas -->
                    <form method="post" enctype="multipart/form-data">
                      <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>">
                        <div class="mb-2">
                        <label class="form-label">Upload Berkas (PDF, JPG, PNG)</label>
                        <input type="file" class="form-control form-control-sm" name="attachment_file" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <div class="mb-2">
                        <label class="form-label">Keterangan</label>
                        <input type="text" class="form-control form-control-sm" name="attachment_keterangan" placeholder="Keterangan singkat">
                        </div>
                        <button type="submit" name="upload_attachment" class="btn btn-success btn-sm">Upload</button>
                    </form>
                    <hr>
                    <!-- Daftar berkas terlampir -->
                    <h6><span class="fa fa-paperclip me-1"></span> Lampiran:</h6>
                    <ul class="list-group list-lampiran">
                        <?php
                        $lampiran = $pdo->prepare("SELECT * FROM kriminal_attachments WHERE status=1 AND kriminal_id=? ORDER BY created_at DESC");
                        $lampiran->execute([$data['id']]);
                        while($att = $lampiran->fetch()) {
                        $icon = "fa-file";
                        $jenis = strtolower(pathinfo($att['file'], PATHINFO_EXTENSION));
                        if($jenis=='pdf') $icon="fa-file-pdf-o text-danger";
                        elseif($jenis=='jpg'||$jenis=='jpeg'||$jenis=='png') $icon="fa-file-image-o text-info";
                        echo "<li class='list-group-item d-flex justify-content-between align-items-center list-lampiran-item'>
                            <span><span class='fa $icon me-2'></span>
                            <a href='../public/upload/kriminal/".$att['file']."' target='_blank'>".htmlspecialchars($att['nama'])."</a>
                            ".($att['keterangan']? "<span class='text-muted ms-1'>($att[keterangan])</span>":"")."
                            </span>
                            <form method='post' class='form-hapus-lampiran'>
                            <input type='hidden' name='csrf_token' value='".htmlspecialchars($_SESSION['csrf_token'])."'>
                            <input type='hidden' name='delete_attachment_id' value='$att[id]'>
                            <button type='submit' name='delete_attachment' class='btn btn-xs btn-danger btn-sm' title='Hapus lampiran' onclick=\"return confirm('Hapus berkas ini?')\"><i class='fa fa-times'></i></button>
                            </form>
                        </li>";
                        }
                        ?>
                        <?php if($lampiran->rowCount()==0) echo "<li class='list-group-item text-muted small'>Belum ada berkas terupload.</li>"; ?>
                      </ul>
                    </div>
                  </div>

                  <div class="card mb-3 border-primary">
                    <div class="card-header bg-light">
                      <h5 class="fs-0 mb-0"><span class="fa fa-history me-2"></span> Riwayat Aktivitas Data</h5>
                    </div>
                    <div class="card-body">
                      <ul class="list-group">
                        <?php if($logHist->rowCount() == 0): ?>
                          <li class="list-group-item text-muted small">Belum ada riwayat aktivitas.</li>
                        <?php else: ?>
                          <?php while($lg = $logHist->fetch(PDO::FETCH_ASSOC)): ?>
                            <li class="list-group-item">
                              <span class="badge bg-primary me-2"><?=htmlspecialchars($lg['aktivitas'])?></span>
                              <span class="fw-bold"><?=htmlspecialchars($lg['user_nama'] ?? $lg['username'])?></span>
                              <span class="text-muted"><?=date('d/m/Y H:i', strtotime($lg['created_at']))?></span>
                              <br>
                              <small><?=htmlspecialchars($lg['keterangan'])?></small>
                            </li>
                          <?php endwhile; ?>
                        <?php endif; ?>
                      </ul>
                    </div>
                  </div>
                </div>
        </div>
        <?php include_once("footer.php") ?>                         
      </div>
    </div>
  </main>

  <!-- AJAX Dropdown cascade: kecamatan, desa, polsek -->
<script src="../vendors/jquery/jquery-3.7.0.min.js"></script>
 
      <script src="../vendors/popper/popper.min.js"></script>
    <script src="../vendors/bootstrap/bootstrap.min.js"></script>
    <script src="../assets/js/kriminalitas-edit.js"></script>
    <script src="../vendors/anchorjs/anchor.min.js"></script>
    <script src="../vendors/is/is.min.js"></script>
    <script src="../vendors/prism/prism.js"></script>
   
    <script src="../vendors/lodash/lodash.min.js"></script>
    <script src="../vendors/list.js/list.min.js"></script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>
 