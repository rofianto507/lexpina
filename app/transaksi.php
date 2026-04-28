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
// Menangkap status dari URL (Default ke PENDING jika kosong)
$status_transaksi = isset($_GET["status"]) ? strtoupper($_GET["status"]) : "PENDING";

// Mengatur Active Menu
$_SESSION["menu"] = "transaksi-" . strtolower($status_transaksi);
$menu = $_SESSION["menu"];
  $nama=$_SESSION["nama"];
  $id=$_SESSION["id"];
  $username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
  $foto=$_SESSION["foto"];
  $last_login=$_SESSION["last_login"];
 
// ==========================================
// PROSES PENANGANAN AKSI TRANSAKSI (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: transaksi?status=" . $status_transaksi . "&msg=csrf_failed");

        exit;
    }

     // 1. PROSES HAPUS DATA
    if (isset($_POST['hapus_id']) && !empty($_POST['hapus_id'])) {
        $hapus_id = (int)$_POST['hapus_id'];
        try {
            $stmt_hapus = $pdo->prepare("update `transaksis` set status = 0 WHERE id = ?");
            $stmt_hapus->execute([$hapus_id]);

            header("Location: transaksi?status=" . $status_transaksi . "&msg=hapus_sukses");
            exit;
        } catch (PDOException $e) {
            die("Error menghapus data: " . $e->getMessage());
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
    <title>Data Transaksi <?php echo ucfirst(strtolower($status_transaksi)); ?> | LexPina</title>

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

          <?php
          
          if(isset($_GET['msg']) && $_GET['msg']=='hapus_sukses'): ?>
          <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            Data berhasil dihapus.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php elseif(isset($_GET['msg']) && $_GET['msg'] == 'csrf_failed'): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
              <strong>Akses ditolak!</strong> Token keamanan tidak valid.
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php elseif(isset($_GET['msg']) && $_GET['msg'] == 'sukses_update'): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
              Data berhasil diperbarui.
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <div class="card mb-3 mt-3">
            <div class="card-header">
              <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                  <h5 class="fs-0 mb-0">
                    <span class="fa fa-shopping-cart me-2 fs-0 "></span>
                    Data Transaksi <?php echo $status_transaksi; ?></span>
                  </h5>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="transaksiTable" class="display table table-striped table-bordered table-sm" style="width:100%">
                  <thead class="bg-primary text-white">
                    <tr>
                      <th>ID Trx</th>
                      <th>Tanggal</th>
                      <th>Nama Pengguna</th>
                      <th>Paket Produk</th>
                      <th>Ttl Bayar</th>
                    
                      <th class="text-center">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php
                    // Query JOIN untuk mengambil nama user dan nama produk
                    $query = "SELECT t.*, u.nama AS nama_user, p.nama_paket 
                              FROM transaksis t 
                              LEFT JOIN users u ON t.user_id = u.id 
                              LEFT JOIN produks p ON t.produk_id = p.id 
                              WHERE t.status = ? 
                              ORDER BY t.created_at DESC";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$status_transaksi]);
                    
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      $tgl_trx = date('d/m/Y H:i', strtotime($row['created_at']));
                      
                      // Penyesuaian warna badge status
                      $badge_color = 'bg-warning';
                      if($row['status'] == 'LUNAS') $badge_color = 'bg-success';
                      if($row['status'] == 'DITOLAK') $badge_color = 'bg-danger';

                      echo "<tr>
                        <td>#TRX-".$row['id']."</td>
                        <td>".$tgl_trx."</td>
                        <td>".htmlspecialchars($row['nama_user'])."</td>
                        <td>".htmlspecialchars($row['nama_paket'])."</td>   
                        <td>Rp ".number_format($row['total_transfer'], 0, ',', '.')."</td>                   
                       <td>
                          <a href='transaksi-edit?id=".$row['id']."' class='btn btn-sm btn-info'>Edit</a>
                          <button class='btn btn-sm btn-danger btnHapusTransaksi' data-id='".$row['id']."' data-action='hapus' data-desc='menghapus data'>Hapus</button>
                        </td>
                        
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Modal Konfirmasi Hapus Transaksi -->
            <div class="modal fade" id="modalHapusTransaksi" tabindex="-1" aria-labelledby="modalHapusTransaksiLabel" aria-hidden="true">
              <div class="modal-dialog">
                <form method="POST" id="formHapusTransaksi">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                  <input type="hidden" name="hapus_id" id="hapus_id_transaksi">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalHapusTransaksiLabel">Konfirmasi Hapus</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <p>Yakin ingin menghapus data transaksi <b id="hapus_judul"></b>?</p>
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
    <!-- Scripts -->
<script src="../vendors/jquery/jquery-3.7.0.min.js"></script>
    <script src="../vendors/datatables/datatables.min.js"></script>
    <script>
    $(document).ready(function() {
      // Inisialisasi DataTables
      $('#transaksiTable').DataTable({
        "autoWidth": false,
        "order": [[ 1, "desc" ]] // Urutkan berdasarkan kolom tanggal secara default
      });
     
    });
      $(document).on('click', '.btnHapusTransaksi', function() {
      var id = $(this).data('id');
      $('#hapus_id_transaksi').val(id);
      $('#hapus_judul').text('#TRX-' + id);
      $('#modalHapusTransaksi').modal('show');
    });
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
 