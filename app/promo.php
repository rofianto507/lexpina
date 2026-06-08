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
$_SESSION["menu"] = "promo";

$menu       = $_SESSION["menu"];
$nama       = $_SESSION["nama"];
$id         = $_SESSION["id"];
$username   = $_SESSION["username"];
$akses      = $_SESSION["akses"];
$foto       = $_SESSION["foto"];
$last_login = $_SESSION["last_login"];

// ==========================================
// PROSES PENANGANAN FORM (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: promo?msg=csrf_failed");
        exit;
    }

    $action = $_POST['action'] ?? '';

    try {
        // --- 1. PROSES TAMBAH ---
        if ($action == 'tambah') {
            $nama_promo  = trim($_POST['nama']);
            $nominal     = !empty($_POST['nominal'])    ? (float)$_POST['nominal']    : null;
            $persentase  = !empty($_POST['persentase']) ? (float)$_POST['persentase'] : null;
            $kode        = strtoupper(trim($_POST['kode']));
            $keterangan  = trim($_POST['keterangan']);
            $expired_at  = !empty($_POST['expired_at']) ? $_POST['expired_at'] : null;
            $status      = isset($_POST['status']) ? (int)$_POST['status'] : 1;

            // Cek kode unik
            $cek = $pdo->prepare("SELECT id FROM promos WHERE kode = ?");
            $cek->execute([$kode]);
            if ($cek->fetch()) {
                header("Location: promo?msg=kode_duplikat");
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO promos (kode, nama, nominal, persentase, keterangan, expired_at, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$kode, $nama_promo, $nominal, $persentase, $keterangan, $expired_at, $status]);

            header("Location: promo?msg=sukses_tambah");
            exit;
        }

        // --- 2. PROSES EDIT ---
        if ($action == 'edit' && !empty($_POST['edit_id'])) {
            $edit_id     = (int)$_POST['edit_id'];
            $kode        = strtoupper(trim($_POST['kode']));
            $nama_promo  = trim($_POST['nama']);
            $nominal     = !empty($_POST['nominal'])    ? (float)$_POST['nominal']    : null;
            $persentase  = !empty($_POST['persentase']) ? (float)$_POST['persentase'] : null;
            $keterangan  = trim($_POST['keterangan']);
            $expired_at  = !empty($_POST['expired_at']) ? $_POST['expired_at'] : null;
            $status      = isset($_POST['status']) ? (int)$_POST['status'] : 1;

            $stmt = $pdo->prepare("UPDATE promos SET kode=?, nama=?, nominal=?, persentase=?, keterangan=?, expired_at=?, status=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$kode, $nama_promo, $nominal, $persentase, $keterangan, $expired_at, $status, $edit_id]);

            header("Location: promo?msg=sukses_edit");
            exit;
        }

        // --- 3. PROSES HAPUS ---
        if ($action == 'hapus' && !empty($_POST['hapus_id'])) {
            $hapus_id = (int)$_POST['hapus_id'];

            $stmt = $pdo->prepare("DELETE FROM promos WHERE id = ?");
            $stmt->execute([$hapus_id]);

            header("Location: promo?msg=sukses_hapus");
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
    <title>Master Promo | LexPina</title>
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
      document.getElementById('style-default').setAttribute('disabled', true);
      document.getElementById('user-style-default').setAttribute('disabled', true);
      document.querySelector('html').setAttribute('dir', 'rtl');
    } else {
      document.getElementById('style-rtl').setAttribute('disabled', true);
      document.getElementById('user-style-rtl').setAttribute('disabled', true);
    }
    </script>
  </head>
  <body>
    <main class="main" id="top">
      <div class="container-fluid" data-layout="container">
        <?php include_once("navbar.php") ?>
        <div class="content">
          <?php include_once("header.php") ?>

          <?php if(isset($_GET['msg'])): ?>
            <?php if($_GET['msg'] == 'sukses_tambah'): ?>
              <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">Data promo berhasil ditambahkan.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'sukses_edit'): ?>
              <div class="alert alert-info alert-dismissible fade show mt-3" role="alert">Data promo berhasil diperbarui.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'sukses_hapus'): ?>
              <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">Data promo berhasil dihapus.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'csrf_failed'): ?>
              <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert"><strong>Akses ditolak!</strong> Token keamanan tidak valid.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'kode_duplikat'): ?>
              <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert"><strong>Kode sudah digunakan!</strong> Gunakan kode promo yang berbeda.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
          <?php endif; ?>

          <div class="card mb-3 mt-3">
            <div class="card-header">
              <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                  <h5 class="fs-0 mb-0"><span class="fa fa-gift me-2 fs-0 text-primary"></span>Master Data: Promo</h5>
                </div>
                <div class="col-auto ms-auto">
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    Tambah Promo
                  </button>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="promoTable" class="display table table-striped table-bordered table-sm" style="width:100%">
                  <thead class="bg-primary text-white">
                    <tr>
                      <th width="5%">No</th>
                      <th>Kode</th>
                      <th>Nama Promo</th>
                      <th>Nominal (Rp)</th>
                      <th>Persentase (%)</th>
                      <th>Keterangan</th>
                      <th>Expired</th>
                      <th class="text-center">Status</th>
                      <th width="15%" class="text-center">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php
                    // Ambil data promos sekaligus jumlah penggunaan kode dari tabel transaksis
                    $stmt = $pdo->query("SELECT promos.*, (SELECT COUNT(*) FROM transaksis t WHERE t.kode_promo = promos.kode) AS usage_count FROM promos ORDER BY id DESC");
                    $no = 1;
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $expired = $row['expired_at'] ? date('d/m/Y H:i', strtotime($row['expired_at'])) : '-';
                        $nominal    = $row['nominal']    !== null ? 'Rp ' . number_format($row['nominal'], 0, ',', '.') : '-';
                        $persentase = $row['persentase'] !== null ? $row['persentase'] . '%' : '-';

                        // Badge expired
                        $now = new DateTime();
                        $exp_dt = $row['expired_at'] ? new DateTime($row['expired_at']) : null;
                        $badge = '';
                        if ($exp_dt && $now > $exp_dt) {
                            $badge = '<span class="badge bg-danger ms-1">Expired</span>';
                        } elseif ($exp_dt) {
                            $badge = '<span class="badge bg-success ms-1"></span>';
                        }

                        $status_badge = $row['status'] == 1
                            ? '<span class="badge bg-success">Aktif</span>'
                            : '<span class="badge bg-secondary">Tidak Aktif</span>';

                        // badge kecil yang menampilkan jumlah pengguna promo
                        $usage_badge = '<span class="badge bg-info ms-1">'.(int)
                            ($row['usage_count'] ?? 0).'</span>';

                        echo "<tr>
                            <td class='text-center'>".$no++."</td>
                            <td><span class='badge badge-soft-primary text-uppercase fw-bold' style='font-size:13px;letter-spacing:1px;'>".htmlspecialchars($row['kode'])."</span></td>
                            <td class='fw-bold'>".htmlspecialchars($row['nama'])."</td>
                            <td>".$nominal."</td>
                            <td>".$persentase."</td>
                            <td>".htmlspecialchars($row['keterangan'])."</td>
                            <td>".$expired.$badge."</td>
                            <td class='text-center'>".$status_badge."</td>
                            <td class='text-center'>
                                <button class='btn btn-sm btn-info btnEdit'
                                    data-id='".$row['id']."'
                                    data-nama='".htmlspecialchars($row['nama'], ENT_QUOTES)."'
                                    data-nominal='".($row['nominal'] ?? '')."'
                                    data-persentase='".($row['persentase'] ?? '')."'
                                    data-keterangan='".htmlspecialchars($row['keterangan'], ENT_QUOTES)."'
                                    data-expired='".($row['expired_at'] ? date('Y-m-d\\TH:i', strtotime($row['expired_at'])) : '')."'
                                    data-status='".$row['status']."'
                                    data-kode='".htmlspecialchars($row['kode'], ENT_QUOTES)."'
                                >Edit</button>
                                <button class='btn btn-sm btn-danger btnHapus'
                                    data-id='".$row['id']."'
                                    data-nama='".htmlspecialchars($row['nama'], ENT_QUOTES)."'
                                >Hapus</button>
                                <button class='btn btn-sm btn-secondary btnUsers' 
                                    data-kode='".htmlspecialchars($row['kode'], ENT_QUOTES)."' 
                                    data-nama='".htmlspecialchars($row['nama'], ENT_QUOTES)."'
                                >Pengguna $usage_badge</button>
                            </td>
                        </tr>";
                     }
                   ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Modal Tambah -->
          <div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Tambah Promo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-2">
                      <label class="form-label">Kode Promo <span class="text-danger">*</span></label>
                      <input type="text" class="form-control form-control-sm text-uppercase" name="kode" required placeholder="Contoh: LEBARAN25" style="letter-spacing:1px;">
                      <small class="text-muted">Kode akan otomatis diubah ke HURUF KAPITAL.</small>
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Nama Promo <span class="text-danger">*</span></label>
                      <input type="text" class="form-control form-control-sm" name="nama" required placeholder="Contoh: Promo Lebaran 2025">
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Nominal Diskon (Rp)</label>
                      <input type="number" class="form-control form-control-sm" name="nominal" placeholder="Contoh: 50000" min="0">
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Persentase Diskon (%)</label>
                      <input type="number" class="form-control form-control-sm" name="persentase" placeholder="Contoh: 10" min="0" max="100" step="0.01">
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Keterangan</label>
                      <input type="text" class="form-control form-control-sm" name="keterangan" placeholder="Keterangan singkat promo">
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Expired At</label>
                      <input type="datetime-local" class="form-control form-control-sm" name="expired_at">
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Status</label>
                      <select class="form-select form-select-sm" name="status">
                        <option value="1">Aktif</option>
                        <option value="0">Tidak Aktif</option>
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

          <!-- Modal Edit -->
          <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="edit_id" id="form_edit_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Edit Promo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-2">
                      <label class="form-label">Kode Promo <span class="text-danger">*</span></label>
                      <input type="text" class="form-control form-control-sm text-uppercase" name="kode" id="form_edit_kode" required style="letter-spacing:1px;">
                      <small class="text-muted">Kode akan otomatis diubah ke HURUF KAPITAL.</small>
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Nama Promo <span class="text-danger">*</span></label>
                      <input type="text" class="form-control form-control-sm" name="nama" id="form_edit_nama" required>
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Nominal Diskon (Rp)</label>
                      <input type="number" class="form-control form-control-sm" name="nominal" id="form_edit_nominal" min="0">
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Persentase Diskon (%)</label>
                      <input type="number" class="form-control form-control-sm" name="persentase" id="form_edit_persentase" min="0" max="100" step="0.01">
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Keterangan</label>
                      <input type="text" class="form-control form-control-sm" name="keterangan" id="form_edit_keterangan">
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Expired At</label>
                      <input type="datetime-local" class="form-control form-control-sm" name="expired_at" id="form_edit_expired">
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Status</label>
                      <select class="form-select form-select-sm" name="status" id="form_edit_status">
                        <option value="1">Aktif</option>
                        <option value="0">Tidak Aktif</option>
                      </select>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info text-white">Update</button>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <!-- Modal Hapus -->
          <div class="modal fade" id="modalHapus" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="hapus">
                <input type="hidden" name="hapus_id" id="form_hapus_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h6 class="modal-title">Konfirmasi Hapus</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body text-center">
                    <p class="mb-0">Yakin ingin menghapus promo <br><strong id="label_hapus_nama" class="text-danger"></strong>?</p>
                  </div>
                  <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger">Ya, Hapus</button>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <!-- Modal Pengguna Promo -->
          <div class="modal fade" id="modalPenggunaPromo" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <div class="modal-header">
                  <h6 class="modal-title">Pengguna Promo: <span id="modalPenggunaKode"></span></h6>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <div id="penggunaPromoList">
                    <table class="table table-sm table-striped">
                      <thead>
                        <tr>
                          <th>No</th>
                          <th>Nama</th>
                          <th>Username</th>
                          <th>Total Transfer</th>
                          <th>Diskon</th>
                          <th>Status</th>
                          <th>Tanggal</th>
                        </tr>
                      </thead>
                      <tbody id="penggunaPromoTbody"></tbody>
                    </table>
                    <div id="penggunaPromoEmpty" class="text-center text-muted" style="display:none;">Belum ada pengguna yang memakai kode ini.</div>
                  </div>
                </div>
                <div class="modal-footer">
                   <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
              </div>
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
      $('#promoTable').DataTable({
        "autoWidth": false,
        "order": [[ 0, "asc" ]]
      });

      // Trigger Edit Modal
      $(document).on('click', '.btnEdit', function() {
        $('#form_edit_id').val($(this).data('id'));
        $('#form_edit_kode').val($(this).data('kode'));
        $('#form_edit_nama').val($(this).data('nama'));
        $('#form_edit_nominal').val($(this).data('nominal'));
        $('#form_edit_persentase').val($(this).data('persentase'));
        $('#form_edit_keterangan').val($(this).data('keterangan'));
        $('#form_edit_expired').val($(this).data('expired'));
        $('#form_edit_status').val($(this).data('status'));
        $('#modalEdit').modal('show');
      });

      // Trigger Hapus Modal
      $(document).on('click', '.btnHapus', function() {
        $('#form_hapus_id').val($(this).data('id'));
        $('#label_hapus_nama').text($(this).data('nama'));
        $('#modalHapus').modal('show');
      });

      // Trigger Pengguna Modal (AJAX)
      $(document).on('click', '.btnUsers', function() {
        var kode = $(this).data('kode');
        $('#modalPenggunaKode').text(kode);
        $('#penggunaPromoTbody').html('');
        $('#penggunaPromoEmpty').hide();
        $('#modalPenggunaPromo').modal('show');

        $.getJSON('get_promo_users.php', { kode: kode }, function(data) {
          if (data && data.length) {
            var html = '';
            $.each(data, function(i, row) {
              var total = row.total_transfer ? 'Rp ' + Number(row.total_transfer).toLocaleString('id-ID') : '-';
              var diskon = row.diskon_nominal ? 'Rp ' + Number(row.diskon_nominal).toLocaleString('id-ID') : '-';
              html += '<tr>'+
                        '<td>'+(i+1)+'</td>'+ 
                        '<td>'+(row.nama ? row.nama : '-')+'</td>'+ 
                        '<td>'+(row.username ? row.username : '-')+'</td>'+ 
                        '<td>'+total+'</td>'+ 
                        '<td>'+diskon+'</td>'+ 
                        '<td>'+(row.status ? row.status : '-')+'</td>'+ 
                        '<td>'+(row.created_at ? row.created_at : '-')+'</td>'+ 
                      '</tr>';
            });
            $('#penggunaPromoTbody').html(html);
          } else {
            $('#penggunaPromoEmpty').show();
          }
        }).fail(function(){
          $('#penggunaPromoEmpty').show();
        });
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
