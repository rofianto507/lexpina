<?php
session_start();
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://*.tile.openstreetmap.org;");
include("../config/configuration.php");
if(!isset($_SESSION["id"]) || !isset($_SESSION["nama"])) {
  header("Location: ../index");
  exit;
}
  $_SESSION["menu"]="dashboard";
	$nama=$_SESSION["nama"];
	$id=$_SESSION["id"];
	$username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
	$foto=$_SESSION["foto"];
  $last_login=$_SESSION["last_login"];
  $query_provinsi = $pdo->query("SELECT * FROM provinsis WHERE status=1 limit 1");
  $data_provinsi = $query_provinsi->fetch();
  $nama_provinsi = $data_provinsi["nama"];
  $kode_provinsi = $data_provinsi["kode"];
  $lat_provinsi = $data_provinsi["lat"];
  $lng_provinsi = $data_provinsi["lng"];
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | Peta Digital</title>
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
    <body
        data-lat-provinsi="<?php echo htmlspecialchars($lat_provinsi, ENT_QUOTES); ?>"
        data-lng-provinsi="<?php echo htmlspecialchars($lng_provinsi, ENT_QUOTES); ?>">
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
                        <h6 class="text-primary fs--1 mb-0">Selamat Datang, <?php echo $nama; ?></h6>
                        <h4 class="text-primary fw-bold mb-0">Peta Digital Kamtibmas<span class="text-info fw-medium"> Polda Sumsel</span></h4>
                      </div><img class="ms-n4 d-md-none d-lg-block" src="../assets/img/illustrations/crm-line-chart.png" alt="" width="150" />
                    </div>
                    <div class="col-md-auto p-3 d-none" id="card-stats">
                      <div class="row align-items-center">
                        <div class="col-lg-4 border-lg-end border-bottom border-lg-0 pb-3 pb-lg-0">
                          <div class="d-flex flex-between-center mb-3">
                            <div class="d-flex align-items-center">
                              <div class="icon-item icon-item-sm bg-soft-primary shadow-none me-2 bg-soft-primary"><span class="fs--2 fa fa-clock-o text-primary"></span></div>
                              <h6 class="mb-0" id="stat-label-1">Label 1</h6>
                            </div>                               
                          </div>
                          <div class="d-flex">
                            <div class="d-flex">
                              <p class="font-sans-serif lh-1 mb-1 fs-4 pe-2" id="stat-value-1">0</p>                    
                            </div>                                 
                          </div>
                        </div>
                        <div class="col-lg-4 border-lg-end border-bottom border-lg-0 pb-3 pb-lg-0">
                          <div class="d-flex flex-between-center mb-3">
                            <div class="d-flex align-items-center">
                              <div class="icon-item icon-item-sm bg-soft-success shadow-none me-2 bg-soft-success"><span class="fs--2 fa fa-check text-success"></span></div>
                              <h6 class="mb-0" id="stat-label-2">Label 2</h6>
                            </div>
                          </div>
                          <div class="d-flex">
                            <div class="d-flex">
                              <p class="font-sans-serif lh-1 mb-1 fs-4 pe-2" id="stat-value-2">0</p>                    
                            </div>                                 
                          </div>
                        </div>
                        <div class="col-lg-4 border-lg-end border-bottom border-lg-0 pb-3 pb-lg-0">
                          <div class="d-flex flex-between-center mb-3">
                            <div class="d-flex align-items-center">
                              <div class="icon-item icon-item-sm bg-soft-warning shadow-none me-2 bg-soft-warning"><span class="fs--2 fa fa-trophy text-warning"></span></div>
                              <h6 class="mb-0 pe-6" id="stat-label-3">Label 3</h6>
                            </div>                  
                          </div>
                          <div class="d-flex">
                            <div class="d-flex">
                              <p class="font-sans-serif lh-1 mb-1 fs-4 pe-2" id="stat-value-3">0</p>                    
                            </div>                                 
                          </div>
                        </div>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>
          <div class="card mb-3">
            <div class="card-header">
              <div class="row justify-content-between gx-0">
                <div class="col-auto">
                  <h5 class="fs-0 mb-0" id="map-title"><span class="fa fa-map me-2 fs-0"></span>Peta Digital Provinsi Sumsel</h5>
                </div>
                <div class="col-auto d-flex align-items-center gap-2">
                  <select class="form-select form-select-sm pe-4 me-2 d-none" id="subKategoriMapSelect" >
                    <option value="">- Semua Sub Kategori -</option>
                    <!-- Opsi akan diisi by JS+AJAX -->
                  </select>
                  <select class="form-select form-select-sm pe-4" id="mapTypeSelect">
                      <option value="umum" selected>Peta Umum</option>
                      <?php if($akses == "POLDA"): ?>
                      <option value="kriminalitas">Peta Kriminalitas</option>
                      <option value="kamtibmas">Peta Kasus Menonjol</option>
                      <option value="lalu-lintas">Peta Lalu Lintas</option>
                      <option value="bencana">Peta Bencana</option>
                       
                      <?php endif; ?>
                  </select> 
                  <select class="form-select form-select-sm pe-4 ms-2" id="mapYearSelect" >
                    <!-- Opsi tahun akan diisi otomatisff lewat JS -->
                  </select>  
                  <input 
                    class="form-control form-control-sm ms-2" 
                    id="mapDateRange" 
                    type="text" 
                    placeholder="Pilih Rentang Tanggal"   
                  />
                  <div class="dropdown ms-2" id="filterLokasiDropdown">
                    <button 
                      class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                      type="button" 
                      id="filterLokasiBtn"
                      data-bs-toggle="dropdown" 
                      data-bs-auto-close="outside"
                      aria-expanded="false" 
                    >
                      <span class="fa fa-map-marker me-1"></span>
                      <span id="filterLokasiLabel">Semua Lokasi</span>
                    </button>
                    <ul class="dropdown-menu shadow" aria-labelledby="filterLokasiBtn">
                       
                      
                    </ul>
                  </div>
                                
                </div>
              </div>
            </div>
            <div class="card-body bg-light" id="map-container">             
              <div id="map2">
                <div id="sumberOverlay" class="sumber-overlay"> 
                </div>
                <div id="map-legend" class="d-none"></div>
                <div id="map-filter-overlay" class="d-none">
                  <b>Pilih Filter</b>
                  <div id="filter-options-list"></div>
                </div>
              </div>
            </div>
            
          </div>
          <div class="row">
            <div class="col-md-6 d-none" id="chart_kabupaten_container">
              <div class="card mb-3">
                <div class="card-header">
                  <h5 class="fs-0 mb-0" id="chart-title"></span>Grafik Total Konflik per Kabupaten</h5>
                </div>
                <div class="card-body bg-light p-0">
                  <div id="conflict_chart"></div>
                </div>
              </div>
            </div>
            <div class="col-md-6 d-none" id="chart_kategori_container">
              <div class="card mb-3">
                <div class="card-header">
                  <h5 class="fs-0 mb-0" id="chart-kat-title">Grafik Konflik Berdasarkan Kategori (Semua Wilayah)</h5>
                </div>
                <div class="card-body bg-light p-0">
                  <div id="conflict_chart_kat"></div>
                </div>
              </div>
            </div>
          </div>
          <div class="row d-none" id="cardSubKategoriKriminal" >           
            <div class="col-md-6" >
              <div class="card mb-3" >
                <div class="card-header">
                  <h5 class="fs-0 mb-0"><span class="fa fa-map-pin me-2 fs-0"></span>Lokasi Kejahatan Terbanyak</h5>
                </div>
                <div class="card-body bg-light p-0">
                  <div id="lokasi_kejahatan_chart"></div>
                </div>
              </div>
            </div>
            <div class="col-md-6" >
              <div class="card mb-3" >
                <div class="card-header">
                  <h5 class="fs-0 mb-0"><span class="fa fa-tags me-2 fs-0"></span>Sub Kategori Kejahatan Terbanyak</h5>
                </div>
                <div class="card-body bg-light p-0">
                    <div id="sub_kategori_chart"></div>
                </div>
              </div>
            </div>
          </div>
          <div class="row d-none" id="cardWaktuKriminal">
            <div class="col-md-7" >
              <div class="card mb-3" >
                <div class="card-header">
                  <h5 class="fs-0 mb-0"><span class="fa fa-line-chart me-2 fs-0"></span>Trend Kriminalitas</h5>
                </div>
                <div class="card-body bg-light p-0">
                  <div id="trend_kriminalitas"></div>
                </div>
              </div>
            </div>
            <div class="col-md-5" >
              <div class="card mb-3" >
                <div class="card-header">
                  <h5 class="fs-0 mb-0"><span class="fa fa-clock-o me-2 fs-0"></span>Statistik Waktu Kriminalitas</h5>
                </div>
                <div class="card-body bg-light p-0">
                  <div id="waktu_kejahatan_chart"></div>
                </div>
              </div>
            </div>
          </div>
          <div class="row d-none" id="cardWaktuKriminal3c">
            <div class="col-md-4" >
              <div class="card mb-3" >
                <div class="card-header">
                  <h5 class="fs-0 mb-0"><span class="fa fa-line-chart me-2 fs-0"></span>Waktu Kejadian Curat</h5>
                </div>
                <div class="card-body bg-light p-0">
                  <div id="waktu_kejadian_curat"></div>
                </div>
              </div>
            </div>
            <div class="col-md-4" >
              <div class="card mb-3" >
                <div class="card-header">
                  <h5 class="fs-0 mb-0"><span class="fa fa-clock-o me-2 fs-0"></span>Waktu Kejadian Curas</h5>
                </div>
                <div class="card-body bg-light p-0">
                  <div id="waktu_kejadian_curas"></div>
                </div>
              </div>
            </div>
            <div class="col-md-4" >
              <div class="card mb-3" >
                <div class="card-header">
                  <h5 class="fs-0 mb-0"><span class="fa fa-clock-o me-2 fs-0"></span>Waktu Kejadian Curanmor</h5>
                </div>
                <div class="card-body bg-light p-0">
                  <div id="waktu_kejadian_curanmor"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Container Table -->
          <div class="card mb-3 d-none" id="cardDataWilayah">
            <div class="card-header">
              <h5 class="fs-0 mb-0"><span class="fa fa-table me-2 fs-0"></span>Data <span id="cardTitleWilayah"></span></h5>
            </div>
            <div class="card-body">
              <div class="table-responsive">           
                <table id="tableKamtibmasMenonjol" class="table table-striped table-bordered table-sm d-none"></table>
                <table id="tableLalin" class="table table-striped table-bordered table-sm d-none"></table>               
                <table id="tableKriminalitas" class="table table-striped table-bordered table-sm d-none"></table>
                <table id="tableBencana" class="table table-striped table-bordered table-sm d-none"></table>
              </div>
            </div>
          </div>
           <!-- Modal Detail Bencana -->
            <div class="modal fade" id="modalDetailBencana" tabindex="-1">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Detail Data Bencana</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body" id="modalDetailBodyBencana">
                    <!-- detail data tampil di sini -->
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                  </div>
                </div>
              </div>
            </div>
          <!-- Modal Detail Kriminalitas -->
            <div class="modal fade" id="modalDetailKriminalitas" tabindex="-1">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Detail Data Kriminalitas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body" id="modalDetailBodyKriminalitas">
                    <!-- detail data tampil di sini -->
                  </div>
                  
                </div>
              </div>
            </div>
             
             <!-- Modal Detail Kamtibmas -->
            <div class="modal fade" id="modalDetailKamtibmasMenonjol" tabindex="-1">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Detail Data Kasus Menonjol</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body" id="modalDetailBodyKamtibmasMenonjol">
                    <!-- detail data tampil di sini -->
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                  </div>
                </div>
              </div>
            </div>
            <!-- Modal Detail Lalu Lintas -->
            <div class="modal fade" id="modalDetailLalin" tabindex="-1">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Detail Data Lalu Lintas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body" id="modalDetailBodyLalin">
                    <!-- detail data tampil di sini -->
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                  </div>
                </div>
              </div>
            </div>
            
           <?php include_once("footer.php") ?>
          
        </div>
         
      </div>
    </main>
 
    <!-- ===============================================-->
    <!--    JavaScripts-->
    <!-- ===============================================-->
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
