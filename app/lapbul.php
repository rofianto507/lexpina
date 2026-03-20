<?php
session_start();
include("../config/configuration.php");
if($_SESSION["nama"]!="" && $_SESSION["id"]!=""){
  $_SESSION["menu"]="lapbul";
  $menu=$_SESSION["menu"];
  $nama=$_SESSION["nama"];
  $id=$_SESSION["id"];
  $username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
  $foto=$_SESSION["foto"];
  $polres_id=$_SESSION["polres_id"] ?? null;
  $polsek_id=$_SESSION["polsek_id"] ?? null;
  $last_login=$_SESSION["last_login"];
  if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['namadesa'])) {
      $kodedesa = trim($_POST['kodedesa']);
      $jenis = trim($_POST['jenis']);
      $kecamatan_id = trim($_POST['kecamatan_id']);
      $namadesa = trim($_POST['namadesa']);
      if($namadesa != "") {
          $stmtInsert = $pdo->prepare("INSERT INTO desas (kode, jenis, kecamatan_id, nama, status) VALUES (:kode, :jenis, :kecamatan_id, :nama, 1)");
          $stmtInsert->bindParam(':kode', $kodedesa);
          $stmtInsert->bindParam(':jenis', $jenis);
          $stmtInsert->bindParam(':kecamatan_id', $kecamatan_id);
          $stmtInsert->bindParam(':nama', $namadesa);
          $stmtInsert->execute();
          // Redirect agar refresh page dan menghilangkan POST (juga agar tabel otomatis update)
          header("Location: desa?status=sukses");
          exit;
      }
  }
  // Handle proses Update
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_id'], $_POST['edit_nama'])) {
    $edit_id = intval($_POST['edit_id']);
    $edit_jenis = trim($_POST['edit_jenis']);
    $edit_kecamatan_id = intval($_POST['edit_kecamatan_id']);
    $edit_nama = trim($_POST['edit_nama']);
    if($edit_id && $edit_nama != "") {
      $stmtUpdate = $pdo->prepare("UPDATE desas SET nama=:nama, kecamatan_id=:kecamatan_id, jenis=:jenis, updated_at=NOW() WHERE id=:id");
      $stmtUpdate->execute([':nama'=>$edit_nama, ':kecamatan_id'=>$edit_kecamatan_id, ':jenis'=>$edit_jenis, ':id'=>$edit_id]);
      header("Location: desa?status=edit_sukses");
      exit;
    }
  }
  // Handle proses Hapus
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['hapus_id'])) {
    if($akses != "POLDA") {
      header("Location: desa?status=hapus_gagal");
      exit;
    }
    $hapus_id = intval($_POST['hapus_id']);
    if($hapus_id) {
      $stmtDel = $pdo->prepare("update desas set status=0 WHERE id=:id");
      $stmtDel->execute([':id'=>$hapus_id]);
      header("Location: desa?status=hapus_sukses");
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
    <title>Import Lapbul | Peta Digital</title>
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/img/favicons/favicon-16x16.png">
    <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicons/favicon.ico">
    <link rel="manifest" href="../assets/img/favicons/manifest.json">
    <meta name="msapplication-TileImage" content="../assets/img/favicons/mstile-150x150.png">
    <meta name="theme-color" content="#ffffff">
    <script src="../assets/js/config.js"></script>
    <script src="../vendors/overlayscrollbars/OverlayScrollbars.min.js"></script>

    <!-- DataTables CSS & jQuery -->
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
                  Data berhasil ditambahkan.
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                <?php if(isset($_GET['status']) && $_GET['status']=='edit_sukses'): ?>
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                  Data berhasil diubah.
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php elseif(isset($_GET['status']) && $_GET['status']=='hapus_sukses'): ?>
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                  Data berhasil dihapus.
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php elseif(isset($_GET['status']) && $_GET['status']=='hapus_gagal'): ?>
                <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
                  Hapus Gagal. Anda tidak memiliki akses untuk menghapus data.
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
          <div class="card mb-3">
            <div class="card-header">
              <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                  <h5 class="fs-0 mb-0"><span class="fas fa-calendar me-2 fs-0"></span> Laporan Bulanan</h5>
                </div>
                <div class="col-auto ms-auto">
                  <?php if($akses == "POLDA"): ?>
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahLapbul">
                    Tambah Data Import
                  </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="lapbulTable" class="display table table-striped table-bordered table-sm" style="width:100%">
                  <thead class="bg-primary text-white">
                    <tr>
                      <th>ID</th>
                      <th>Tahun</th>
                      <th>Bulan</th>
                      <th>Polres</th>
                      <th>Polsek</th>
                      <th>Sumber</th>
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
                    $no=1;
                    $stmt = $pdo->query("SELECT lapbuls.*, polress.nama AS polres, polseks.nama AS polsek FROM lapbuls 
                    join polress on lapbuls.polres_id = polress.id 
                    join polseks on lapbuls.polsek_id = polseks.id WHERE lapbuls.status=1 ORDER BY lapbuls.id ASC");

                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      echo "<tr>
                     
                        <td>".$row['id']."</td>
                        <td>".$row['tahun']."</td>
                        <td>".$row['bulan']."</td>
                        <td>".$row['polres']."</td>
                        <td>".$row['polsek']."</td>
                        <td>".$row['file_import']."</td>
                        <td>
                          <a href='lapbul-edit?id=".$row['id']."' class='btn btn-sm btn-info'>Edit</a>
                          <button class='btn btn-sm btn-danger btnHapuslapbul' data-id='{$row['id']}' data-nama='".htmlspecialchars($row['nama'],ENT_QUOTES)."'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <!-- Modal Tambah Data lapbul -->
          <div class="modal fade" id="modalTambahLapbul" tabindex="-1" aria-labelledby="modalTambahLapbulLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formTambahLapbul">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahLapbulLabel">Tambah lapbul</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label for="jenis" class="form-label">Jenis</label>
                      <select class="form-select" id="jenis" name="jenis" required>
                        <option value="lapbul">DESA</option>
                        <option value="KELURAHAN">KELURAHAN</option>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label for="namadesa" class="form-label">Kecamatan</label>
                      <select class="form-select" id="kecamatan_id" name="kecamatan_id" required>
                        <option value="">Pilih Kecamatan</option>
                        <?php
                          $stmtKecamatan = $pdo->query("
                          SELECT kecamatans.*,kabupatens.nama AS kabupaten_nama FROM kecamatans 
                          JOIN kabupatens ON kecamatans.kabupaten_id = kabupatens.id 
                          WHERE kecamatans.status=1 ORDER BY kecamatans.id ASC");
                          while($kecamatan = $stmtKecamatan->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='".$kecamatan['id']."'>".$kecamatan['kabupaten_nama']." - ".$kecamatan['nama']."</option>";
                          }
                        ?>
                      </select>
                    </div>
                     <div class="mb-3">
                      <label for="kodedesa" class="form-label">Kode desa</label>
                      <input type="text" class="form-control" id="kodedesa" name="kodedesa" required>
                    </div>
                    <div class="mb-3">
                      <label for="namadesa" class="form-label">Nama desa</label>
                      <input type="text" class="form-control" id="namadesa" name="namadesa" required>
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
          

          <!-- Modal Konfirmasi Hapus -->
          <div class="modal fade" id="modalHapuslapbul" tabindex="-1" aria-labelledby="modalHapuslapbulLabel" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" id="formHapuslapbul">
                <input type="hidden" name="hapus_id" id="hapus_id">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalHapuslapbulLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <p>Yakin ingin menghapus data <b id="hapus_nama"></b>?</p>
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
    <!-- JavaScripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
      $(document).ready(function() {
        $('#lapbulTable').DataTable({
          "autoWidth": false,
          "order": [[ 0, "desc" ]],
          initComplete: function () {
            this.api().columns([1,2,3,4]).every( function () {
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
      // Handle tombol Edit (reload data di modal)
      $(document).on('click', '.btnEditlapbul', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#edit_id').val(id);
        $('#edit_jenis').val($(this).data('jenis'));
        $('#edit_nama').val(nama);
        $('#edit_kecamatan_id').val($(this).data('kecamatan-id'));
        $('#edit_kode').val($(this).data('kode'));
        $('#modalEditlapbul').modal('show');
      });

      // Handle tombol Hapus (isi nama di modal konfirmasi)
      $(document).on('click', '.btnHapuslapbul', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#hapus_id').val(id);
        $('#hapus_nama').text(nama);
        $('#modalHapuslapbul').modal('show');
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