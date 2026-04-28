<?php
session_start();
include("../config/configuration.php");
if(!isset($_SESSION["id"]) || !isset($_SESSION["nama"])) {
  header("Location: ../login/");
  exit;
}
  $_SESSION["menu"]="dashboard";
  $nama=$_SESSION["nama"];
  $id=$_SESSION["id"];
  $username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
  $foto=$_SESSION["foto"];
  $last_login=$_SESSION["last_login"];

  // ==========================================
  // QUERY DATA STATISTIK DASHBOARD REAL-TIME
  // ==========================================
  try {
      // 1. Hitung Transaksi (Hari Ini, Pending, dan Selesai/Lunas)
      $stmt_trx_baru = $pdo->query("SELECT COUNT(*) FROM transaksis WHERE DATE(created_at) = CURDATE()");
      $trx_baru = $stmt_trx_baru->fetchColumn();

      $stmt_trx_pending = $pdo->query("SELECT COUNT(*) FROM transaksis WHERE status = 'PENDING'");
      $trx_pending = $stmt_trx_pending->fetchColumn();

      $stmt_trx_selesai = $pdo->query("SELECT COUNT(*) FROM transaksis WHERE status = 'LUNAS'");
      $trx_selesai = $stmt_trx_selesai->fetchColumn();

      // 2. Hitung Pengunjung Unik (Berdasarkan session_id dari tabel visitors)
      $stmt_visitors = $pdo->query("SELECT COUNT(DISTINCT session_id) FROM visitors");
      $total_visitors = $stmt_visitors->fetchColumn();

      // 3. Hitung Total Pengguna Biasa (Akses: PENGGUNA)
      $stmt_users = $pdo->query("SELECT COUNT(*) FROM users WHERE akses = 'PENGGUNA'");
      $total_users = $stmt_users->fetchColumn();

      // 4. Hitung Total Member Premium (Akses: MEMBER)
      $stmt_members = $pdo->query("SELECT COUNT(*) FROM users WHERE akses = 'MEMBER'");
      $total_members = $stmt_members->fetchColumn();

  } catch (PDOException $e) {
      // Set nilai default 0 jika terjadi error koneksi ke tabel
      $trx_baru = $trx_pending = $trx_selesai = $total_visitors = $total_users = $total_members = 0;
  }
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | <?php echo htmlspecialchars($app_name); ?></title>
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/img/favicons/favicon-16x16.png">
    <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicons/favicon.ico">
    <link rel="manifest" href="../assets/img/favicons/manifest.json">
    <meta name="msapplication-TileImage" content="../assets/img/favicons/mstile-150x150.png">
    <meta name="theme-color" content="#ffffff">
    <script src="../assets/js/config.js"></script>
    <script src="../vendors/overlayscrollbars/OverlayScrollbars.min.js"></script>
    <link href="../vendors/choices/choices.min.css" rel="stylesheet" />
    <script src="../vendors/choices/choices.min.js"></script>
    <link rel="stylesheet" href="../vendors/leaflet/leaflet.css" />
    <link rel="stylesheet" href="../vendors/leaflet.markercluster/MarkerCluster.Default.css" />
    <link href="../vendors/flatpickr/flatpickr.min.css" rel="stylesheet" />
     <link rel="stylesheet" type="text/css" href="../vendors/datatables/datatables.min.css"/>
    <link rel="stylesheet" type="text/css" href="../assets/icon/font-awesome/css/font-awesome.min.css">
    <link href="../vendors/prism/prism-okaidia.css" rel="stylesheet">
     <link href="../vendors/overlayscrollbars/OverlayScrollbars.min.css" rel="stylesheet">
    <link href="../assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
    <link href="../assets/css/theme.min.css" rel="stylesheet" id="style-default">
    <link href="../assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
    <link href="../assets/css/user.min.css" rel="stylesheet" id="user-style-default">
    <link href="../assets/css/index.css" rel="stylesheet" /> 
  </head>
    <body >
    <main class="main" id="top">
      <div class="container-fluid" data-layout="container">         
        <?php include_once("navbar.php") ?>
        <div class="content">
          <?php include_once("header.php") ?>          
            <div class="row mb-3">
              <div class="col">
                <div class="card bg-100 shadow-none border">
                  <div class="row gx-0 flex-between-center">
                    <div class="col-sm-auto d-flex align-items-center"><img class="ms-n2" src="../assets/img/illustrations/crm-bar-chart.png" alt="" width="90" />
                      <div>
                        <h6 class="text-primary fs--1 mb-0">Selamat Datang, <?php echo htmlspecialchars($nama); ?></h6>
                        <h4 class="text-primary fw-bold mb-0"><?php echo htmlspecialchars($app_name); ?></h4>
                      </div>
                      <img class="ms-n4 d-md-none d-lg-block" src="../assets/img/illustrations/crm-line-chart.png" alt="" width="150" />
                    </div>
                    <div class="col-md-auto p-3" id="card-stats">
                      <div class="row align-items-center">
                        <div class="col-lg-4 border-lg-end border-bottom border-lg-0 pb-3 pb-lg-0">
                          <div class="d-flex flex-between-center mb-3">
                            <div class="d-flex align-items-center">
                              <div class="icon-item icon-item-sm bg-soft-danger shadow-none me-2 bg-soft-danger"><span class="fs--2 fa fa-cart-plus text-danger"></span></div>
                              <h6 class="mb-0" id="stat-label-1">Transaksi Hari Ini</h6>
                            </div>                               
                          </div>
                          <div class="d-flex">
                            <div class="d-flex">
                              <p class="font-sans-serif lh-1 mb-1 fs-4 pe-2" id="stat-value-1"><?php echo number_format($trx_baru, 0, ',', '.'); ?></p>                    
                            </div>                                
                          </div>
                        </div>
                        <div class="col-lg-4 border-lg-end border-bottom border-lg-0 pb-3 pb-lg-0">
                          <div class="d-flex flex-between-center mb-3">
                            <div class="d-flex align-items-center">
                              <div class="icon-item icon-item-sm bg-soft-warning shadow-none me-2 bg-soft-warning"><span class="fs--2 fa fa-clock-o text-warning"></span></div>
                              <h6 class="mb-0" id="stat-label-2">Menunggu Validasi</h6>
                            </div>
                          </div>
                          <div class="d-flex">
                            <div class="d-flex">
                              <p class="font-sans-serif lh-1 mb-1 fs-4 pe-2" id="stat-value-2"><?php echo number_format($trx_pending, 0, ',', '.'); ?></p>                    
                            </div>                                
                          </div>
                        </div>
                        <div class="col-lg-4 border-lg-end border-bottom border-lg-0 pb-3 pb-lg-0">
                          <div class="d-flex flex-between-center mb-3">
                            <div class="d-flex align-items-center">
                              <div class="icon-item icon-item-sm bg-soft-success shadow-none me-2 bg-soft-success"><span class="fs--2 fa fa-check text-success"></span></div>
                              <h6 class="mb-0" id="stat-label-3">Transaksi Selesai</h6>
                            </div>                  
                          </div>
                          <div class="d-flex">
                            <div class="d-flex">
                              <p class="font-sans-serif lh-1 mb-1 fs-4 pe-2" id="stat-value-3"><?php echo number_format($trx_selesai, 0, ',', '.'); ?></p>                    
                            </div>                                
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>  
            </div>   
            <div class="row">
              <div class="col-lg-12">
                  <div class="row g-3 mb-3">
                    <div class="col-sm-4 col-md-4">
                      <div class="card overflow-hidden" style="min-width: 12rem">
                        <div class="bg-holder bg-card" style="background-image:url(../assets/img/icons/spot-illustrations/corner-1.png);">
                        </div>
                        <div class="card-body position-relative">
                          <h6>Visitors</h6>
                          <div class="display-4 fs-4 mb-2 fw-normal font-sans-serif text-warning"><?php echo number_format($total_visitors, 0, ',', '.'); ?></div>
                          <a class="fw-semi-bold fs--1 text-nowrap" href="#">See all visitors<span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
                        </div>
                      </div>
                    </div>
                    <div class="col-sm-4 col-md-4">
                      <div class="card overflow-hidden" style="min-width: 12rem">
                        <div class="bg-holder bg-card" style="background-image:url(../assets/img/icons/spot-illustrations/corner-2.png);">
                        </div>
                        <div class="card-body position-relative">
                          <h6>Users</h6>
                          <div class="display-4 fs-4 mb-2 fw-normal font-sans-serif text-info"><?php echo number_format($total_users, 0, ',', '.'); ?></div>
                          <a class="fw-semi-bold fs--1 text-nowrap" href="#">All Users<span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
                        </div>
                      </div>
                    </div>
                    <div class="col-sm-4 col-md-4">
                      <div class="card overflow-hidden" style="min-width: 12rem">
                        <div class="bg-holder bg-card" style="background-image:url(../assets/img/icons/spot-illustrations/corner-3.png);">
                        </div>
                        <div class="card-body position-relative">
                          <h6>Members</h6>
                          <div class="display-4 fs-4 mb-2 fw-normal font-sans-serif text-success"><?php echo number_format($total_members, 0, ',', '.'); ?></div>
                          <a class="fw-semi-bold fs--1 text-nowrap" href="#">All Members<span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
            </div>
            <?php  
             try {
                // 1. Data Grafik Donat: Total Transaksi per Produk
                $stmt_produk = $pdo->query("SELECT p.nama_paket AS name, (SELECT COUNT(*) FROM transaksis t WHERE t.produk_id = p.id AND t.status = 'LUNAS') AS value 
                                  FROM produks p  
                                  GROUP BY p.id");
                $data_produk = $stmt_produk->fetchAll(PDO::FETCH_ASSOC);

                // 2. Data Grafik Batang: 10 Dokumen Terpopuler (Berdasarkan Views)
                $stmt_populer = $pdo->query("SELECT judul, views 
                                            FROM `databases` 
                                            WHERE status = 1 
                                            ORDER BY views DESC 
                                            LIMIT 10");
                $data_populer = $stmt_populer->fetchAll(PDO::FETCH_ASSOC);
                
                // Siapkan array khusus untuk label sumbu X dan nilai Y di ECharts
                $judul_populer = [];
                $views_populer = [];
                foreach($data_populer as $row) {
                    // Potong judul jika terlalu panjang agar sumbu X tidak berantakan
                    $short_judul = strlen($row['judul']) > 20 ? substr($row['judul'], 0, 20) . '...' : $row['judul'];
                    $judul_populer[] = $short_judul;
                    $views_populer[] = $row['views'];
                }

            } catch (PDOException $e) {
                $data_produk = [];
                $judul_populer = [];
                $views_populer = [];
            }
            ?>
            <div class="row mb-3">
              <div class="col-md-4">
                <div class="card mb-3 h-100">
                  <div class="card-header">
                    <h5 class="fs-0 mb-0"><i class="fa fa-pie-chart text-primary"></i> Total Transaksi per Produk</h5>
                  </div>
                  <div class="card-body bg-light" style="padding:0">
                    <div id="trans_by_product_chart" style="width:100%;height:350px"></div>
                  </div>
                </div>
              </div>
              <div class="col-md-8" id="chart_kategori_container">
                <div class="card mb-3 h-100">
                  <div class="card-header">
                    <h5 class="fs-0 mb-0"><i class="fa fa-bar-chart text-info"></i> Dokumen Terpopuler</h5>
                  </div>
                  <div class="card-body bg-light" style="padding:0">
                    <div id="document_populer_chart" style="width:100%;height:350px"></div>
                  </div>
                </div>
              </div>
            </div>
            <?php  
            // 3. Data Grafik Garis: Tren Pengunjung Bulan Ini
              $bulan_ini = date('m');
              $tahun_ini = date('Y');
              $jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan_ini, $tahun_ini);

              $tgl_visitor = [];
              $total_visitor_per_hari = [];

              // Siapkan array dengan nilai 0 untuk tanggal 1 sampai akhir bulan
              for ($i = 1; $i <= $jumlah_hari; $i++) {
                  $tgl_format = sprintf("%02d", $i) . ' ' . date('M'); // Contoh: "01 Apr"
                  $tgl_visitor[] = $tgl_format;
                  $total_visitor_per_hari[$i] = 0; // Default 0
              }

              // Tarik data pengunjung unik (berdasarkan session) dari database
              $stmt_vis_daily = $pdo->prepare("
                  SELECT DAY(created_at) as hari, COUNT(DISTINCT session_id) as total 
                  FROM visitors 
                  WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? 
                  GROUP BY DAY(created_at)
              ");
              $stmt_vis_daily->execute([$bulan_ini, $tahun_ini]);
              $data_vis_daily = $stmt_vis_daily->fetchAll(PDO::FETCH_ASSOC);

              // Timpa nilai 0 dengan data asli jika di tanggal tersebut ada pengunjung
              foreach ($data_vis_daily as $row) {
                  $hari = (int)$row['hari'];
                  $total_visitor_per_hari[$hari] = (int)$row['total'];
              }

              // Re-index array value agar siap dilempar ke ECharts
              $data_visitor = array_values($total_visitor_per_hari);
            ?>
            <div class="row mb-3">
              <div class="col-12">
                <div class="card mb-3">
                  <div class="card-header">
                    <h5 class="fs-0 mb-0"><i class="fa fa-line-chart text-success"></i> Tren Pengunjung Bulan Ini (<?php echo date('F Y'); ?>)</h5>
                  </div>
                  <div class="card-body bg-light" style="padding:0">
                    <div id="visitor_chart" style="width:100%;height:400px"></div>
                  </div>
                </div>
              </div>
            </div>
            <script>
                const chartDataProduk = <?php echo json_encode($data_produk); ?>;
                const chartLabelPopuler = <?php echo json_encode($judul_populer); ?>;
                const chartDataPopuler = <?php echo json_encode($views_populer); ?>;
                
                // Tambahan Data Visitor
                const chartLabelVisitor = <?php echo json_encode($tgl_visitor); ?>;
                const chartDataVisitor = <?php echo json_encode($data_visitor); ?>;
            </script>
            <?php include_once("footer.php") ?>
          
        </div>
         
      </div>
    </main>
 
    <script src="../vendors/leaflet/leaflet.js"></script>
    <script src="../vendors/leaflet.markercluster/leaflet.markercluster.min.js"></script>
    <script src="../vendors/popper/popper.min.js"></script>
    <script src="../vendors/bootstrap/bootstrap.min.js"></script>
    <script src="../vendors/anchorjs/anchor.min.js"></script>
    <script src="../vendors/is/is.min.js"></script>
    <script src="../vendors/prism/prism.js"></script>
    <script src="../vendors/lodash/lodash.min.js"></script>
    <script src="../vendors/flatpickr/flatpickr.min.js"></script>
    <script src="../vendors/list.js/list.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script src="../vendors/echarts/echarts.min.js"></script>
    <script src="../vendors/jquery/jquery-3.7.0.min.js"></script>
    <script src="../vendors/datatables/datatables.min.js"></script>
    <script src="../assets/js/index.js"></script>

  </body>
</html>