<?php
session_start();
 
include("../config/configuration.php");
if(!isset($_SESSION["id"]) || !isset($_SESSION["nama"])) {
  header("Location: ../login/");
  exit;
}
 if(empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  $_SESSION["menu"]="produk";
 
  $menu=$_SESSION["menu"];
  $nama=$_SESSION["nama"];
  $id=$_SESSION["id"];
  $username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
  $foto=$_SESSION["foto"];
  $last_login=$_SESSION["last_login"];
 
// ==========================================
// PROSES PENANGANAN FORM (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: produk?msg=csrf_failed");
        exit;
    }

    $action = $_POST['action'] ?? '';

    try {
        // --- 1. PROSES TAMBAH ---
        if ($action == 'tambah') {
            $nama_paket     = $_POST['nama_paket'];
            $deskripsi      = $_POST['deskripsi'];
            $durasi_bulan   = (int)$_POST['durasi_bulan'];
            $harga_coret    = !empty($_POST['harga_coret']) ? (int)$_POST['harga_coret'] : NULL;
            $harga_per_bln  = (int)$_POST['harga_per_bulan'];
            $total_bayar    = (int)$_POST['total_bayar'];
            $badge          = $_POST['badge'];

            $stmt = $pdo->prepare("INSERT INTO produks (nama_paket, deskripsi, durasi_bulan, harga_coret, harga_per_bulan, total_bayar, badge, status) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$nama_paket, $deskripsi, $durasi_bulan, $harga_coret, $harga_per_bln, $total_bayar, $badge]);
            
            header("Location: produk?msg=sukses_tambah");
            exit;
        }

        // --- 2. PROSES EDIT ---
        if ($action == 'edit') {
            $id_produk      = (int)$_POST['id'];
            $nama_paket     = $_POST['nama_paket'];
            $deskripsi      = $_POST['deskripsi'];
            $durasi_bulan   = (int)$_POST['durasi_bulan'];
            $harga_coret    = !empty($_POST['harga_coret']) ? (int)$_POST['harga_coret'] : NULL;
            $harga_per_bln  = (int)$_POST['harga_per_bulan'];
            $total_bayar    = (int)$_POST['total_bayar'];
            $badge          = $_POST['badge'];

            $stmt = $pdo->prepare("UPDATE produks SET nama_paket = ?, deskripsi = ?, durasi_bulan = ?, harga_coret = ?, harga_per_bulan = ?, total_bayar = ?, badge = ? WHERE id = ?");
            $stmt->execute([$nama_paket, $deskripsi, $durasi_bulan, $harga_coret, $harga_per_bln, $total_bayar, $badge, $id_produk]);
            
            header("Location: produk?msg=sukses_edit");
            exit;
        }

        // --- 3. PROSES HAPUS (SOFT DELETE) ---
        if ($action == 'hapus') {
            $hapus_id = (int)$_POST['hapus_id'];
            $stmt = $pdo->prepare("UPDATE produks SET status = 0 WHERE id = ?");
            $stmt->execute([$hapus_id]);
            
            header("Location: produk?msg=sukses_hapus");
            exit;
        }

    } catch (PDOException $e) {
        die("Error memproses data: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Produk | LexPina</title>

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
        <link href="../assets/css/database.css" rel="stylesheet">
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
  <body  >
    <main class="main" id="top">
      <div class="container-fluid" data-layout="container">
        <?php include_once("navbar.php") ?>
        <div class="content">
          <?php include_once("header.php") ?>

          <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <?php
                if($_GET['msg'] == 'sukses_tambah') echo "Produk baru berhasil ditambahkan.";
                elseif($_GET['msg'] == 'sukses_edit') echo "Data produk berhasil diperbarui.";
                elseif($_GET['msg'] == 'sukses_hapus') echo "Produk berhasil dihapus.";
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <div class="card mb-3 mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0"><span class="fa fa-cube me-2 text-primary"></span>Master Produk Langganan</h5>
               
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="produkTable" class="display table table-striped table-bordered table-sm" style="width:100%">
                  <thead class="bg-primary text-white">
                    <tr>
                      <th>Nama Paket</th>
                      <th>Durasi</th>
                      <th>Harga/Bulan</th>
                      <th>Total Bayar</th>
                      <th>Badge</th>
                      <th class="text-center">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php
                    $stmt = $pdo->query("SELECT * FROM produks WHERE status = 1 ORDER BY total_bayar ASC");
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      echo "<tr>
                        <td class='fw-bold'>".htmlspecialchars($row['nama_paket'])."</td>
                        <td>".$row['durasi_bulan']." Bulan</td>
                        <td>Rp ".number_format($row['harga_per_bulan'], 0, ',', '.')."</td>
                        <td class='text-primary fw-bold'>Rp ".number_format($row['total_bayar'], 0, ',', '.')."</td>
                        <td><span class='badge rounded-pill bg-soft-info text-info'>".htmlspecialchars($row['badge'] ?? '')."</span></td>
                        <td class='text-center'>
                          <button class='btn btn-sm btn-info text-white' onclick='editProduk(".json_encode($row).")'>Edit</button>
                          <button class='btn btn-sm btn-danger' onclick='hapusProduk(".$row['id'].", \"".htmlspecialchars($row['nama_paket'], ENT_QUOTES)."\")'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="modal fade" id="modalProduk" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" id="form_action" value="tambah">
                <input type="hidden" name="id" id="form_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Produk Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="row g-3">
                      <div class="col-md-8">
                        <label class="form-label">Nama Paket</label>
                        <input type="text" class="form-control" name="nama_paket" id="nama_paket" required>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Durasi (Bulan)</label>
                        <input type="number" class="form-control" name="durasi_bulan" id="durasi_bulan" required oninput="hitungTotal()">
                      </div>
                      <div class="col-md-12">
                        <label class="form-label">Deskripsi Singkat</label>
                        <input type="text" class="form-control" name="deskripsi" id="deskripsi">
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Harga Coret (Opsional)</label>
                        <input type="number" class="form-control" name="harga_coret" id="harga_coret">
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Harga / Bulan</label>
                        <input type="number" class="form-control" name="harga_per_bulan" id="harga_per_bulan" required oninput="hitungTotal()">
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Total Bayar</label>
                        <input type="number" class="form-control bg-soft-primary fw-bold" name="total_bayar" id="total_bayar" required>
                      </div>
                      <div class="col-md-12">
                        <label class="form-label">Badge (Misal: Best Seller / Populer)</label>
                        <input type="text" class="form-control" name="badge" id="badge" placeholder="Kosongkan jika tidak ada">
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmit">Simpan Produk</button>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <div class="modal fade" id="modalHapus" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-sm">
              <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="hapus">
                <input type="hidden" name="hapus_id" id="hapus_id">
                <div class="modal-content">
                  <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title">Konfirmasi Hapus</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body text-center">
                    <p>Hapus paket <br><strong id="label_hapus"></strong>?</p>
                  </div>
                  <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger">Ya, Hapus</button>
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
     <script>
      $(document).ready(function() {
        $('#produkTable').DataTable({ "autoWidth": false });
      });

      function hitungTotal() {
        const durasi = document.getElementById('durasi_bulan').value;
        const harga = document.getElementById('harga_per_bulan').value;
        if (durasi && harga) {
          document.getElementById('total_bayar').value = durasi * harga;
        }
      }

      function clearForm() {
        document.getElementById('form_action').value = 'tambah';
        document.getElementById('modalTitle').innerText = 'Tambah Produk Baru';
        document.getElementById('btnSubmit').innerText = 'Simpan Produk';
        document.getElementById('form_id').value = '';
        ['nama_paket', 'durasi_bulan', 'deskripsi', 'harga_coret', 'harga_per_bulan', 'total_bayar', 'badge'].forEach(id => {
          document.getElementById(id).value = '';
        });
      }

      function editProduk(data) {
        document.getElementById('form_action').value = 'edit';
        document.getElementById('modalTitle').innerText = 'Edit Produk';
        document.getElementById('btnSubmit').innerText = 'Update Produk';
        document.getElementById('form_id').value = data.id;
        document.getElementById('nama_paket').value = data.nama_paket;
        document.getElementById('durasi_bulan').value = data.durasi_bulan;
        document.getElementById('deskripsi').value = data.deskripsi;
        document.getElementById('harga_coret').value = data.harga_coret;
        document.getElementById('harga_per_bulan').value = data.harga_per_bulan;
        document.getElementById('total_bayar').value = data.total_bayar;
        document.getElementById('badge').value = data.badge;
        $('#modalProduk').modal('show');
      }

      function hapusProduk(id, nama) {
        document.getElementById('hapus_id').value = id;
        document.getElementById('label_hapus').innerText = nama;
        $('#modalHapus').modal('show');
      }
    </script>           
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
 