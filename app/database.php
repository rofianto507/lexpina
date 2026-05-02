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
    $kategori=$_GET["kategori"] ?? "";
  $_SESSION["menu"]=$kategori;
  $kategori_nama= ucwords(str_replace('-', ' ', $kategori));
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
        header("Location: database?kategori=" . $kategori . "&status=csrf_failed");
        exit;
    }

    // 1. PROSES HAPUS DATA
    if (isset($_POST['hapus_id']) && !empty($_POST['hapus_id'])) {
        $hapus_id = (int)$_POST['hapus_id'];
        try {
            $stmt_hapus = $pdo->prepare("update `databases` set status = 0 WHERE id = ?");
            $stmt_hapus->execute([$hapus_id]);
            
            header("Location: database?kategori=" . $kategori . "&status=hapus_sukses");
            exit;
        } catch (PDOException $e) {
            die("Error menghapus data: " . $e->getMessage());
        }
    }

    // 2. PROSES TAMBAH DATA
    if (isset($_POST['judul'])) {
        $judul = $_POST['judul'];
        $sumber = $_POST['sumber'];
        $tgl_penetapan = $_POST['tanggal_penetapan'];
        $tgl_pengundangan = $_POST['tanggal_pengundangan'];
        $tgl_berlaku = $_POST['tanggal_berlaku'];
        $deskripsi = $_POST['deskripsi'];
        $dicabut = $_POST['dicabut'];
        $dicabut_sebagian = $_POST['dicabut_sebagian'] ?? '';
        $mencabut = $_POST['mencabut'];
        $mencabut_sebagian = $_POST['mencabut_sebagian'] ?? '';
        $diubah = $_POST['diubah'] ?? '';
        $diubah_sebagian = $_POST['diubah_sebagian'] ?? '';
        $mengubah = $_POST['mengubah'] ?? '';
        $mengubah_sebagian = $_POST['mengubah_sebagian'] ?? '';
        $uji_materi = $_POST['uji_materi'] ?? '';
        $kategori_post = $_POST['kategori'];
        $rekomendasi = isset($_POST['rekomendasi']) ? 1 : 0;
        
        // Tangkap Array dari multi-select konsolidasi
        $konsolidasi_ids  = isset($_POST['konsolidasi_ids']) ? $_POST['konsolidasi_ids'] : [];
        $file_url = ''; 

        // Logika Upload File PDF
        if (isset($_FILES['file_pdf']) && $_FILES['file_pdf']['error'] == 0) {
            $ext = pathinfo($_FILES['file_pdf']['name'], PATHINFO_EXTENSION);
            if (strtolower($ext) == 'pdf') {
                $upload_dir = '../public/upload/documents/';
                // Pastikan folder ada
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Buat nama file unik agar tidak tertimpa
                $file_name = time() . '_' . md5($_FILES['file_pdf']['name']) . '.pdf';
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['file_pdf']['tmp_name'], $target_file)) {
                    $file_url = $file_name;
                }
            } else {
                die("Hanya file PDF yang diizinkan!");
            }
        }

        try {
            // Mulai Transaksi
            $pdo->beginTransaction();

            // 1. Simpan ke tabel databases
            $stmt_tambah = $pdo->prepare("INSERT INTO `databases` 
                (kategori, judul, sumber, tanggal_penetapan, tanggal_pengundangan, tanggal_berlaku, deskripsi, dicabut, dicabut_sebagian, mencabut, mencabut_sebagian, diubah, diubah_sebagian, mengubah, mengubah_sebagian, uji_materi, file_pdf, rekomendasi, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

            $stmt_tambah->execute([
                $kategori_post, $judul, $sumber, $tgl_penetapan, $tgl_pengundangan, $tgl_berlaku, $deskripsi, $dicabut, $dicabut_sebagian, $mencabut, $mencabut_sebagian, $diubah, $diubah_sebagian, $mengubah, $mengubah_sebagian, $uji_materi, $file_url, $rekomendasi
            ]);

            // Ambil ID yang baru saja digenerate oleh MySQL
            $new_dokumen_id = $pdo->lastInsertId();

            // 2. Simpan ke tabel relasi_konsolidasi (jika ada yang dicentang)
            if (!empty($konsolidasi_ids)) {
                $stmt_ins_rel = $pdo->prepare("INSERT INTO relasi_konsolidasi (parent_id, konsolidasi_id) VALUES (?, ?)");
                foreach ($konsolidasi_ids as $k_id) {
                    $stmt_ins_rel->execute([$new_dokumen_id, $k_id]);
                }
            }

            $pdo->commit(); // Akhiri transaksi dan simpan permanen

            header("Location: database?kategori=" . $kategori . "&status=sukses");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack(); // Batalkan jika gagal
            die("Error menambah data: " . $e->getMessage());
        }
    }
}

