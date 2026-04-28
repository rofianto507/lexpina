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
  $_SESSION["menu"]="saran";
 
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
    // Validasi Token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: saran?status=csrf_failed");
        exit;
    }

    // 1. PROSES HAPUS DATA
    if (isset($_POST['hapus_id']) && !empty($_POST['hapus_id'])) {
        $hapus_id = (int)$_POST['hapus_id'];
        try {
            $stmt_hapus = $pdo->prepare("delete from `saran` WHERE id = ?");
            $stmt_hapus->execute([$hapus_id]);

            header("Location: saran?status=hapus_sukses");
            exit;
        } catch (PDOException $e) {
            die("Error menghapus data: " . $e->getMessage());
        }
    }
    if(isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        $edit_id = (int)$_POST['edit_id'];
        $pengguna_id = (int)$_POST['pengguna_id'];
        try {
            $stmt_update = $pdo->prepare("UPDATE `sarans` SET status = 1 WHERE id = ?");
            $stmt_update->execute([$edit_id]);
            $notif_judul = "Saran & Masukan Ditinjau";            
            $notif_konten = "Saran & Masukan dengan judul <b>".htmlspecialchars($_POST['edit_judul'])."</b> telah ditinjau oleh admin. Terima kasih atas kontribusinya!";
            $stmt_notif = $pdo->prepare("INSERT INTO notifikasis (user_id, tipe, judul, konten, status) VALUES (?, 'pemberitahuan', ?, ?, 0)");
            $stmt_notif->execute([$pengguna_id, $notif_judul, $notif_konten]);
            header("Location: saran?status=update_sukses");
            exit;
        } catch (PDOException $e) {
            die("Error memperbarui data: " . $e->getMessage());
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
    <title>Saran & Masukan | LexPina</title>

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
          
          
          <?php if(isset($_GET['status']) && $_GET['status']=='hapus_sukses'): ?>
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
                  <h5 class="fs-0 mb-0"><span class="fa fa-envelope me-2 fs-0"></span>Data Saran & Masukan</h5>
                </div>
                 
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="saranTable" class="display table table-striped table-bordered table-sm" >
                  <thead class="bg-primary text-white">
                    <tr>
                      <th>ID</th>
                      <th>Tanggal</th>
                      <th>Judul</th>
                      <th>Pengguna</th>    
                      <th>Status</th>                
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
                    </tr>
                  </tfoot>
                  <tbody>
                  <?php

                    $query = "SELECT sarans.*, users.nama as pengguna_nama FROM `sarans` JOIN `users` ON sarans.user_id = users.id ORDER BY sarans.id DESC";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute();  
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      echo "<tr>
                        <td>".$row['id']."</td>
                        <td>".htmlspecialchars(date('d/m/Y H:i', strtotime($row['created_at'])))."</td>
                        <td>".htmlspecialchars($row['judul'])."</td>
                        <td>".htmlspecialchars($row['pengguna_nama'])."</td>                      
                        <td>".htmlspecialchars($row['status']==1 ? 'Ditinjau' : 'Belum Ditinjau')."</td>
                        <td>
                          <button class='btn btn-sm btn-info btnEditSaran' 
                          data-id='{$row['id']}' 
                          data-judul='".htmlspecialchars($row['judul'],ENT_QUOTES)."' 
                          data-pengguna-nama='{$row['pengguna_nama']}'
                          data-pengguna-id='{$row['user_id']}'
                          data-status='{$row['status']}'
                          data-konten='".htmlspecialchars($row['konten'],ENT_QUOTES)."'
                          >Detail</button>
                          <button class='btn btn-sm btn-danger btnHapusSaran' data-id='".$row['id']."' data-judul='".htmlspecialchars($row['judul'], ENT_QUOTES)."'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <!-- Modal Konfirmasi Hapus Saran -->
            <div class="modal fade" id="modalHapusSaran" tabindex="-1" aria-labelledby="modalHapusSaranLabel" aria-hidden="true">
              <div class="modal-dialog">
                <form method="POST" id="formHapusSaran">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                  <input type="hidden" name="hapus_id" id="hapus_id_saran">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalHapusSaranLabel">Konfirmasi Hapus</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <p>Yakin ingin menghapus data saran dengan judul: <b id="hapus_judul"></b>?</p>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                      <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
              <!-- Modal Edit Saran -->
              <div class="modal fade" id="modalEditSaran" tabindex="-1" aria-labelledby="modalEditSaranLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                  <form method="POST" id="formEditSaran">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="edit_id" id="edit_id">
                    <input type="hidden" name="pengguna_id" id="pengguna_id_edit">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalEditSaranLabel">Detail Saran & Masukan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-3">
                          <label for="edit_judul" class="form-label">Judul</label>
                          <input type="text" class="form-control" id="edit_judul" name="edit_judul" readonly required>
                        </div>
                          <div class="mb-3">
                            <label for="edit_konten" class="form-label">Konten</label>
                            <textarea class="form-control" id="edit_konten" name="edit_konten" rows="5" required readonly></textarea>
                        </div>
                        <div class="mb-3">
                          <label for="edit_status" class="form-label">Status</label>
                          <input type="text" class="form-control" id="edit_status" name="edit_status" readonly required>
                        </div>
                        <div class="mb-3">
                          <label for="pengguna_nama_edit" class="form-label">Pengguna</label>
                          <input type="text" class="form-control" id="pengguna_nama_edit" name="pengguna_nama_edit" readonly required>
                        </div>
                      </div>
                      <div class="modal-footer"> 
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-success d-none" id="btn_sudah_ditinjau" disabled>Sudah Ditinjau</button>
                        <button type="submit" class="btn btn-primary" id="btn_tandai_ditinjau">Tandai Sudah Ditinjau</button>
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
      $('#saranTable').DataTable({
        "autoWidth": false,
        "order": [[ 0, "desc" ]], 
         initComplete: function () {
            this.api().columns([3, 4]).every( function () {
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
      $(document).on('click', '.btnHapusSaran', function() {
      var id = $(this).data('id');
      var judul = $(this).data('judul');
      $('#hapus_id_saran').val(id);
      $('#hapus_judul').text(judul);
      $('#modalHapusSaran').modal('show');
    });
     $(document).on('click', '.btnEditSaran', function() {
        var id = $(this).data('id');
        var judul = $(this).data('judul');
        var pengguna_nama = $(this).data('pengguna-nama');
        var pengguna_id = $(this).data('pengguna-id');
        var konten = $(this).data('konten');
         var status = $(this).data('status');
         if(status == 1) {
            $('#edit_status').val('Ditinjau');
            $('#btn_sudah_ditinjau').removeClass('d-none');
            $('#btn_tandai_ditinjau').addClass('d-none');
          } else {
            $('#edit_status').val('Belum Ditinjau');
            $('#btn_sudah_ditinjau').addClass('d-none');
            $('#btn_tandai_ditinjau').removeClass('d-none');
          }
         // Set nilai ke form edit
        $('#edit_id').val(id);
        $('#edit_judul').val(judul);
        $('#pengguna_nama_edit').val(pengguna_nama);
        $('#pengguna_id_edit').val(pengguna_id);
        $('#edit_konten').val(konten);
        $('#modalEditSaran').modal('show');
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
 