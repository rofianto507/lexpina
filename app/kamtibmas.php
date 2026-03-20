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
  $_SESSION["menu"]="kamtibmas";
  $menu=$_SESSION["menu"];
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
        header("Location: kemtibmas?status=csrf_failed");
        exit;
    }
  }
 // Proses hapus konflik (soft/hard delete, pilih salah satu)
 // Proses hapus kamtibmas (soft/hard delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_id'])) {
    $hapus_id = intval($_POST['hapus_id']);
    if($hapus_id){
        // Soft Delete: ubah status = 0
        $pdo->prepare("UPDATE kamtibmass SET status=0, updated_at=NOW() WHERE id=?")->execute([$hapus_id]);
        // Jika ingin hard delete, gunakan query: DELETE FROM kamtibmass WHERE id=?
        header("Location: kamtibmas?status=hapus_sukses");
        exit;
    }
}
// proses tambah data kamtibmas
if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['kategori_id'],$_POST['modus_operandi_id'],$_POST['jenis_tkp_id'],$_POST['desa_id'],$_POST['polres_id'],$_POST['tanggal'])) {
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
    $sumber_id = intval($_POST['sumber_id']);
    $user_id = $id; // dari session login
    $tanggal = $_POST['tanggal'];
    $nomor_lp = trim($_POST['nomor_lp'] ?? '');
    $tersangka = trim($_POST['tersangka'] ?? '');
    $permasalahan = trim($_POST['permasalahan'] ?? '');
    $penanganan = trim($_POST['penanganan'] ?? '');
    $tindak_lanjut = trim($_POST['tindak_lanjut'] ?? '');
    $state = $_POST['state'] ?? 'PROSES';
    $tujuan = $_POST['tujuan'] ?? '';
    $is_menonjol = 1;

    if($kategori_id && $modus_operandi_id && $jenis_tkp_id && $desa_id && $polres_id && $tanggal){
        $stmt = $pdo->prepare("INSERT INTO kamtibmass (kategori_id, modus_operandi_id, jenis_tkp_id, desa_id, polres_id, polsek_id, sumber_id, user_id, tanggal, nomor_lp, tersangka, permasalahan, penanganan, tindak_lanjut, is_menonjol, state, created_at, tujuan)
            VALUES (:kategori_id, :modus_operandi_id, :jenis_tkp_id, :desa_id, :polres_id, :polsek_id, :sumber_id, :user_id, :tanggal, :nomor_lp, :tersangka, :permasalahan, :penanganan, :tindak_lanjut, :is_menonjol, :state, NOW(), :tujuan)");
        $stmt->execute([
            ':kategori_id'=>$kategori_id,
            ':modus_operandi_id'=>$modus_operandi_id,
            ':jenis_tkp_id'=>$jenis_tkp_id,
            ':desa_id'=>$desa_id,
            ':polres_id'=>$polres_id,
            ':polsek_id'=>$polsek_id,
            ':sumber_id'=>$sumber_id,
            ':user_id'=>$user_id,
            ':tanggal'=>$tanggal,
            ':nomor_lp'=>$nomor_lp,
            ':tersangka'=>$tersangka,
            ':permasalahan'=>$permasalahan,
            ':penanganan'=>$penanganan,
            ':tindak_lanjut'=>$tindak_lanjut,
            ':is_menonjol'=>$is_menonjol,
            ':state'=>$state,
            ':tujuan'=>$tujuan
        ]);
        $data_id = $pdo->lastInsertId();
        logUser($pdo, $id, 'add', 'kamtibmas', $data_id, 'Tambah oleh '.$username);
        header("Location: kamtibmas?status=sukses");
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
    <title>Data Kasus Menonjol | Peta Digital</title>

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
  
  </head>
  <body data-polsek-id="<?php echo htmlspecialchars($_SESSION['polsek_id'] ?? '', ENT_QUOTES); ?>">
    <main class="main" id="top">
      <div class="container-fluid" data-layout="container">
        <?php include_once("navbar.php") ?>
        <div class="content">
          <?php include_once("header.php") ?>
          <?php if(isset($_GET['status']) && $_GET['status']=='sukses'): ?>
          <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            Data kamtibmas berhasil ditambahkan.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          
          <?php if(isset($_GET['status']) && $_GET['status']=='hapus_sukses'): ?>
          <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            Data kamtibmas berhasil dihapus.
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
                  <h5 class="fs-0 mb-0"><span class="fas fa-mask me-2 fs-0"></span>Data Kasus Menonjol</h5>
                </div>
                <div class="col-auto ms-auto">
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahKamtibmas">
                  Tambah Data
                </button>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="kamtibmasTable" class="display table table-striped table-bordered table-sm" >
                  <thead class="bg-primary text-white">
                    <tr>
                      <th>ID</th>
                      <th>Tanggal</th>
                      <th>Kategori</th>
                      <th>Modus</th>
                      <th>Jenis TKP</th>
                      <th>Permasalahan</th>
                      <th>Desa</th>
                      <th>Polres</th>
                      <th>Polsek</th>
                      <th>Sumber</th>
                      <th>State</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php
                  if($akses == "POLRES") {
                      $query = "SELECT
                        k.id,
                        k.tanggal,
                        ka.nama AS kategori_nama,
                        mo.nama AS modus_nama,
                        jt.nama AS jenis_tkp_nama,
                        k.permasalahan,
                        d.nama AS desa_nama,
                        po.nama AS polres_nama,
                        ps.nama AS polsek_nama,
                        s.nama AS sumber_nama,
                        k.is_menonjol,
                        k.state
                      FROM kamtibmass k
                      LEFT JOIN kamtibmas_kategoris ka ON k.kategori_id = ka.id
                      LEFT JOIN modus_operandis mo ON k.modus_operandi_id = mo.id
                      LEFT JOIN jenis_tkps jt ON k.jenis_tkp_id = jt.id
                      LEFT JOIN desas d ON k.desa_id = d.id
                      LEFT JOIN polress po ON k.polres_id = po.id
                      LEFT JOIN polseks ps ON k.polsek_id = ps.id
                      LEFT JOIN sumbers s ON k.sumber_id = s.id
                      WHERE k.status=1 AND k.polres_id=$polres_id
                      ORDER BY k.id DESC";
                  } else if($akses == "POLSEK") {
                     $query = "SELECT
                      k.id,
                      k.tanggal,
                      ka.nama AS kategori_nama,
                      mo.nama AS modus_nama,
                      jt.nama AS jenis_tkp_nama,
                      k.permasalahan,
                      d.nama AS desa_nama,
                      po.nama AS polres_nama,
                      ps.nama AS polsek_nama,
                      s.nama AS sumber_nama,
                      k.is_menonjol,
                      k.state
                    FROM kamtibmass k
                    LEFT JOIN kamtibmas_kategoris ka ON k.kategori_id = ka.id
                    LEFT JOIN modus_operandis mo ON k.modus_operandi_id = mo.id
                    LEFT JOIN jenis_tkps jt ON k.jenis_tkp_id = jt.id
                    LEFT JOIN desas d ON k.desa_id = d.id
                    LEFT JOIN polress po ON k.polres_id = po.id
                    LEFT JOIN polseks ps ON k.polsek_id = ps.id
                    LEFT JOIN sumbers s ON k.sumber_id = s.id
                    WHERE k.status=1 AND k.polsek_id=$polsek_id
                    ORDER BY k.id DESC";
                  } else if($akses == "POLDA") {
                    $query = "SELECT
                      k.id,
                      k.tanggal,
                      ka.nama AS kategori_nama,
                      mo.nama AS modus_nama,
                      jt.nama AS jenis_tkp_nama,
                      k.permasalahan,
                      d.nama AS desa_nama,
                      po.nama AS polres_nama,
                      ps.nama AS polsek_nama,
                      s.nama AS sumber_nama,
                      k.is_menonjol,
                      k.state
                    FROM kamtibmass k
                    LEFT JOIN kamtibmas_kategoris ka ON k.kategori_id = ka.id
                    LEFT JOIN modus_operandis mo ON k.modus_operandi_id = mo.id
                    LEFT JOIN jenis_tkps jt ON k.jenis_tkp_id = jt.id
                    LEFT JOIN desas d ON k.desa_id = d.id
                    LEFT JOIN polress po ON k.polres_id = po.id
                    LEFT JOIN polseks ps ON k.polsek_id = ps.id
                    LEFT JOIN sumbers s ON k.sumber_id = s.id
                    WHERE k.status=1
                    ORDER BY k.id DESC";
                  }else {
                    $query = "SELECT
                      k.id,
                      k.tanggal,
                      ka.nama AS kategori_nama,
                      mo.nama AS modus_nama,
                      jt.nama AS jenis_tkp_nama,
                      k.permasalahan,
                      d.nama AS desa_nama,
                      po.nama AS polres_nama,
                      ps.nama AS polsek_nama,
                      s.nama AS sumber_nama,
                      k.is_menonjol,
                      k.state
                    FROM kamtibmass k
                    LEFT JOIN kamtibmas_kategoris ka ON k.kategori_id = ka.id
                    LEFT JOIN modus_operandis mo ON k.modus_operandi_id = mo.id
                    LEFT JOIN jenis_tkps jt ON k.jenis_tkp_id = jt.id
                    LEFT JOIN desas d ON k.desa_id = d.id
                    LEFT JOIN polress po ON k.polres_id = po.id
                    LEFT JOIN polseks ps ON k.polsek_id = ps.id
                    LEFT JOIN sumbers s ON k.sumber_id = s.id
                    WHERE k.status=1 and k.tujuan='{$akses}'
                    ORDER BY k.id DESC";
                  }
                    $stmt = $pdo->query($query);
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      echo "<tr>
                        <td>".$row['id']."</td>
                        <td>".$row['tanggal']."</td>
                        <td>".$row['kategori_nama']."</td>
                        <td>".$row['modus_nama']."</td>
                        <td>".$row['jenis_tkp_nama']."</td>
                        <td>".(mb_strlen($row['permasalahan']) > 100 ? mb_substr($row['permasalahan'], 0, 100).'...' : $row['permasalahan'])."</td>
                        <td>".$row['desa_nama']."</td>
                        <td>".$row['polres_nama']."</td>
                        <td>".$row['polsek_nama']."</td>
                        <td>".$row['sumber_nama']."</td>
                         <td><span class='badge bg-".($row['state'] == 'SELESAI' ? 'success' : 'warning')."'>".$row['state']."</span></td>
                        <td>
                          <a href='kamtibmas-edit?id=".$row['id']."' class='btn btn-sm btn-info'>Edit</a>
                          <button class='btn btn-sm btn-danger btnHapusKamtibmas' data-id='".$row['id']."' data-permasalahan='".htmlspecialchars($row['permasalahan'],ENT_QUOTES)."'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <!-- Modal Konfirmasi Hapus Kamtibmas -->
            <div class="modal fade" id="modalHapusKamtibmas" tabindex="-1" aria-labelledby="modalHapusKamtibmasLabel" aria-hidden="true">
              <div class="modal-dialog">
                <form method="POST" id="formHapusKamtibmas">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                  <input type="hidden" name="hapus_id" id="hapus_id_kamtibmas">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalHapusKamtibmasLabel">Konfirmasi Hapus</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <p>Yakin ingin menghapus data kamtibmas dengan permasalahan: <b id="hapus_permasalahan"></b>?</p>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                      <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>

          <!-- Modal Tambah Data Kamtibmas -->
          <div class="modal fade" id="modalTambahKamtibmas" tabindex="-1" aria-labelledby="modalTambahKamtibmasLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <form method="POST" id="formTambahKamtibmas">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahKamtibmasLabel">Tambah Data Kasus Menonjol</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                  </div>
                  <div class="modal-body">
                     
                    <div class="mb-3">
                      <label class="form-label">Kategori</label>
                      <select class="form-select form-select-sm" name="kategori_id" required>
                        <option value="">- Pilih Kategori -</option>
                        <?php
                        $kategori = $pdo->query("SELECT id, nama FROM kamtibmas_kategoris WHERE status=1 ORDER BY nama");
                        while($k = $kategori->fetch()) {
                          echo "<option value='{$k['id']}'>".htmlspecialchars($k['nama'])."</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Modus Operandi</label>
                      <select class="form-select form-select-sm" name="modus_operandi_id" required>
                        <option value="">- Pilih Modus Operandi -</option>
                        <?php
                        $modus = $pdo->query("SELECT id, nama FROM modus_operandis WHERE status=1 ORDER BY nama");
                        while($m = $modus->fetch()) {
                          echo "<option value='{$m['id']}'>".htmlspecialchars($m['nama'])."</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Jenis TKP</label>
                      <select class="form-select form-select-sm" name="jenis_tkp_id" required>
                        <option value="">- Pilih Jenis TKP -</option>
                        <?php
                        $tkp = $pdo->query("SELECT id, nama FROM jenis_tkps WHERE status=1 ORDER BY nama");
                        while($j = $tkp->fetch()) {
                          echo "<option value='{$j['id']}'>".htmlspecialchars($j['nama'])."</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <!-- Cascade Kabupaten - Kecamatan - Desa -->
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
                          echo "<option value='{$p['id']}'>".htmlspecialchars($p['nama'])."</option>";
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
                      <label class="form-label">Tanggal Kejadian</label>
                      <input type="datetime-local" class="form-control form-control-sm" name="tanggal" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Nomor LP</label>
                      <input type="text" class="form-control form-control-sm" name="nomor_lp" placeholder="(jika ada)">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Tersangka</label>
                      <textarea class="form-control form-control-sm" name="tersangka" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Permasalahan / Kasus</label>
                      <textarea class="form-control form-control-sm" name="permasalahan" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Penanganan</label>
                      <textarea class="form-control form-control-sm" name="penanganan" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Tindak Lanjut</label>
                      <textarea class="form-control form-control-sm" name="tindak_lanjut" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Sumber Dokumen</label>
                      <select class="form-select form-select-sm" name="sumber_id" required>
                        <option value="">- Pilih Sumber -</option>
                        <?php
                        $sumber = $pdo->query("SELECT id, nama FROM sumbers WHERE status=1 and tipe='KASUS MENONJOL' ORDER BY nama");
                        while($s = $sumber->fetch()) {
                          echo "<option value='{$s['id']}'>".htmlspecialchars($s['nama'])."</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="mb-3">
                          <label class="form-label">State</label>
                          <select class="form-select form-select-sm" name="state" required>
                            <option value="PROSES" selected>PROSES</option>
                            <option value="SELESAI">SELESAI</option>                     
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
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                  </div>
                </div>
              </form>
            </div>
          </div>
          <?php include_once("footer.php") ?>
        </div>
      </div>
    </main>
    <!-- Scripts -->
    <script src="../vendors/jquery/jquery-3.7.0.min.js"></script>
    <script src="../vendors/datatables/datatables.min.js"></script>
    <script src="../assets/js/kamtibmas.js"></script>
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
 