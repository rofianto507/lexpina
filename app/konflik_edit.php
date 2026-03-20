<?php
session_start();
include("../config/configuration.php");

if($_SESSION["nama"]!="" && $_SESSION["id"]!=""){
  $_SESSION["menu"]="konflik";
    $menu=$_SESSION["menu"];
  $id_user=$_SESSION["id"];
  // --- Ambil id konflik dari GET
  $id_konflik = isset($_GET['id']) ? intval($_GET['id']) : 0;

  // Handle update saat submit
  if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $kategori_id = intval($_POST['kategori_id']);
    $desa_id = intval($_POST['desa_id']);
    $lokasi = trim($_POST['lokasi']);
    $permasalahan = trim($_POST['permasalahan']);
    $uraian = trim($_POST['uraian'] ?? '');
    $penanganan = trim($_POST['penanganan'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $polres_id = intval($_POST['polres_id']);
    $polsek_id = intval($_POST['polsek_id']);
    $sumber_id = intval($_POST['sumber_id']);

    // Update ke tabel konfliks
    $stmt = $pdo->prepare("UPDATE konfliks SET kategori_id=:kategori_id, desa_id=:desa_id, polres_id=:polres_id, polsek_id=:polsek_id, lokasi=:lokasi, permasalahan=:permasalahan, uraian=:uraian, penanganan=:penanganan, keterangan=:keterangan, sumber_id=:sumber_id, updated_at=NOW() WHERE id=:edit_id");
    $stmt->execute([
      ':kategori_id'=>$kategori_id,
      ':desa_id'=>$desa_id,
      ':polres_id'=>$polres_id,
      ':polsek_id'=>$polsek_id,
      ':lokasi'=>$lokasi,
      ':permasalahan'=>$permasalahan,
      ':uraian'=>$uraian,
      ':penanganan'=>$penanganan,
      ':keterangan'=>$keterangan,
      ':sumber_id'=>$sumber_id,
      ':edit_id'=>$edit_id
    ]);
    header("Location: konflik?status=edit_sukses");
    exit;
  }

  // --- Ambil data konflik untuk diedit
  $q = $pdo->prepare("
    SELECT k.*, d.kecamatan_id, ke.kabupaten_id, k.polres_id, k.polsek_id
    FROM konfliks k
    LEFT JOIN desas d ON k.desa_id = d.id
    LEFT JOIN kecamatans ke ON d.kecamatan_id = ke.id
    WHERE k.id=:id
  ");
  $q->execute([':id'=>$id_konflik]);
  $data = $q->fetch(PDO::FETCH_ASSOC);

  // Jika data tidak ditemukan, redirect/bisa beri pesan error
  if(!$data){ header("Location: konflik?status=tak_ada");exit; }

  // --- Load pilihan dropdown terkait untuk pre-fill
  // 1. Semua kategori
  $kategoriList = $pdo->query("SELECT id, nama FROM konflik_kategoris WHERE status=1 ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
  // 2. Semua kabupaten
  $kabList = $pdo->query("SELECT id, nama, kode FROM kabupatens WHERE status=1 ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
  // 3. Kecamatan dari kabupaten sekarang
  $kecList = [];
  if($data['kabupaten_id']){
    $stmt_kec = $pdo->prepare("SELECT id, nama, kode FROM kecamatans WHERE status=1 AND kabupaten_id=? ORDER BY nama");
    $stmt_kec->execute([$data['kabupaten_id']]);
    $kecList = $stmt_kec->fetchAll(PDO::FETCH_ASSOC);
  }
  // 4. Desa dari kecamatan sekarang
  $desaList = [];
  if($data['kecamatan_id']){
    $stmt_desa = $pdo->prepare("SELECT id, nama, kode FROM desas WHERE status=1 AND kecamatan_id=? ORDER BY nama");
    $stmt_desa->execute([$data['kecamatan_id']]);
    $desaList = $stmt_desa->fetchAll(PDO::FETCH_ASSOC);
  }
  // 5. Semua polres
  $polresList = $pdo->query("SELECT id, nama FROM polress WHERE status=1 ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
  // 6. Polsek dari polres sekarang
  $polsekList = [];
  if($data['polres_id']){
    $stmt_polsek = $pdo->prepare("SELECT id, nama FROM polseks WHERE status=1 AND polres_id=? ORDER BY nama");
    $stmt_polsek->execute([$data['polres_id']]);
    $polsekList = $stmt_polsek->fetchAll(PDO::FETCH_ASSOC);
  }
  // 7. Semua sumber dokumen
  $sumberList = $pdo->query("SELECT id, nama FROM sumbers WHERE status=1 AND tipe='KONFLIK' ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);  

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Konflik | Peta Digital</title>
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
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">
    <link href="../vendors/overlayscrollbars/OverlayScrollbars.min.css" rel="stylesheet">
    <link href="../assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
    <link href="../assets/css/theme.min.css" rel="stylesheet" id="style-default">
    <link href="../assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
    <link href="../assets/css/user.min.css" rel="stylesheet" id="user-style-default">
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
      <?php include_once("navbar.php"); ?>
      <div class="content">
        <?php include_once("header.php"); ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <div class="row flex-between-end">
                            <div class="col-auto align-self-center">
                                <h5 class="fs-0 mb-0"><span class="fas fa-edit me-2 fs-0"></span> Edit Data Konflik</h5>
                            </div>
                            <div class="col-auto ms-auto">
                                <a href="konflik" class="btn btn-falcon-default btn-sm">Kembali ke Daftar Konflik</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                        <input type="hidden" name="edit_id" value="<?=$data['id']?>">
                        <div class="mb-3">
                            <label class="form-label">Kategori Konflik</label>
                            <select class="form-select form-select-sm" name="kategori_id" required>
                            <option value="">- Pilih Kategori -</option>
                            <?php foreach($kategoriList as $k): ?>
                                <option value="<?=$k['id']?>" <?=$data['kategori_id']==$k['id']?'selected':''?>><?=htmlspecialchars($k['nama'])?></option>
                            <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Permasalahan</label>
                            <input type="text" class="form-control form-control-sm" name="permasalahan" value="<?=htmlspecialchars($data['permasalahan'])?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Lokasi</label>
                            <input type="text" class="form-control form-control-sm" name="lokasi" value="<?=htmlspecialchars($data['lokasi'])?>" required>
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
                            <label class="form-label">Polsek</label>
                            <select class="form-select form-select-sm" name="polsek_id" id="polsek_id" required>
                            <option value="">- Pilih Polsek -</option>
                            <?php foreach($polsekList as $psk): ?>
                                <option value="<?=$psk['id']?>" <?=$data['polsek_id']==$psk['id']?'selected':''?>><?=htmlspecialchars($psk['nama'])?></option>
                            <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Uraian</label>
                            <textarea class="form-control form-control-sm" name="uraian" rows="10"><?=htmlspecialchars($data['uraian'])?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Penanganan</label>
                            <textarea class="form-control form-control-sm" name="penanganan" rows="10"><?=htmlspecialchars($data['penanganan'])?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control form-control-sm" name="keterangan" rows="10"><?=htmlspecialchars($data['keterangan'])?></textarea>
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
                        <button type="submit" class="btn btn-primary">Update</button>
                        <a href="konflik.php" class="btn btn-secondary">Batal</a>
                        </form>
                    </div>
                </div>
            </div>
            <?php  
            // Folder simpan upload
                $upload_dir = realpath(__DIR__ . '/../public/upload/konflik');
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true); // hanya sekali, lalu cek permission
                }
                if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['upload_attachment'])) {
                    $konflik_id = $data['id'];
                    $file_name = $_FILES['attachment_file']['name'];
                    $tmp_name = $_FILES['attachment_file']['tmp_name'];
                    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed = ['pdf','jpg','jpeg','png'];
                    $keterangan = trim($_POST['attachment_keterangan']??'');

                    if(in_array($ext,$allowed) && is_uploaded_file($tmp_name)) {
                        $new_name = uniqid('konflik_'.$konflik_id.'_').'.'.$ext;
                        $move_path = $upload_dir . '/' . $new_name;
                        if(move_uploaded_file($tmp_name, $move_path)) {
                            $stmt = $pdo->prepare("INSERT INTO konflik_attachments (nama, keterangan, jenis, file, konflik_id, status, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
                            $stmt->execute([$file_name, $keterangan, $ext, $new_name, $konflik_id]);
                            header("Location: konflik-edit?id=$konflik_id&upload=sukses");
                            echo "<meta http-equiv='refresh' content='0; url=konflik-edit?id=$konflik_id&upload=sukses'>";
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
                    $q = $pdo->prepare("SELECT file, konflik_id FROM konflik_attachments WHERE id=?");
                    $q->execute([$del_id]);
                    $f = $q->fetch();
                    if($f) {
                        @unlink($upload_dir . '/' . $f['file']);
                        $pdo->prepare("UPDATE konflik_attachments SET status=0, updated_at=NOW() WHERE id=?")->execute([$del_id]);
                        //header("Location: konflik_edit.php?id=".$f['konflik_id']."&delete=ok");
                        echo "<meta http-equiv='refresh' content='0; url=konflik-edit?id=".$f['konflik_id']."&delete=ok'>";
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
                    <h5 class="fs-0 mb-0"><span class="fas fa-folder me-2 fs-0"></span> Data Berkas</h5>
                    </div>
                <div class="card mb-3 border-success">
                    
                    <div class="card-body">
                    <!-- Form upload berkas -->
                    <form method="post" enctype="multipart/form-data">
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
                    <h6><span class="fas fa-paperclip me-1"></span> Lampiran:</h6>
                    <ul class="list-group" style="margin-bottom:0">
                        <?php
                        $lampiran = $pdo->prepare("SELECT * FROM konflik_attachments WHERE status=1 AND konflik_id=? ORDER BY created_at DESC");
                        $lampiran->execute([$data['id']]);
                        while($att = $lampiran->fetch()) {
                        $icon = "fa-file";
                        $jenis = strtolower(pathinfo($att['file'], PATHINFO_EXTENSION));
                        if($jenis=='pdf') $icon="fa-file-pdf text-danger";
                        elseif($jenis=='jpg'||$jenis=='jpeg'||$jenis=='png') $icon="fa-file-image text-info";
                        echo "<li class='list-group-item d-flex justify-content-between align-items-center' style='font-size:15px'>
                            <span><span class='fas $icon me-2'></span>
                            <a href='../uploads/".$att['file']."' target='_blank'>".htmlspecialchars($att['nama'])."</a>
                            ".($att['keterangan']? "<span class='text-muted ms-1'>($att[keterangan])</span>":"")."
                            </span>
                            <form method='post' style='display:inline;margin:0'>
                            <input type='hidden' name='delete_attachment_id' value='$att[id]'>
                            <button type='submit' name='delete_attachment' class='btn btn-xs btn-danger btn-sm' title='Hapus lampiran' onclick=\"return confirm('Hapus berkas ini?')\"><i class='fas fa-times'></i></button>
                            </form>
                        </li>";
                        }
                        ?>
                        <?php if($lampiran->rowCount()==0) echo "<li class='list-group-item text-muted small'>Belum ada berkas terupload.</li>"; ?>
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
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script>
  // Kabupaten → Kecamatan
  $('#kabupaten_id').change(function() {
    var kabupatenId = $(this).val();
    $('#kecamatan_id').html('<option value="">- Pilih Kecamatan -</option>');
    $('#desa_id').html('<option value="">- Pilih Desa -</option>');
    if(kabupatenId) {
      $.get('get_kecamatan.php', {kabupaten_id: kabupatenId}, function(data) {
        var opt = '<option value="">- Pilih Kecamatan -</option>';
        $.each(data, function(i, v) { opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>'; });
        $('#kecamatan_id').html(opt);
      }, 'json');
    }
  });

  // Kecamatan → Desa
  $('#kecamatan_id').change(function() {
    var kecamatanId = $(this).val();
    $('#desa_id').html('<option value="">- Pilih Desa -</option>');
    if(kecamatanId) {
      $.get('get_desa.php', {kecamatan_id: kecamatanId}, function(data) {
        var opt = '<option value="">- Pilih Desa -</option>';
        $.each(data, function(i, v) { opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>'; });
        $('#desa_id').html(opt);
      }, 'json');
    }
  });

  // Polres → Polsek
  $('#polres_id').change(function() {
    var polresId = $(this).val();
    $('#polsek_id').html('<option value="">- Pilih Polsek -</option>');
    if(polresId) {
      $.get('get_polsek.php', {polres_id: polresId}, function(data) {
        var opt = '<option value="">- Pilih Polsek -</option>';
        $.each(data, function(i, v) { opt += '<option value="'+v.id+'">'+v.nama+'</option>'; });
        $('#polsek_id').html(opt);
      }, 'json');
    }
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
} else {
  header("Location: ../index");
}
?>