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
  $_SESSION["menu"]="member";
 
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
// ==========================================
// PROSES PENANGANAN FORM (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: member?msg=csrf_failed");
        exit;
    }

    $action = $_POST['action'] ?? '';

    try {
        // --- 1. PROSES EDIT ---
        if ($action == 'edit' && !empty($_POST['edit_id'])) {
            $edit_id = (int)$_POST['edit_id'];
            $edit_nama = $_POST['nama'];
            $edit_username = $_POST['username'];
            $edit_status = (int)$_POST['status']; 
            
            // Format datetime-local HTML ke format MySQL DateTime
            $edit_batas = !empty($_POST['batas_langganan']) ? date('Y-m-d H:i:s', strtotime($_POST['batas_langganan'])) : NULL;

            $stmt = $pdo->prepare("UPDATE users SET nama = ?, username = ?, status = ?, batas_langganan = ? WHERE id = ? AND akses = 'MEMBER'");
            $stmt->execute([$edit_nama, $edit_username, $edit_status, $edit_batas, $edit_id]);
            
            header("Location: member?msg=sukses_edit");
            exit;
        }

        // --- 2. PROSES HAPUS (HARD DELETE) ---
        if ($action == 'hapus' && !empty($_POST['hapus_id'])) {
            $hapus_id = (int)$_POST['hapus_id'];
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND akses = 'MEMBER'");
            $stmt->execute([$hapus_id]);
            
            header("Location: member?msg=sukses_hapus");
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
    <title>Member | LexPina</title>

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
                if($_GET['msg'] == 'sukses_edit') echo "Data member berhasil diperbarui.";
                elseif($_GET['msg'] == 'sukses_hapus') echo "Akun member berhasil dihapus permanen.";
                elseif($_GET['msg'] == 'csrf_failed') echo "<strong>Akses ditolak!</strong> Token keamanan tidak valid.";
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <div class="card mb-3 mt-3">
            <div class="card-header border-bottom">
              <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                  <h5 class="fs-0 mb-0"><span class="fa fa-star me-2 fs-0 text-warning"></span>Data Akun: Member Premium</h5>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="memberTable" class="display table table-striped table-bordered table-sm" style="width:100%">
                  <thead class="bg-primary text-white">
                    <tr>
                      <th width="5%" class="text-center">No</th>
                      <th width="8%" class="text-center">Avatar</th>
                      <th>Nama Lengkap</th>
                      <th>Username / Email</th>
                      <th>Masa Aktif Langganan</th>
                      <th>Status Akun</th>
                      <th class="text-center">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php
                    // Filter khusus untuk akses = MEMBER
                    $stmt = $pdo->query("SELECT * FROM users WHERE akses = 'MEMBER' ORDER BY batas_langganan DESC");
                    $no = 1;
                    $sekarang = date('Y-m-d H:i:s');
                    
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      $avatar = !empty($row['google_id']) ? $row['foto'] : '../public/img/user/' . ($row['foto'] ?? 'avatar.png');

                      // Cek status aktif/blokir
                      $status_akun = ($row['status'] == 1) ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Diblokir</span>';

                      // Logika Pengecekan Masa Aktif Langganan
                      $tgl_batas_format = !empty($row['batas_langganan']) ? date('d/m/Y H:i', strtotime($row['batas_langganan'])) : '-';
                      $input_batas_html = !empty($row['batas_langganan']) ? date('Y-m-d\TH:i', strtotime($row['batas_langganan'])) : '';

                      if(empty($row['batas_langganan'])) {
                          $status_langganan = '<span class="badge bg-secondary">Tidak Ada Data</span>';
                      } elseif ($row['batas_langganan'] >= $sekarang) {
                          $status_langganan = '<span class="badge bg-soft-success text-success"><i class="fa fa-check-circle me-1"></i>Aktif s/d '.$tgl_batas_format.'</span>';
                      } else {
                          $status_langganan = '<span class="badge bg-soft-danger text-danger"><i class="fa fa-times-circle me-1"></i>Kedaluwarsa ('.$tgl_batas_format.')</span>';
                      }

                      echo "<tr>
                        <td class='text-center align-middle'>".$no++."</td>
                        <td class='text-center align-middle'>
                            <img src='".htmlspecialchars($avatar)."' referrerpolicy='no-referrer' class='rounded-circle border border-warning' width='40' height='40' style='object-fit:cover;' alt='Avatar'>
                        </td>
                        <td class='align-middle fw-semi-bold'>".htmlspecialchars($row['nama'])."</td>
                        <td class='align-middle'>".htmlspecialchars($row['username'])."</td>
                        <td class='align-middle'>".$status_langganan."</td>
                        <td class='align-middle'>".$status_akun."</td>
                        <td class='text-center align-middle'>
                          <button class='btn btn-sm btn-info text-white btnEdit' 
                            data-id='".$row['id']."' 
                            data-nama='".htmlspecialchars($row['nama'], ENT_QUOTES)."' 
                            data-username='".htmlspecialchars($row['username'], ENT_QUOTES)."' 
                            data-batas='".$input_batas_html."'
                            data-status='".$row['status']."'>Edit</button>
                          
                          <button class='btn btn-sm btn-danger btnHapus' 
                            data-id='".$row['id']."' 
                            data-nama='".htmlspecialchars($row['nama'], ENT_QUOTES)."'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="edit_id" id="form_edit_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Edit Profil Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama" id="form_edit_nama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username / Email</label>
                        <input type="text" class="form-control bg-light" name="username" id="form_edit_username" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-primary fw-bold"><i class="fa fa-calendar me-1"></i> Batas Langganan</label>
                        <input type="datetime-local" class="form-control" name="batas_langganan" id="form_edit_batas">
                        <small class="text-muted">Ubah ini jika Anda ingin memberikan bonus masa aktif secara manual.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status Akun Login</label>
                        <select class="form-select" name="status" id="form_edit_status">
                            <option value="1">Aktif (Bisa Login)</option>
                            <option value="0">Blokir / Suspend</option>
                        </select>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info text-white">Simpan Perubahan</button>
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
                <input type="hidden" name="hapus_id" id="form_hapus_id">
                <div class="modal-content">
                  <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title">Hapus Member?</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body text-center">
                    <p class="mb-0">Yakin ingin menghapus member <br><strong id="label_hapus_nama" class="text-danger"></strong>?</p>
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
      // Init DataTables (Urutkan dari langganan paling baru habis)
      $('#memberTable').DataTable({
        "autoWidth": false,
        "order": [[ 4, "desc" ]] 
      });

      // Trigger Edit Modal
      $(document).on('click', '.btnEdit', function() {
        $('#form_edit_id').val($(this).data('id'));
        $('#form_edit_nama').val($(this).data('nama'));
        $('#form_edit_username').val($(this).data('username'));
        $('#form_edit_batas').val($(this).data('batas'));
        $('#form_edit_status').val($(this).data('status'));
        $('#modalEdit').modal('show');
      });

      // Trigger Hapus Modal
      $(document).on('click', '.btnHapus', function() {
        $('#form_hapus_id').val($(this).data('id'));
        $('#label_hapus_nama').text($(this).data('nama'));
        $('#modalHapus').modal('show');
      });
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
 