// AMBIL SEMUA DOKUMEN BERKATEGORI KONSOLIDASI (Untuk Opsi Checkbox)
try {
    $stmt_all_kon = $pdo->prepare("SELECT id, judul FROM `databases` WHERE kategori = 'peraturan-konsolidasi' AND status = 1 ORDER BY judul ASC");
    $stmt_all_kon->execute();
    $semua_konsolidasi = $stmt_all_kon->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $semua_konsolidasi = [];
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database - <?php echo htmlspecialchars($kategori_nama); ?></title>

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
  <body>
    <main class="main" id="top">
      <div class="container-fluid" data-layout="container">
        <?php include_once("navbar.php") ?>
        <div class="content">
          <?php include_once("header.php") ?>
          <?php if(isset($_GET['status']) && $_GET['status']=='sukses'): ?>
          <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            Data berhasil ditambahkan.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          
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
                  <h5 class="fs-0 mb-0"><span class="fa fa-folder me-2 fs-0"></span>Data <?php echo htmlspecialchars($kategori_nama); ?></h5>
                </div>
                <div class="col-auto ms-auto">
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahDokumen">
                    Tambah Data
                  </button>
                </div>
              </div>
            </div>
            <div class="card-body bg-light">
              <div class="table-responsive">
                <table id="dokumenTable" class="display table table-striped table-bordered table-sm" >
                  <thead class="bg-primary text-white">
                    <tr>
                      <th>ID</th>
                      <th>Tanggal</th>
                      <th>Judul</th>
                      <th>Sumber</th>
                   
                      <th>Tanggal Penetapan</th>
                      <th>Tanggal Pengundangan</th>
                      <th>Tanggal Berlaku</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php
                  
                    $query = "SELECT* from `databases` WHERE status=1 and kategori=? ORDER BY id DESC";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$kategori]);
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                      echo "<tr>
                        <td>".$row['id']."</td>
                        <td>".htmlspecialchars(date('d/m/Y H:i', strtotime($row['created_at'])))."</td>
                        <td>".htmlspecialchars($row['judul'])."</td>
                        <td>".htmlspecialchars($row['sumber'])."</td>                      
                        <td>".htmlspecialchars(date('d/m/Y', strtotime($row['tanggal_penetapan'])))."</td>
                        <td>".htmlspecialchars(date('d/m/Y', strtotime($row['tanggal_pengundangan'])))."</td>
                        <td>".htmlspecialchars(date('d/m/Y', strtotime($row['tanggal_berlaku'])))."</td>
                        <td>
                          <a href='database-edit?id=".$row['id']."' class='btn btn-sm btn-info'>Edit</a>
                          <button class='btn btn-sm btn-danger btnHapusDokumen' data-id='".$row['id']."' data-judul='".htmlspecialchars($row['judul'], ENT_QUOTES)."'>Hapus</button>
                        </td>
                      </tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          
          <!-- Modal Konfirmasi Hapus Dokumen -->
            <div class="modal fade" id="modalHapusDokumen" tabindex="-1" aria-labelledby="modalHapusDokumenLabel" aria-hidden="true">
              <div class="modal-dialog">
                <form method="POST" id="formHapusDokumen">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                  <input type="hidden" name="hapus_id" id="hapus_id_dokumen">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalHapusDokumenLabel">Konfirmasi Hapus</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <p>Yakin ingin menghapus data dokumen dengan judul: <b id="hapus_judul"></b>?</p>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                      <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>

          <!-- Modal Tambah Data Dokumen -->
          <div class="modal fade" id="modalTambahDokumen" tabindex="-1" aria-labelledby="modalTambahDokumenLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <form action="" method="POST" id="formTambahDokumen" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="kategori" value="<?php echo htmlspecialchars($kategori); ?>">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahDokumenLabel">Tambah Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label class="form-label">Judul</label>
                      <input type="text" class="form-control form-control-sm" name="judul" required>
                    </div>
                                   
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tanggal Penetapan</label>
                            <input type="date" class="form-control form-control-sm" name="tanggal_penetapan" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tanggal Pengundangan</label>
                            <input type="date" class="form-control form-control-sm" name="tanggal_pengundangan" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tanggal Berlaku</label>
                            <input type="date" class="form-control form-control-sm" name="tanggal_berlaku" required>
                        </div>
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Sumber</label>
                      <input type="text" class="form-control form-control-sm" name="sumber" required>
                    </div>
                      <div class="mb-3">
                        <label class="form-label">File PDF (URL)</label>
                        <input type="file" class="form-control form-control-sm" name="file_pdf" accept=".pdf" required>
                      </div>
                    <div class="mb-3">
                      <label class="form-label">Deskripsi</label>
                      <textarea class="form-control form-control-sm" name="deskripsi" rows="5"></textarea>
                    </div>

                    <!-- AREA RELASI KONSOLIDASI (TAMBAH DATA BARU) -->
                    <div class="mb-4 mt-4">
                        <div class="p-3 border rounded" style="background-color: #fff4e5; border-color: #ffa117 !important;">
                            <label class="form-label fw-bold text-warning"><i class="fa fa-link"></i> Hubungkan ke Peraturan Konsolidasi</label>
                            
                            <div style="max-height: 200px; overflow-y: auto; background: #fff; border: 1px solid #ddd; padding: 10px; border-radius: 5px;">
                                <?php if(empty($semua_konsolidasi)): ?>
                                    <p class="text-muted mb-0 small">Belum ada dokumen Peraturan Konsolidasi di sistem.</p>
                                <?php else: ?>
                                    <?php foreach($semua_konsolidasi as $kon): ?>
                                        <div class="form-check" style=" border-bottom: 1px solid #f8f9fa; margin-bottom: 0; padding: 5px 10px;">
                                            <input class="form-check-input" type="checkbox" name="konsolidasi_ids[]" value="<?php echo $kon['id']; ?>" id="kon_add_<?php echo $kon['id']; ?>">
                                            <label class="form-check-label w-100" for="kon_add_<?php echo $kon['id']; ?>" style="cursor: pointer; font-size: 14px;">
                                                <span class="text-secondary hover-text-dark">
                                                    <?php echo htmlspecialchars($kon['judul']); ?>
                                                </span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted d-block mt-2">Centang kotak jika dokumen baru ini terkait dengan naskah konsolidasi yang sudah ada.</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Dicabut</label><textarea class="form-control form-control-sm" name="dicabut" rows="4"></textarea></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Dicabut sebagian</label><textarea class="form-control form-control-sm" name="dicabut_sebagian" rows="4"></textarea></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Mencabut</label><textarea class="form-control form-control-sm" name="mencabut" rows="4"></textarea></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Mencabut sebagian</label><textarea class="form-control form-control-sm" name="mencabut_sebagian" rows="4"></textarea></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Diubah</label><textarea class="form-control form-control-sm" name="diubah" rows="4"></textarea></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Diubah sebagian</label><textarea class="form-control form-control-sm" name="diubah_sebagian" rows="4"></textarea></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Mengubah</label><textarea class="form-control form-control-sm" name="mengubah" rows="4"></textarea></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Mengubah sebagian</label><textarea class="form-control form-control-sm" name="mengubah_sebagian" rows="4"></textarea></div>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Uji Materi</label>
                      <textarea class="form-control form-control-sm" name="uji_materi" rows="4"></textarea>
                    </div>
                    
                    <div class="mb-3 mt-4">
                      <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="rekomendasiCheck" name="rekomendasi" value="1">
                        <label class="form-check-label fw-bold text-primary" for="rekomendasiCheck" style="cursor:pointer;">
                          <i class="fa fa-star text-warning"></i> Jadikan Dokumen Rekomendasi
                        </label>
                      </div>
                      <small class="text-muted">Jika dicentang, dokumen ini akan tampil di bagian atas (slider) halaman beranda pengguna.</small>
                    </div>
                  </div>
                  <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan & Unggah Dokumen</button>
                  </div>
                </div>
              </form>
            </div>
          </div>
          <?php include_once("footer.php") ?>
        </div>
      </div>
    </main>

    <!-- OVERLAY PROGRESS BAR AJAX -->
    <div id="progressOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.95); z-index: 9999; flex-direction: column; justify-content: center; align-items: center; backdrop-filter: blur(5px);">
        <div style="width: 80%; max-width: 500px; text-align: center; background: #fff; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            <i class="fa fa-cloud-upload text-primary mb-3" style="font-size: 60px;"></i>
            <h3 class="mb-2 text-dark fw-bold">Menyimpan Dokumen</h3>
            <p class="text-muted mb-4">Sedang mengunggah data ke server LexPina...</p>
            
            <div class="progress" style="height: 25px; border-radius: 15px; background-color: #e9ecef; box-shadow: inset 0 1px 2px rgba(0,0,0,.1);">
                <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%; font-weight: bold; font-size: 14px;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
            
            <p id="progressText" class="mt-3 fw-bold text-primary fs-5">0% Terunggah</p>
            <small class="text-danger fw-bold"><i class="fa fa-warning"></i> Jangan tutup atau _refresh_ jendela ini!</small>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../vendors/jquery/jquery-3.7.0.min.js"></script>
    <script src="../vendors/datatables/datatables.min.js"></script>
     <script>
    $(document).ready(function() {
      $('#dokumenTable').DataTable({
        "autoWidth": false,
        "order": [[ 0, "desc" ]], 
      });

      // Fungsi Hapus
      $(document).on('click', '.btnHapusDokumen', function() {
          var id = $(this).data('id');
          var judul = $(this).data('judul');
          $('#hapus_id_dokumen').val(id);
          $('#hapus_judul').text(judul);
          $('#modalHapusDokumen').modal('show');
      });

      // FUNGSI AJAX PROGRESS BAR UNTUK TAMBAH DOKUMEN
      $('#formTambahDokumen').on('submit', function(e) {
            e.preventDefault(); 
            var form = $(this);
            var formData = new FormData(this); 
            var kategoriParam = $('input[name="kategori"]').val();

            // Sembunyikan Modal Tambah agar tidak menumpuk, lalu tampilkan Progress Bar
            $('#modalTambahDokumen').modal('hide');
            $('#progressOverlay').css('display', 'flex').hide().fadeIn();

            $.ajax({
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = Math.round((evt.loaded / evt.total) * 100);
                            $('#progressBar').css('width', percentComplete + '%');
                            $('#progressBar').attr('aria-valuenow', percentComplete);
                            $('#progressBar').text(percentComplete + '%');
                            $('#progressText').text(percentComplete + '% Data Terunggah');

                            if(percentComplete === 100) {
                                $('#progressText').text('Menyimpan ke Database... Mohon tunggu.');
                                $('#progressBar').removeClass('progress-bar-striped').addClass('bg-success');
                            }
                        }
                    }, false);
                    return xhr;
                },
                type: 'POST',
                url: '', // Post ke halaman ini sendiri
                data: formData,
                processData: false, 
                contentType: false, 
                success: function(response) {
                    // Redirect sukses
                    window.location.href = "database?kategori=" + kategoriParam + "&status=sukses";
                },
                error: function(xhr, status, error) {
                    $('#progressOverlay').fadeOut();
                    alert('Terjadi kesalahan saat mengunggah. Pastikan pengaturan server Nginx cukup untuk ukuran file ini.');
                }
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
    
    <style>
        /* CSS kecil untuk efek hover checkbox relasi di form tambah */
        .hover-text-dark:hover {
            color: #2c3e50 !important;
            font-weight: 600;
        }
    </style>
  </body>
</html>