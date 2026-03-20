<?php
session_start();
include("../config/configuration.php");
if($_SESSION["nama"]!="" && $_SESSION["id"]!=""){
  $_SESSION["menu"]="konflik";
  $menu=$_SESSION["menu"];
  $nama=$_SESSION["nama"];
  $id=$_SESSION["id"];
  $username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
  $foto=$_SESSION["foto"];
  $last_login=$_SESSION["last_login"];
 // Proses hapus konflik (soft/hard delete, pilih salah satu)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_id'])) {
    $hapus_id = intval($_POST['hapus_id']);
    if($hapus_id){
        // Soft Delete: ubah status = 0
        $pdo->prepare("UPDATE konfliks SET status=0, updated_at=NOW() WHERE id=?")->execute([$hapus_id]);
        // Jika ingin hard delete, gunakan query: DELETE FROM konfliks WHERE id=?
        header("Location: konflik?status=hapus_sukses");
        exit;
    }
}
// proses tambah data konflik
if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['kategori_id'],$_POST['desa_id'],$_POST['polres_id'],$_POST['lokasi'],$_POST['permasalahan'],$_POST['polsek_id'],$_POST['sumber_id'])) {
    $kategori_id = intval($_POST['kategori_id']);
    $desa_id = intval($_POST['desa_id']);
    $polres_id = intval($_POST['polres_id']);
    $lokasi = trim($_POST['lokasi']);
    $permasalahan = trim($_POST['permasalahan']);
    $uraian = trim($_POST['uraian'] ?? '');
    $penanganan = trim($_POST['penanganan'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
     $polsek_id = intval($_POST['polsek_id']);
     $sumber_id = intval($_POST['sumber_id'] ?? 0);
    $user_id = $id; // dari session login

    if($kategori_id && $desa_id && $polres_id && $lokasi && $permasalahan && $polsek_id && $sumber_id){
        $stmt = $pdo->prepare("INSERT INTO konfliks (kategori_id, desa_id, polres_id, polsek_id, sumber_id, lokasi, permasalahan, uraian, penanganan, keterangan, user_id, status, created_at)
            VALUES (:kategori_id, :desa_id, :polres_id, :polsek_id, :sumber_id, :lokasi, :permasalahan, :uraian, :penanganan, :keterangan, :user_id, 1, NOW())");
        $stmt->execute([
            ':kategori_id'=>$kategori_id,
            ':desa_id'=>$desa_id,
            ':polres_id'=>$polres_id,
            ':polsek_id'=>$polsek_id,
            ':sumber_id'=>$sumber_id,
            ':lokasi'=>$lokasi,
            ':permasalahan'=>$permasalahan,
            ':uraian'=>$uraian,
            ':penanganan'=>$penanganan,
            ':keterangan'=>$keterangan,
            ':user_id'=>$user_id
        ]);
        header("Location: konflik?status=sukses");
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
    <title>Data Konflik | Peta Digital</title>

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
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css"/>
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
        <?php include_once("navbar.php") ?>
        <div class="content">
          <?php include_once("header.php") ?>
          <?php if(isset($_GET['status']) && $_GET['status']=='sukses'): ?>
          <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            Data konflik berhasil ditambahkan.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          
          <?php if(isset($_GET['status']) && $_GET['status']=='hapus_sukses'): ?>
          <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            Data konflik berhasil dihapus.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          <div class="card mb-3">
            <div class="card-header">
              <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                  <h5 class="fs-0 mb-0"><span class="fas fa-rocket me-2 fs-0"></span>Data Konflik</h5>
                </div>
                <div class="col-auto ms-auto">
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahKonflik">
                  Tambah Data
                </button>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="konflikTable" class="display table table-striped table-bordered table-sm" style="width:100%">
                  <thead class="bg-primary text-white">
                    <tr>
                      <th>ID</th>
                      <th>Kategori</th>
                      <th>Permasalahan</th>
                      <th>Lokasi</th>
                      <th>Desa</th>
                      <th>Polres</th>
                      <th>Polsek</th>
                      <th>Sumber</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php
                    $query = "SELECT
                      k.id,
                      kk.nama AS kategori_nama,
                      d.nama AS desa_nama,
                      p.nama AS polres_nama,
                      ps.nama AS polsek_nama,
                      sd.nama AS sumber_dokumen_nama,
                      k.lokasi,
                      k.permasalahan,
                      k.created_at
                    FROM konfliks k
                    LEFT JOIN konflik_kategoris kk ON k.kategori_id = kk.id
                    LEFT JOIN desas d ON k.desa_id = d.id
                    LEFT JOIN polress p ON k.polres_id = p.id
                    LEFT JOIN polseks ps ON k.polsek_id = ps.id
                    LEFT JOIN sumbers sd ON k.sumber_id = sd.id
                    WHERE k.status=1
                    ORDER BY k.id DESC";
                    
                    $stmt = $pdo->query($query);
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      echo "<tr>
                      <td>".$row['id']."</td>
                        <td>".$row['kategori_nama']."</td>
                         <td>".(mb_strlen($row['permasalahan']) > 100 ? mb_substr($row['permasalahan'], 0, 100).'...' : $row['permasalahan'])."</td>
                        <td>".$row['lokasi']."</td>
                        <td>".$row['desa_nama']."</td>
                        <td>".$row['polres_nama']."</td>
                         <td>".$row['polsek_nama']."</td>
                        <td>".$row['sumber_dokumen_nama']."</td>
                        <td>
                         
                          <a href='konflik-edit?id=".$row['id']."' class='btn btn-sm btn-info'>Edit</a>
                          <button class='btn btn-sm btn-danger btnHapusKonflik' data-id='".$row['id']."' data-permasalahan='".htmlspecialchars($row['permasalahan'],ENT_QUOTES)."'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <!-- Modal Konfirmasi Hapus Konflik -->
            <div class="modal fade" id="modalHapusKonflik" tabindex="-1" aria-labelledby="modalHapusKonflikLabel" aria-hidden="true">
              <div class="modal-dialog">
                <form method="POST" id="formHapusKonflik">
                  <input type="hidden" name="hapus_id" id="hapus_id_konflik">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalHapusKonflikLabel">Konfirmasi Hapus</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <p>Yakin ingin menghapus data konflik dengan permasalahan: <b id="hapus_permasalahan"></b>?</p>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                      <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
           
          <!-- Modal Tambah Data Konflik -->
          <div class="modal fade" id="modalTambahKonflik" tabindex="-1" aria-labelledby="modalTambahKonflikLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <form method="POST" id="formTambahKonflik">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahKonflikLabel">Tambah Data Konflik</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label class="form-label">Kategori Konflik</label>
                      <select class="form-select form-select-sm" name="kategori_id" required>
                        <option value="">- Pilih Kategori -</option>
                        <?php
                        $kategori = $pdo->query("SELECT id, nama FROM konflik_kategoris WHERE status=1 ORDER BY nama");
                        while($k = $kategori->fetch()) {
                          echo "<option value='{$k['id']}'>".htmlspecialchars($k['nama'])."</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Permasalahan</label>
                      <input type="text" class="form-control form-control-sm" name="permasalahan" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Lokasi</label>
                      <input type="text" class="form-control form-control-sm" name="lokasi" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Kabupaten</label>
                      <select class="form-select form-select-sm" name="kabupaten_id" id="kabupaten_id" required>
                        <option value="">- Pilih Kabupaten -</option>
                        <?php
                        $kabupaten = $pdo->query("SELECT id, nama,kode FROM kabupatens WHERE status=1 ORDER BY nama");
                        while($k = $kabupaten->fetch()) {
                          echo "<option value='{$k['id']}'>".htmlspecialchars($k['kode'])." - ".htmlspecialchars($k['nama'])."</option>";
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
                    <div class="mb-3">
                      <label class="form-label">Polres</label>
                      <select class="form-select form-select-sm" name="polres_id" id="polres_id" required>
                        <option value="">- Pilih Polres -</option>
                        <?php
                          $polres = $pdo->query("SELECT id, nama FROM polress WHERE status=1 ORDER BY nama");
                          while($p = $polres->fetch()) {
                            echo "<option value='{$p['id']}'>".htmlspecialchars($p['nama'])."</option>";
                          }
                        ?>
                      </select>
                    </div>
                    <!-- Polsek (dinamis by Polres) -->
                    <div class="mb-3">
                      <label class="form-label">Polsek</label>
                      <select class="form-select form-select-sm" name="polsek_id" id="polsek_id" disabled>
                        <option value="">- Pilih Polsek -</option>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Uraian</label>
                      <textarea class="form-control form-control-sm" name="uraian" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Penanganan</label>
                      <textarea class="form-control form-control-sm" name="penanganan" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Keterangan</label>
                      <textarea class="form-control form-control-sm" name="keterangan" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Sumber Dokumen</label>
                      <select class="form-select form-select-sm" name="sumber_id" required>
                        <option value="">- Pilih Sumber Dokumen -</option>
                        <?php
                        $sumber = $pdo->query("SELECT id, nama FROM sumbers WHERE status=1 and tipe='KONFLIK' ORDER BY nama");
                        while($s = $sumber->fetch()) {
                          echo "<option value='{$s['id']}'>".htmlspecialchars($s['nama'])."</option>";
                        }
                        ?>
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
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
    $(document).ready(function() {
      $('#konflikTable').DataTable({
        "autoWidth": false,
        "order": [[ 0, "desc" ]],
        "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ]
        
      });
    });
    $('#kabupaten_id').change(function() {
      var kabupatenId = $(this).val();
      console.log(kabupatenId);
      $('#kecamatan_id').prop('disabled', true).html('<option value="">- Pilih Kecamatan -</option>');
      $('#desa_id').prop('disabled', true).html('<option value="">- Pilih Desa -</option>');
      if(kabupatenId) {
        $.get('get_kecamatan.php', {kabupaten_id: kabupatenId}, function(data) {
          var opt = '<option value="">- Pilih Kecamatan -</option>';
          $.each(data, function(i, v) { opt += '<option value="'+v.id+'">'+v.kode +' - '+v.nama+'</option>'; });
          $('#kecamatan_id').html(opt).prop('disabled', false);
        }, 'json');
      }
    });

    $('#kecamatan_id').change(function() {
      var kecamatanId = $(this).val();
      $('#desa_id').prop('disabled', true).html('<option value="">- Pilih Desa -</option>');
      if(kecamatanId) {
        $.get('get_desa.php', {kecamatan_id: kecamatanId}, function(data) {
          var opt = '<option value="">- Pilih Desa -</option>';
          $.each(data, function(i, v) { opt += '<option value="'+v.id+'">'+v.kode +' - '+v.nama+'</option>'; });
          $('#desa_id').html(opt).prop('disabled', false);
        }, 'json');
      }
    });
    $('#polres_id').change(function() {
          var polresId = $(this).val();
          console.log(polresId);
          $('#polsek_id').prop('disabled', true).html('<option value="">- Pilih Polsek -</option>');
          if(polresId) {
            $.get('get_polsek.php', {polres_id: polresId}, function(data) {
              var opt = '<option value="">- Pilih Polsek -</option>';
              $.each(data, function(i, v) { opt += '<option value="'+v.id+'">'+v.nama+'</option>'; });
              $('#polsek_id').html(opt).prop('disabled', false);
            }, 'json');
          }
        });
    // Hapus Konflik
    $(document).on('click', '.btnHapusKonflik', function() {
      var id = $(this).data('id');
      var permasalahan = $(this).data('permasalahan');
      $('#hapus_id_konflik').val(id);
      $('#hapus_permasalahan').text(permasalahan);
      $('#modalHapusKonflik').modal('show');
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
}else{
  header("Location: ../index");
}
?>