<?php
session_start();
include("../config/configuration.php");

// 1. CEK AUTENTIKASI
if(!isset($_SESSION["id"]) || !isset($_SESSION["nama"])) {
  header("Location: ../login/");
  exit;
}

if(empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
  $nama=$_SESSION["nama"];
  $id=$_SESSION["id"];
  $username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
  $foto=$_SESSION["foto"];
  $last_login=$_SESSION["last_login"];

$id_dokumen = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ==========================================
// PROSES PENANGANAN FORM UPDATE (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi Token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Akses ditolak! Token keamanan tidak valid.");
    }

    $judul            = $_POST['judul'];
    $sumber           = $_POST['sumber'];
    $tgl_penetapan    = $_POST['tanggal_penetapan'];
    $tgl_pengundangan = $_POST['tanggal_pengundangan'];
    $tgl_berlaku      = $_POST['tanggal_berlaku'];
    $deskripsi        = $_POST['deskripsi'];
    $dicabut          = $_POST['dicabut'];
    $dicabut_sebagian = $_POST['dicabut_sebagian'] ?? '';
    $mencabut         = $_POST['mencabut'];
    $mencabut_sebagian   = $_POST['mencabut_sebagian'] ?? '';
    $diubah          = $_POST['diubah'] ?? '';
    $diubah_sebagian   = $_POST['diubah_sebagian'] ?? '';
    $mengubah         = $_POST['mengubah'] ?? '';
    $mengubah_sebagian   = $_POST['mengubah_sebagian'] ?? '';
    $uji_materi       = $_POST['uji_materi'] ?? '';
    $kategori_post    = $_POST['kategori'];
    $rekomendasi      = isset($_POST['rekomendasi']) ? 1 : 0; 
    
    // Tangkap Array dari multi-select konsolidasi
    $konsolidasi_ids  = isset($_POST['konsolidasi_ids']) ? $_POST['konsolidasi_ids'] : [];

    // Ambil nama file lama 
    $file_url = $_POST['file_lama']; 

    // Logika Upload File PDF Baru 
    if (isset($_FILES['file_pdf']) && $_FILES['file_pdf']['error'] == 0) {
        $ext = pathinfo($_FILES['file_pdf']['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) == 'pdf') {
            $upload_dir = '../public/upload/documents/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $file_name = time() . '_' . md5($_FILES['file_pdf']['name']) . '.pdf';
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['file_pdf']['tmp_name'], $target_file)) {
                if(!empty($file_url) && file_exists($upload_dir . $file_url)) {
                    unlink($upload_dir . $file_url);
                }
                $file_url = $file_name; 
            }
        } else {
            die("Hanya file PDF yang diizinkan!");
        }
    }

    try {
        $pdo->beginTransaction();

        // 1. UPDATE TABEL UTAMA (databases)
        $stmt_update = $pdo->prepare("UPDATE `databases` SET 
            kategori = ?, judul = ?, sumber = ?, tanggal_penetapan = ?, 
            tanggal_pengundangan = ?, tanggal_berlaku = ?, deskripsi = ?, 
            dicabut = ?, dicabut_sebagian = ?, mencabut = ?, mencabut_sebagian = ?, 
            diubah = ?, diubah_sebagian = ?, mengubah = ?, mengubah_sebagian = ?, 
            uji_materi = ?, file_pdf = ?, rekomendasi = ? 
            WHERE id = ?");
        
        $stmt_update->execute([
            $kategori_post, $judul, $sumber, $tgl_penetapan, $tgl_pengundangan, 
            $tgl_berlaku, $deskripsi, $dicabut, $dicabut_sebagian, $mencabut, $mencabut_sebagian, $diubah, $diubah_sebagian, $mengubah, $mengubah_sebagian, $uji_materi, $file_url, $rekomendasi, $id_dokumen
        ]);

        // 2. UPDATE TABEL RELASI 
        $stmt_del_rel = $pdo->prepare("DELETE FROM relasi_konsolidasi WHERE parent_id = ?");
        $stmt_del_rel->execute([$id_dokumen]);

        if (!empty($konsolidasi_ids)) {
            $stmt_ins_rel = $pdo->prepare("INSERT INTO relasi_konsolidasi (parent_id, konsolidasi_id) VALUES (?, ?)");
            foreach ($konsolidasi_ids as $k_id) {
                if ($k_id != $id_dokumen) {
                    $stmt_ins_rel->execute([$id_dokumen, $k_id]);
                }
            }
        }

        $pdo->commit();

        // Redirect jika sukses
        header("Location: database?kategori=" . $kategori_post . "&status=sukses_edit");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error mengubah data: " . $e->getMessage());
    }
}

