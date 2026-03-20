<?php
session_start();
include("../config/configuration.php");
if($_SESSION["nama"]=="" || $_SESSION["id"]==""){
  header("Location: ../index");
  exit;
}
$_SESSION["menu"]="kriminalitas";
  $menu=$_SESSION["menu"];
  $id_user=$_SESSION["id"];
  $nama=$_SESSION["nama"];
  $id=$_SESSION["id"];
  $username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
  $foto=$_SESSION["foto"];
  $polres_id=$_SESSION["polres_id"] ?? null;
  $polsek_id=$_SESSION["polsek_id"] ?? null;
  $last_login=$_SESSION["last_login"];

if(!isset($_GET['id']) || !intval($_GET['id'])){
  header("Location: kriminalitas");
  exit;
}
$kid = intval($_GET['id']);

$stmt = $pdo->prepare("
  SELECT k.*, 
    sk.nama AS sub_kategori_nama,
    kat.nama AS kategori_nama,
    d.nama AS desa_nama,
    kc.nama AS kecamatan_nama,
    kb.nama AS kabupaten_nama,
    s.nama AS sumber_nama,
    jt.nama AS jenis_tkp_nama,
    u.nama AS user_nama
  FROM kriminals k
    LEFT JOIN kriminal_sub_kategoris sk ON k.sub_kategori_id = sk.id
    LEFT JOIN kriminal_kategoris kat ON sk.kategori_id = kat.id
    LEFT JOIN desas d ON k.desa_id = d.id
    LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
    LEFT JOIN kabupatens kb ON kc.kabupaten_id = kb.id
    LEFT JOIN sumbers s ON k.sumber_id = s.id
    LEFT JOIN jenis_tkps jt ON k.jenis_tkp_id = jt.id
    LEFT JOIN users u ON k.user_id = u.id
  WHERE k.id = ?
");
$stmt->execute([$kid]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$data){
  header("Location: kriminalitas");
  exit;
}

// Ambil data provinsi untuk peta
$query_provinsi = $pdo->query("SELECT * FROM provinsis WHERE status=1 LIMIT 1");
$data_provinsi = $query_provinsi->fetch();
$lat_prov = $data_provinsi["lat"];
$lng_prov = $data_provinsi["lng"];
$lat = !empty($data['latitude']) ? $data['latitude'] : $lat_prov;
$lng = !empty($data['longitude']) ? $data['longitude'] : $lng_prov;
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Detail Kriminalitas | Peta Digital</title>
  <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicons/favicon.ico">
  <script src="../assets/js/config.js"></script>
  <script src="../vendors/overlayscrollbars/OverlayScrollbars.min.js"></script>
  <link href="../vendors/overlayscrollbars/OverlayScrollbars.min.css" rel="stylesheet">
  <link href="../assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
  <link href="../assets/css/theme.min.css" rel="stylesheet" id="style-default">
  <link href="../assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
  <link href="../assets/css/user.min.css" rel="stylesheet" id="user-style-default">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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

      <!-- HEADER -->
      <div class="card mb-3">
        <div class="card-header">
          <div class="row flex-between-end">
            <div class="col-auto">
              <h5 class="mb-0">
                <span class="fas fa-eye me-2"></span> Detail Data Kriminalitas
              </h5>
            </div>
            <div class="col-auto ms-auto">
                <a href="javascript:history.back()" class="btn btn-secondary btn-sm me-1">
                <i class="fas fa-arrow-left"></i> Kembali
                </a>
              <a href="kriminalitas-edit?id=<?=$data['id']?>" class="btn btn-warning btn-sm">
                <i class="fas fa-edit"></i> Edit
              </a>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- KOLOM KIRI: INFO DATA -->
        <div class="col-lg-7">
          <div class="card mb-3">
            <div class="card-header"><b>Informasi Laporan</b></div>
            <div class="card-body">
              <table class="table table-sm table-borderless">
                <tr>
                  <td width="180"><b>No. LP</b></td>
                  <td><?= htmlspecialchars($data['no_lp'] ?? '-') ?></td>
                </tr>
                <tr>
                  <td><b>Kategori</b></td>
                  <td><?= htmlspecialchars($data['kategori_nama'] ?? '-') ?></td>
                </tr>
                <tr>
                  <td><b>Sub Kategori</b></td>
                  <td><?= htmlspecialchars($data['sub_kategori_nama'] ?? '-') ?></td>
                </tr>
                <tr>
                  <td><b>Jenis TKP</b></td>
                  <td><?= htmlspecialchars($data['jenis_tkp_nama'] ?? '-') ?></td>
                </tr>
                <tr>
                  <td><b>State</b></td>
                  <td>
                    <span class="badge bg-<?= $data['state']=='SELESAI' ? 'success' : 'warning' ?>">
                      <?= htmlspecialchars($data['state'] ?? '-') ?>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td><b>Sub State</b></td>
                  <td><?= htmlspecialchars($data['sub_state'] ?? '-') ?></td>
                </tr>
                <?php if ($data['state'] == 'SELESAI'): ?>
                <tr>
                  <td><b>Tanggal Selesai</b></td>
                  <td><?= !empty($data['tanggal_selesai']) ? date('d/m/Y H:i', strtotime($data['tanggal_selesai'])) . ' WIB' : '-' ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                  <td><b>Sumber Dokumen</b></td>
                  <td><?= htmlspecialchars($data['sumber_nama'] ?? '-') ?></td>
                </tr>
                <tr>
                  <td><b>Tanggal Kejadian</b></td>
                  <td><?= !empty($data['tanggal_kejadian']) ? date('d/m/Y H:i', strtotime($data['tanggal_kejadian'])) . ' WIB' : '-' ?></td>
                </tr>
                <tr>
                  <td><b>Tanggal Laporan</b></td>
                  <td><?= !empty($data['tanggal_laporan']) ? date('d/m/Y H:i', strtotime($data['tanggal_laporan'])) . ' WIB' : '-' ?></td>
                </tr>
                <tr>
                  <td><b>Input oleh</b></td>
                  <td><?= htmlspecialchars($data['user_nama'] ?? '-') ?></td>
                </tr>
              </table>
            </div>
          </div>

          <!-- LOKASI -->
          <div class="card mb-3">
            <div class="card-header"><b>Lokasi Kejadian</b></div>
            <div class="card-body">
              <table class="table table-sm table-borderless">
                <tr>
                  <td width="180"><b>Kabupaten</b></td>
                  <td><?= htmlspecialchars($data['kabupaten_nama'] ?? '-') ?></td>
                </tr>
                <tr>
                  <td><b>Kecamatan</b></td>
                  <td><?= htmlspecialchars($data['kecamatan_nama'] ?? '-') ?></td>
                </tr>
                <tr>
                  <td><b>Desa</b></td>
                  <td><?= htmlspecialchars($data['desa_nama'] ?? '-') ?></td>
                </tr>
                <tr>
                  <td><b>Nama Lokasi</b></td>
                  <td><?= htmlspecialchars($data['nama'] ?? '-') ?></td>
                </tr>
                <tr>
                  <td><b>Latitude</b></td>
                  <td><?= htmlspecialchars($data['latitude'] ?? '-') ?></td>
                </tr>
                <tr>
                  <td><b>Longitude</b></td>
                  <td><?= htmlspecialchars($data['longitude'] ?? '-') ?></td>
                </tr>
              </table>
            </div>
          </div>

          <!-- DETAIL PERISTIWA -->
          <div class="card mb-3">
            <div class="card-header"><b>Detail Peristiwa</b></div>
            <div class="card-body">
              <table class="table table-sm table-borderless">
                <tr>
                  <td width="180"><b>Pelapor</b></td>
                  <td><pre class="mb-0" style="white-space:pre-wrap;font-family:inherit;"><?= htmlspecialchars($data['pelapor'] ?? '-') ?></pre></td>
                </tr>
                <tr>
                  <td><b>Terlapor</b></td>
                  <td><?= htmlspecialchars($data['terlapor'] ?? '-') ?></td>
                </tr>
                <tr>
                  <td><b>Korban</b></td>
                  <td><pre class="mb-0" style="white-space:pre-wrap;font-family:inherit;"><?= htmlspecialchars($data['korban'] ?? '-') ?></pre></td>
                </tr>
                <tr>
                  <td><b>Saksi</b></td>
                  <td><?= htmlspecialchars($data['saksi'] ?? '-') ?></td>
                </tr>
                <tr>
                  <td><b>Barang Bukti</b></td>
                  <td><?= htmlspecialchars($data['barang_bukti'] ?? '-') ?></td>
                </tr>
                <tr>
                  <td><b>Uraian Singkat</b></td>
                  <td><pre class="mb-0" style="white-space:pre-wrap;font-family:inherit;"><?= htmlspecialchars($data['uraian'] ?? '-') ?></pre></td>
                </tr>
                <tr>
                  <td><b>Keterangan</b></td>
                  <td><?= htmlspecialchars($data['keterangan'] ?? '-') ?></td>
                </tr>
              </table>
            </div>
          </div>

          <!-- FOTO -->
          <?php if(!empty($data['foto'])): ?>
          <div class="card mb-3">
            <div class="card-header"><b>Foto</b></div>
            <div class="card-body text-center">
              <img src="../public/upload/kriminal/<?= htmlspecialchars($data['foto']) ?>" 
                   class="img-fluid img-thumbnail" style="max-height:400px;">
            </div>
          </div>
          <?php endif; ?>
        </div>

        <!-- KOLOM KANAN: PETA -->
        <div class="col-lg-5">
          <div class="card mb-3">
            <div class="card-header"><b>Peta Lokasi</b></div>
            <div class="card-body p-0">
              <div id="viewMap" style="height: 450px;"></div>
            </div>
          </div>
          <div class="card mb-3">
            <div class="card-header">
               <b>Data Berkas Lampiran</b> 
            </div>
            <div class="card-body p-0">
                <?php
                  $lampiran = $pdo->prepare("SELECT * FROM kriminal_attachments WHERE status=1 AND kriminal_id=? ORDER BY created_at DESC");
                  $lampiran->execute([$data['id']]);
                  $lampiran = $lampiran->fetchAll();

                  if($lampiran){
                      echo '<div class="list-group list-group-flush">';
                      foreach($lampiran as $l){
                          // Logika sederhana untuk icon berdasarkan file (opsional)
                          $ext = pathinfo($l['file'], PATHINFO_EXTENSION);
                          $icon = 'fa-file-alt';
                          if(in_array($ext, ['jpg', 'jpeg', 'png'])) $icon = 'fa-file-image';
                          if($ext == 'pdf') $icon = 'fa-file-pdf';

                          echo '<div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">';
                          echo '  <div class="d-flex align-items-center">';
                          echo '    <div class="icon-box me-3 text-secondary text-center" style="width: 30px;"><i class="fas '.$icon.' fa-lg"></i></div>';
                          echo '    <div>';
                          echo '      <div class="fw-bold text-dark mb-0">' . htmlspecialchars($l['nama']) . '</div>';
                          echo '      <small class="text-muted" style="font-size: 0.75rem;">' . $l['keterangan'] . '</small>';
                          echo '    </div>';
                          echo '  </div>';
                          echo '  <a href="../public/upload/kriminal/' . htmlspecialchars($l['file']) . '" target="_blank" class="btn btn-outline-primary btn-sm  px-3 shadow-sm">';
                          echo '    <i class="fas fa-eye me-1"></i> Lihat';
                          echo '  </a>';
                          echo '</div>';
                      }
                      echo '</div>';
                  } else {
                      echo '<div class="p-4 text-center text-muted">';
                      echo '  <i class="fas fa-folder-open fa-2x mb-2 opacity-25"></i>';
                      echo '  <p class="mb-0"><em>Tidak ada berkas terlampir.</em></p>';
                      echo '</div>';
                  }
                  ?>
              </div>
          </div>
          <div class="card mb-3">
            <div class="card-header"><b>Riwayat Aktifitas Data</b></div>
            <div class="card-body p-0">
              <?php
                $history = $pdo->prepare(" SELECT l.*, u.nama AS user_nama, u.username
                  FROM user_logs l
                  LEFT JOIN users u ON l.user_id = u.id
                  WHERE l.modul = 'kriminalitas' AND l.data_id = ?
                  ORDER BY l.created_at DESC");
                $history->execute([$data['id']]);
                $history = $history->fetchAll();

                if($history){
                    echo '<div class="list-group list-group-flush">';
                    foreach($history as $h){
                        echo '<div class="list-group-item list-group-item-action d-flex justify-content-between align-items-start py-3">';
                        echo '  <div>';
                        echo '    <div class="fw-bold text-dark mb-1">' . htmlspecialchars($h['aktivitas']) . '</div>';
                        echo '     <div class="text-muted mb-1" style="font-size: 0.85rem;">' . htmlspecialchars($h['keterangan']) . '</div>';
                        echo '    <small class="text-muted" style="font-size: 0.75rem;">' . date('d/m/Y H:i', strtotime($h['created_at'])) . ' WIB - oleh ' . htmlspecialchars($h['user_nama']) . '</small>';

                        echo '  </div>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="p-4 text-center text-muted">';
                    echo '  <i class="fas fa-history fa-2x mb-2 opacity-25"></i>';
                    echo '  <p class="mb-0"><em>Belum ada aktivitas.</em></p>';
                    echo '</div>';
                }
              ?>
            </div>
          </div>
        </div>
      </div>

      <?php include_once("footer.php") ?>
    </div>
  </div>
</main>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="../vendors/popper/popper.min.js"></script>
<script src="../vendors/bootstrap/bootstrap.min.js"></script>
<script src="../vendors/anchorjs/anchor.min.js"></script>
<script src="../vendors/is/is.min.js"></script>
<script src="../vendors/fontawesome/all.min.js"></script>
<script src="../vendors/lodash/lodash.min.js"></script>
<script src="../vendors/list.js/list.min.js"></script>
<script src="../assets/js/theme.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var lat = <?= json_encode(floatval($lat)) ?>;
  var lng = <?= json_encode(floatval($lng)) ?>;
  var map = L.map('viewMap').setView([lat, lng], 15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19}).addTo(map);

  <?php if(!empty($data['latitude']) && !empty($data['longitude'])): ?>
  var marker = L.marker([lat, lng], {
    icon: L.icon({
      iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
      iconSize: [25, 41],
      iconAnchor: [12, 41],
      popupAnchor: [1, -34],
      shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
      shadowSize: [41, 41]
    })
  }).addTo(map);
  marker.bindPopup(
    '<b><?= addslashes(htmlspecialchars($data["no_lp"] ?? "-")) ?></b><br>' +
    '<?= addslashes(htmlspecialchars($data["nama"] ?? "-")) ?><br>' +
    'Koordinat: ' + lat + ', ' + lng
  ).openPopup();
  <?php endif; ?>

  setTimeout(function(){ map.invalidateSize(); }, 300);
});
</script>
</body>
</html>