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
function kamtibmasLogDetail($old, $new, $username, $kategoriMap) {
    $fields = [
        'kategori_id'=>'Kategori',
        'modus_operandi_id'=>'Modus Operandi',
        'jenis_tkp_id'=>'Jenis TKP',
        'desa_id'=>'Desa',
        'polres_id'=>'Polres',
        'polsek_id'=>'Polsek',
        'tanggal'=>'Tanggal',
        'nomor_lp'=>'Nomor LP',
        'tersangka'=>'Tersangka',
        'permasalahan'=>'Permasalahan',
        'penanganan'=>'Penanganan',
        'tindak_lanjut'=>'Tindak Lanjut',
        'sumber_id'=>'Sumber',
        'state'=>'State',
        'tujuan'=>'Tujuan'
    ];
    $log = "Edit oleh $username: ";
    $chg = [];
    foreach($fields as $f=>$label){
      $oldVal = isset($old[$f]) ? $old[$f] : null;
      $newVal = isset($new[$f]) ? $new[$f] : null;
      if($f === 'tanggal') {
          $oldValNorm = $oldVal ? date('Y-m-d\TH:i', strtotime($oldVal)) : '';
          $newValNorm = $newVal ? date('Y-m-d\TH:i', strtotime($newVal)) : '';
          if($oldValNorm != $newValNorm) {
              $chg[] = "Ubah '$label' dari ".htmlspecialchars($oldValNorm)." menjadi ".htmlspecialchars($newValNorm);
          }
          continue;
      }
      if($oldVal != $newVal){
          if($f === 'kategori_id') {
              $oldNama = isset($kategoriMap[$oldVal]) ? $kategoriMap[$oldVal] : $oldVal;
              $newNama = isset($kategoriMap[$newVal]) ? $kategoriMap[$newVal] : $newVal;
              $chg[] = "Ubah '$label' dari ".htmlspecialchars($oldNama)." menjadi ".htmlspecialchars($newNama);
          } else {
              $chg[] = "Ubah '$label' dari ".htmlspecialchars($oldVal)." menjadi ".htmlspecialchars($newVal);
          }
      }
    }
    $log .= $chg ? implode("; ", $chg) : "Pengeditan tanpa perubahan data.";
    return $log;
}
if(!isset($_SESSION["id"]) || !isset($_SESSION["nama"])) {
  header("Location: ../index");
  exit;
}
 if(empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  $_SESSION["menu"]="kamtibmas";
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
      //  echo "CSRF token validation failed.".$_POST['csrf_token'] . " | " . $_SESSION['csrf_token'];
        header("Location: kamtibmas?status=csrf_failed");
        exit;
    }
  }
  // --- Ambil id kamtibmas dari GET
  $id_kamtibmas = isset($_GET['id']) ? intval($_GET['id']) : 0;

   if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $kategori_id = intval($_POST['kategori_id']);
    $modus_operandi_id = intval($_POST['modus_operandi_id']);
    $jenis_tkp_id = intval($_POST['jenis_tkp_id']);
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
 
    $tanggal = trim($_POST['tanggal']);
    $nomor_lp = trim($_POST['nomor_lp'] ?? '');
    $tersangka = trim($_POST['tersangka'] ?? '');
    $permasalahan = trim($_POST['permasalahan'] ?? '');
    $penanganan = trim($_POST['penanganan'] ?? '');
    $tindak_lanjut = trim($_POST['tindak_lanjut'] ?? '');
    $is_menonjol = 1;
    $sumber_id = intval($_POST['sumber_id']);
    $state = $_POST['state'] ?? 'PROSES';
    $tujuan = $_POST['tujuan'] ?? '';

    $stmtOld = $pdo->prepare("SELECT * FROM kamtibmass WHERE id=?");
    $stmtOld->execute([$edit_id]);
    $dataOld = $stmtOld->fetch(PDO::FETCH_ASSOC);    
    $kategoriMap = [];
    $stmtKategori = $pdo->query("SELECT id, nama FROM kamtibmas_kategoris WHERE status=1");
    while($rowKat = $stmtKategori->fetch(PDO::FETCH_ASSOC)) {
        $kategoriMap[$rowKat['id']] = $rowKat['nama'];
    }  

    $setPolsek = is_null($polsek_id) ? "polsek_id=NULL" : "polsek_id=:polsek_id";
    $stmt = $pdo->prepare("UPDATE kamtibmass SET kategori_id=:kategori_id, modus_operandi_id=:modus_operandi_id, jenis_tkp_id=:jenis_tkp_id, desa_id=:desa_id, polres_id=:polres_id, $setPolsek, tanggal=:tanggal, nomor_lp=:nomor_lp, tersangka=:tersangka, permasalahan=:permasalahan, penanganan=:penanganan, tindak_lanjut=:tindak_lanjut, is_menonjol=:is_menonjol, sumber_id=:sumber_id, state=:state, tujuan=:tujuan, updated_at=NOW() WHERE id=:edit_id");
    $params = [
      ':kategori_id'=>$kategori_id,
      ':modus_operandi_id'=>$modus_operandi_id,
      ':jenis_tkp_id'=>$jenis_tkp_id,
      ':desa_id'=>$desa_id,
      ':polres_id'=>$polres_id,
      ':tanggal'=>$tanggal,
      ':nomor_lp'=>$nomor_lp,
      ':tersangka'=>$tersangka,
      ':permasalahan'=>$permasalahan,
      ':penanganan'=>$penanganan,
      ':tindak_lanjut'=>$tindak_lanjut,
      ':is_menonjol'=>$is_menonjol,
      ':sumber_id'=>$sumber_id,
      
      ':state'=>$state,
      ':tujuan'=>$tujuan,
      ':edit_id'=>$edit_id
    ];
    if(!is_null($polsek_id)) { $params[':polsek_id'] = $polsek_id; }
    $stmt->execute($params);
    $detailLog = kamtibmasLogDetail($dataOld, [
        'kategori_id'=>$kategori_id,
        'modus_operandi_id'=>$modus_operandi_id,
        'jenis_tkp_id'=>$jenis_tkp_id,
        'desa_id'=>$desa_id,
        'polres_id'=>$polres_id,
        'polsek_id'=>$polsek_id,
        'tanggal'=>$tanggal,
        'nomor_lp'=>$nomor_lp,
        'tersangka'=>$tersangka,
        'permasalahan'=>$permasalahan,
        'penanganan'=>$penanganan,
        'tindak_lanjut'=>$tindak_lanjut,
        'sumber_id'=>$sumber_id,
        'state'=>$state,
        'tujuan'=>$tujuan
    ], $username, $kategoriMap);
    logUser($pdo, $id_user, 'update', 'kamtibmas', $edit_id, $detailLog);
    header("Location: kamtibmas?status=edit_sukses");
    exit;
  }

  // --- Ambil data kamtibmas untuk diedit
  $q = $pdo->prepare("
    SELECT k.*, d.kecamatan_id, ke.kabupaten_id, k.polres_id, k.polsek_id
    FROM kamtibmass k
    LEFT JOIN desas d ON k.desa_id = d.id
    LEFT JOIN kecamatans ke ON d.kecamatan_id = ke.id
    WHERE k.id=:id
  ");
  $q->execute([':id'=>$id_kamtibmas]);
  $data = $q->fetch(PDO::FETCH_ASSOC);

  // Jika data tidak ditemukan, redirect/bisa beri pesan error
  if(!$data){ header("Location: kamtibmas?status=tak_ada");exit; }

  // --- Load pilihan dropdown terkait untuk pre-fill
  $kategoriList = $pdo->query("SELECT id, nama FROM kamtibmas_kategoris WHERE status=1 ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
  $modusList = $pdo->query("SELECT id, nama FROM modus_operandis WHERE status=1 ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
  $jenisTkpList = $pdo->query("SELECT id, nama FROM jenis_tkps WHERE status=1 ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
 
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
      $polsekList = $stmt_polsek->fetchAll(PDO::FETCH_ASSOC);
    } else {
      $stmt_polsek = $pdo->prepare("SELECT id, nama FROM polseks WHERE status=1 AND polres_id=? ORDER BY nama");
      $stmt_polsek->execute([$data['polres_id']]);
      $polsekList = $stmt_polsek->fetchAll(PDO::FETCH_ASSOC);
    }
    
  }
  $sumberList = $pdo->query("SELECT id, nama FROM sumbers WHERE status=1 AND tipe='KASUS MENONJOL' ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Kasus Menonjol | Peta Digital</title>
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
                                <h5 class="fs-0 mb-0"><span class="fa fa-edit me-2 fs-0"></span> Edit Data Kasus Menonjol</h5>
                            </div>
                            <div class="col-auto ms-auto">
                                <a href="kamtibmas" class="btn btn-falcon-default btn-sm">Kembali ke Daftar Kasus Menonjol</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                          <input type="hidden" name="edit_id" value="<?=$data['id']?>">
                           
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
                            <label class="form-label">Modus Operandi</label>
                            <select class="form-select form-select-sm" name="modus_operandi_id" required>
                              <option value="">- Pilih Modus Operandi -</option>
                              <?php foreach($modusList as $m): ?>
                                <option value="<?=$m['id']?>" <?=$data['modus_operandi_id']==$m['id']?'selected':''?>><?=htmlspecialchars($m['nama'])?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Jenis TKP</label>
                            <select class="form-select form-select-sm" name="jenis_tkp_id" required>
                              <option value="">- Pilih Jenis TKP -</option>
                              <?php foreach($jenisTkpList as $j): ?>
                                <option value="<?=$j['id']?>" <?=$data['jenis_tkp_id']==$j['id']?'selected':''?>><?=htmlspecialchars($j['nama'])?></option>
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
                            <label class="form-label">Tanggal Kejadian</label>
                            <input type="datetime-local" class="form-control form-control-sm" name="tanggal" value="<?=htmlspecialchars($data['tanggal'])?>" required>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Nomor LP</label>
                            <input type="text" class="form-control form-control-sm" name="nomor_lp" value="<?=htmlspecialchars($data['nomor_lp'])?>">
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Tersangka</label>
                            <textarea class="form-control form-control-sm" name="tersangka" rows="1"><?=htmlspecialchars($data['tersangka'])?></textarea>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Permasalahan / Kasus</label>
                            <textarea class="form-control form-control-sm" name="permasalahan" rows="2"><?=htmlspecialchars($data['permasalahan'])?></textarea>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Penanganan</label>
                            <textarea class="form-control form-control-sm" name="penanganan" rows="2"><?=htmlspecialchars($data['penanganan'])?></textarea>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Tindak Lanjut</label>
                            <textarea class="form-control form-control-sm" name="tindak_lanjut" rows="2"><?=htmlspecialchars($data['tindak_lanjut'])?></textarea>
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
                            <select class="form-select form-select-sm" name="state" required>
                              <option value="">- Pilih State -</option>
                              <option value="PROSES" <?=$data['state']=='PROSES'?'selected':''?>>PROSES</option>
                              <option value="SELESAI" <?=$data['state']=='SELESAI'?'selected':''?>>SELESAI</option>
                     
                            </select>
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
                          <button type="submit" class="btn btn-primary">Update</button>
                          <a href="kamtibmas" class="btn btn-secondary">Batal</a>
                        </form>
                    </div>
                </div>
            </div>
            <?php  
            // Folder simpan upload
                $upload_dir = realpath(__DIR__ . '/../public/upload/kamtibmas');
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true); // hanya sekali, lalu cek permission
                }
                if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['upload_attachment'])) {
                    $kamtibmas_id = $data['id'];
                    $file_name = $_FILES['attachment_file']['name'];
                    $tmp_name = $_FILES['attachment_file']['tmp_name'];
                    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed = ['pdf','jpg','jpeg','png'];
                    $keterangan = trim($_POST['attachment_keterangan']??'');

                    if(in_array($ext,$allowed) && is_uploaded_file($tmp_name)) {
                        $new_name = uniqid('kamtibmas_'.$kamtibmas_id.'_').'.'.$ext;
                        $move_path = $upload_dir . '/' . $new_name;
                        if(move_uploaded_file($tmp_name, $move_path)) {
                            $stmt = $pdo->prepare("INSERT INTO kamtibmas_attachments (nama, keterangan, jenis, file, kamtibmas_id, status, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
                            $stmt->execute([$file_name, $keterangan, $ext, $new_name, $kamtibmas_id]);
                            header("Location: kamtibmas-edit?id=$kamtibmas_id&upload=sukses");
                            echo "<meta http-equiv='refresh' content='0; url=kamtibmas-edit?id=$kamtibmas_id&upload=sukses'>";
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
                    $q = $pdo->prepare("SELECT file, kamtibmas_id FROM kamtibmas_attachments WHERE id=?");
                    $q->execute([$del_id]);
                    $f = $q->fetch();
                    if($f) {
                        @unlink($upload_dir . '/' . $f['file']);
                        $pdo->prepare("UPDATE kamtibmas_attachments SET status=0, updated_at=NOW() WHERE id=?")->execute([$del_id]);
                        //header("Location: kamtibmas_edit.php?id=".$f['kamtibmas_id']."&delete=ok");
                        echo "<meta http-equiv='refresh' content='0; url=kamtibmas-edit?id=".$f['kamtibmas_id']."&delete=ok'>";
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
                    <h5 class="fs-0 mb-0"><span class="fa fa-folder me-2 fs-0"></span> Data Berkas Tindak Lanjut</h5>
                    </div>
                <div class="card mb-3 border-success">
                    
                    <div class="card-body">
                    <!-- Form upload berkas -->
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
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
                    <ul class="list-group"  >
                        <?php
                        $lampiran = $pdo->prepare("SELECT * FROM kamtibmas_attachments WHERE status=1 AND kamtibmas_id=? ORDER BY created_at DESC");
                        $lampiran->execute([$data['id']]);
                        while($att = $lampiran->fetch()) {
                        $icon = "fa-file";
                        $jenis = strtolower(pathinfo($att['file'], PATHINFO_EXTENSION));
                        if($jenis=='pdf') $icon="fa-file-pdf text-danger";
                        elseif($jenis=='jpg'||$jenis=='jpeg'||$jenis=='png') $icon="fa-file-image text-info";
                        echo "<li class='list-group-item d-flex justify-content-between align-items-center' >
                            <span><span class='fa $icon me-2'></span>
                            <a href='../uploads/".$att['file']."' target='_blank'>".htmlspecialchars($att['nama'])."</a>
                            ".($att['keterangan']? "<span class='text-muted ms-1'>($att[keterangan])</span>":"")."
                            </span>
                            <form method='post'  >
                            <input type='hidden' name='csrf_token' value='".htmlspecialchars($_SESSION['csrf_token'])."'>
                            <input type='hidden' name='delete_attachment_id' value='$att[id]'>
                            <button type='submit' name='delete_attachment' class='btn btn-sm btn-danger ' title='Hapus lampiran' onclick=\"return confirm('Hapus berkas ini?')\"><i class='fa fa-times'></i></button>
                            </form>
                        </li>";
                        }
                        ?>
                        <?php if($lampiran->rowCount()==0) echo "<li class='list-group-item text-muted small'>Belum ada berkas terupload.</li>"; ?>
                    </ul>
                    </div>
                  </div>
                  <?php
                    $logHist = $pdo->prepare("
                        SELECT l.*, u.nama AS user_nama, u.username
                        FROM user_logs l
                        LEFT JOIN users u ON l.user_id = u.id
                        WHERE l.modul = 'kamtibmas' AND l.data_id = ?
                        ORDER BY l.created_at DESC
                    ");
                    $logHist->execute([$data['id']]);
                    ?>
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
  <script src="../assets/js/kamtibmas-edit.js"></script>
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
 