// ==========================================
// AMBIL DATA DOKUMEN SAAT INI 
// ==========================================
try {
    $stmt = $pdo->prepare("SELECT * FROM `databases` WHERE id = ?");
    $stmt->execute([$id_dokumen]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        die("Dokumen tidak ditemukan!");
    }

    $views = isset($doc['views']) ? $doc['views'] : 0;
    $likes = isset($doc['likes']) ? $doc['likes'] : 0;
    $bookmarks = isset($doc['bookmarks']) ? $doc['bookmarks'] : 0;

    $stmt_all_kon = $pdo->prepare("SELECT id, judul FROM `databases` WHERE kategori = 'peraturan-konsolidasi' AND status = 1 ORDER BY judul ASC");
    $stmt_all_kon->execute();
    $semua_konsolidasi = $stmt_all_kon->fetchAll(PDO::FETCH_ASSOC);

    $stmt_curr_rel = $pdo->prepare("SELECT konsolidasi_id FROM relasi_konsolidasi WHERE parent_id = ?");
    $stmt_curr_rel->execute([$id_dokumen]);
    $relasi_saat_ini = $stmt_curr_rel->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Error mengambil data: " . $e->getMessage());
}

  $nama=$_SESSION["nama"];
  $_SESSION["menu"]=$doc['kategori'];
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Dokumen | LexPina</title>
 <link rel="apple-touch-icon" sizes="180x180" href="../assets/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/img/favicons/favicon-16x16.png">
    <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicons/favicon.ico">
    <meta name="theme-color" content="#ffffff">
    <script src="../assets/js/config.js"></script>
    <script src="../vendors/overlayscrollbars/OverlayScrollbars.min.js"></script>
    <link rel="stylesheet" type="text/css" href="../assets/icon/font-awesome/css/font-awesome.min.css">
    <link href="../vendors/prism/prism-okaidia.css" rel="stylesheet">
     <link href="../vendors/overlayscrollbars/OverlayScrollbars.min.css" rel="stylesheet">
    <link href="../assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
    <link href="../assets/css/theme.min.css" rel="stylesheet" id="style-default">
    <link href="../assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
    <link href="../assets/css/user.min.css" rel="stylesheet" id="user-style-default">
  </head>
  <body>
    <main class="main" id="top">
      <div class="container-fluid" data-layout="container">
        <?php include_once("navbar.php") ?>
        <div class="content">
          <?php include_once("header.php") ?>
          
          <div class="d-flex mb-4 mt-3">
            <span class="fa fa-edit me-2 fs-3 text-primary"></span>
            <div>
              <h4 class="mb-0">Edit Dokumen</h4>
              <span class="text-muted">Perbarui informasi dan file dokumen LexPina</span>
            </div>
          </div>

          <!-- PERUBAHAN 1: Tambahkan ID pada Form -->
          <form id="formEditDokumen" method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="file_lama" value="<?php echo htmlspecialchars($doc['file_pdf']); ?>">

            <div class="row g-3">
              <div class="col-lg-8">
                <div class="card mb-3">
                  <div class="card-header bg-light">
                    <h6 class="mb-0">Detail Dokumen</h6>
                  </div>
                  <div class="card-body">
                    <div class="row g-3">
                      <div class="col-md-12">
                        <label class="form-label">Judul Dokumen</label>
                        <input type="text" class="form-control" name="judul" value="<?php echo htmlspecialchars($doc['judul']); ?>" required>
                      </div>
                      
                      <div class="col-md-6">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="kategori" required>
                          <option value="peraturan" <?php if($doc['kategori']=='peraturan') echo 'selected'; ?>>Peraturan</option>
                          <option value="peraturan-konsolidasi" <?php if($doc['kategori']=='peraturan-konsolidasi') echo 'selected'; ?>>Peraturan Konsolidasi</option>
                          <option value="karya-ilmiah" <?php if($doc['kategori']=='karya-ilmiah') echo 'selected'; ?>>Karya Ilmiah</option>
                          <option value="jurnal" <?php if($doc['kategori']=='jurnal') echo 'selected'; ?>>Jurnal</option>
                          <option value="putusan" <?php if($doc['kategori']=='putusan') echo 'selected'; ?>>Putusan</option>
                          <option value="artikel" <?php if($doc['kategori']=='artikel') echo 'selected'; ?>>Artikel</option>
                          <option value="template-perjanjian" <?php if($doc['kategori']=='template-perjanjian') echo 'selected'; ?>>Template Perjanjian</option>
                        </select>
                      </div>

                      <div class="col-md-6">
                        <label class="form-label">Sumber</label>
                        <input type="text" class="form-control" name="sumber" value="<?php echo htmlspecialchars($doc['sumber']); ?>" required>
                      </div>

                      <div class="col-md-4">
                        <label class="form-label">Tanggal Penetapan</label>
                        <input type="date" class="form-control" name="tanggal_penetapan" value="<?php echo $doc['tanggal_penetapan']; ?>" required>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Tanggal Pengundangan</label>
                        <input type="date" class="form-control" name="tanggal_pengundangan" value="<?php echo $doc['tanggal_pengundangan']; ?>" required>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Tanggal Berlaku</label>
                        <input type="date" class="form-control" name="tanggal_berlaku" value="<?php echo $doc['tanggal_berlaku']; ?>" required>
                      </div>

                      <div class="col-md-12">
                        <label class="form-label">Deskripsi / Abstrak</label>
                        <textarea class="form-control" name="deskripsi" rows="4"><?php echo htmlspecialchars($doc['deskripsi'] ?? ''); ?></textarea>
                      </div>

                      <div class="col-md-12 mt-4 mb-2">
                        <div class="p-3 border rounded" style="background-color: #fff4e5; border-color: #ffa117 !important;">
                            <label class="form-label fw-bold text-warning"><i class="fa fa-link"></i> Hubungkan ke Peraturan Konsolidasi</label>
                            
                            <div style="max-height: 200px; overflow-y: auto; background: #fff; border: 1px solid #ddd; padding: 10px; border-radius: 5px;">
                                <?php if(empty($semua_konsolidasi)): ?>
                                    <p class="text-muted mb-0 small">Belum ada dokumen Peraturan Konsolidasi di sistem.</p>
                                <?php else: ?>
                                    <?php foreach($semua_konsolidasi as $kon): ?>
                                        <?php if($kon['id'] != $doc['id']): ?>
                                            <?php $is_linked = in_array($kon['id'], $relasi_saat_ini); ?>
                                            <div class="form-check" style=" border-bottom: 1px solid #f8f9fa; margin-bottom: 0;">
                                                <input class="form-check-input" type="checkbox" name="konsolidasi_ids[]" value="<?php echo $kon['id']; ?>" id="kon_<?php echo $kon['id']; ?>" <?php echo $is_linked ? 'checked' : ''; ?>>
                                                <label class="form-check-label w-100 d-flex justify-content-between align-items-center" for="kon_<?php echo $kon['id']; ?>" style="cursor: pointer; font-size: 14px;">
                                                    <span class="<?php echo $is_linked ? 'fw-bold text-dark' : 'text-muted'; ?>">
                                                        <?php echo htmlspecialchars($kon['judul']); ?>
                                                    </span>
                                                    <?php if($is_linked): ?>
                                                        <i class="fa fa-check-circle text-success fs-2" title="Telah Terhubung"></i>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                      </div>

                      <div class="col-md-6"><label class="form-label">Status: <b>Dicabut</b></label><textarea class="form-control" name="dicabut" rows="3"><?php echo htmlspecialchars($doc['dicabut'] ?? ''); ?></textarea></div>
                      <div class="col-md-6"><label class="form-label">Status: <b>Dicabut Sebagian</b></label><textarea class="form-control" name="dicabut_sebagian" rows="3"><?php echo htmlspecialchars($doc['dicabut_sebagian'] ?? ''); ?></textarea></div>
                      <div class="col-md-6"><label class="form-label">Status: <b>Mencabut</b></label><textarea class="form-control" name="mencabut" rows="3"><?php echo htmlspecialchars($doc['mencabut'] ?? ''); ?></textarea></div>
                      <div class="col-md-6"><label class="form-label">Status: <b>Mencabut Sebagian</b></label><textarea class="form-control" name="mencabut_sebagian" rows="3"><?php echo htmlspecialchars($doc['mencabut_sebagian'] ?? ''); ?></textarea></div>
                      <div class="col-md-6"><label class="form-label">Status: <b>Diubah</b></label><textarea class="form-control" name="diubah" rows="3"><?php echo htmlspecialchars($doc['diubah'] ?? ''); ?></textarea></div>
                      <div class="col-md-6"><label class="form-label">Status: <b>Diubah Sebagian</b></label><textarea class="form-control" name="diubah_sebagian" rows="3"><?php echo htmlspecialchars($doc['diubah_sebagian'] ?? ''); ?></textarea></div>
                      <div class="col-md-6"><label class="form-label">Status: <b>Mengubah</b></label><textarea class="form-control" name="mengubah" rows="3"><?php echo htmlspecialchars($doc['mengubah'] ?? ''); ?></textarea></div>
                      <div class="col-md-6"><label class="form-label">Status: <b>Mengubah Sebagian</b></label><textarea class="form-control" name="mengubah_sebagian" rows="3"><?php echo htmlspecialchars($doc['mengubah_sebagian'] ?? ''); ?></textarea></div>
                      <div class="col-md-12"><label class="form-label">Status: <b>Uji Materi</b></label><textarea class="form-control" name="uji_materi" rows="3"><?php echo htmlspecialchars($doc['uji_materi'] ?? ''); ?></textarea></div>

                      <div class="col-md-12 mt-4">
                        <div class="p-3 bg-light rounded border">
                          <label class="form-label text-primary"><i class="fa fa-file-pdf-o"></i> Ganti File PDF</label>
                          <input type="file" class="form-control" name="file_pdf" accept=".pdf">
                          <small class="text-muted mt-1 d-block">
                            * File saat ini: <a href="../public/upload/documents/<?php echo $doc['file_pdf']; ?>" target="_blank" class="fw-bold"><?php echo $doc['file_pdf']; ?></a><br>
                            * Biarkan kosong jika Anda <b>tidak ingin</b> mengganti dokumen PDF.
                          </small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-lg-4">
                <div class="card mb-3">
                  <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fa fa-cogs text-secondary me-2"></i>Pengaturan</h6>
                  </div>
                  <div class="card-body">
                    <div class="form-check form-switch mb-3">
                      <input class="form-check-input" type="checkbox" id="rekomendasiCheck" name="rekomendasi" value="1" <?php echo ($doc['rekomendasi'] == 1) ? 'checked' : ''; ?>>
                      <label class="form-check-label fw-bold text-dark" for="rekomendasiCheck">Jadikan Rekomendasi</label>
                    </div>

                    <hr class="my-4">
                    
                    <div class="alert alert-info py-2 fs--1" role="alert">
                      <i class="fa fa-info-circle me-1"></i> <strong>Petunjuk:</strong> Form akan memproses file secara visual. Mohon jangan tutup tab browser saat progress bar berjalan.
                    </div>

                    <div class="d-grid gap-2">
                      <button class="btn btn-primary" type="submit">
                        <i class="fa fa-save me-1"></i> Simpan Perubahan
                      </button>
                      <a href="database?kategori=<?php echo $doc['kategori']; ?>" class="btn btn-outline-secondary">
                        <i class="fa fa-times me-1"></i> Batal / Kembali
                      </a>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </form>
          <?php include_once("footer.php") ?>
        </div>
      </div>
    </main>

    <!-- PERUBAHAN 2: Tampilan Visual Layar Progress Bar -->
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

    <script src="../vendors/jquery/jquery-3.7.0.min.js"></script>
    <script src="../vendors/popper/popper.min.js"></script>
    <script src="../vendors/bootstrap/bootstrap.min.js"></script>
    <script src="../vendors/anchorjs/anchor.min.js"></script>
    <script src="../vendors/is/is.min.js"></script>
    <script src="../vendors/prism/prism.js"></script>
    <script src="../vendors/lodash/lodash.min.js"></script>
    <script src="../vendors/list.js/list.min.js"></script>
    <script src="../assets/js/theme.js"></script>

    <!-- PERUBAHAN 3: Skrip AJAX Pencegat Form & Penggerak Progress Bar -->
    <script>
    $(document).ready(function() {
        $('#formEditDokumen').on('submit', function(e) {
            e.preventDefault(); // Hentikan form reload halaman bawaan browser

            var form = $(this);
            var formData = new FormData(this); // Tarik semua data termasuk PDF

            // 1. Munculkan Layar Putih Progress
            $('#progressOverlay').css('display', 'flex').hide().fadeIn();

            // 2. Eksekusi Upload Background via AJAX
            $.ajax({
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    
                    // Sensor pemantau proses upload
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = Math.round((evt.loaded / evt.total) * 100);
                            
                            // Animasi bar dan teks
                            $('#progressBar').css('width', percentComplete + '%');
                            $('#progressBar').attr('aria-valuenow', percentComplete);
                            $('#progressBar').text(percentComplete + '%');
                            $('#progressText').text(percentComplete + '% Data Terunggah');

                            // Jika file sudah terupload sepenuhnya, tunggu respon PHP (simpan ke database)
                            if(percentComplete === 100) {
                                $('#progressText').text('Menyimpan ke Database... Mohon tunggu.');
                                $('#progressBar').removeClass('progress-bar-striped').addClass('bg-success');
                            }
                        }
                    }, false);
                    return xhr;
                },
                type: 'POST',
                url: form.attr('action'),
                data: formData,
                processData: false, // Matikan agar string bawaan JS tidak merusak PDF
                contentType: false, // Matikan agar form otomatis menyusun boundary multipart
                success: function(response) {
                    // Berhasil! Lempar ke halaman kategori terkait
                    var kategori = $('select[name="kategori"]').val();
                    window.location.href = "database?kategori=" + kategori + "&status=sukses_edit";
                },
                error: function(xhr, status, error) {
                    // Jika Nginx menolak (413) atau error lain, matikan layar dan munculkan notif
                    $('#progressOverlay').fadeOut();
                    alert('Terjadi kesalahan saat mengunggah. Pastikan pengaturan Nginx (client_max_body_size) server Anda cukup untuk ukuran file ini.');
                }
            });
        });
    });
    </script>
  </body>
</html>