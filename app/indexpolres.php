<?php
session_start();
include("../config/configuration.php");
if($_SESSION["nama"]!="" && $_SESSION["id"]!=""){
  $_SESSION["menu"]="dashboard";
	$nama=$_SESSION["nama"];
	$id=$_SESSION["id"];
	$username=$_SESSION["username"];
  $akses=$_SESSION["akses"];
	$foto=$_SESSION["foto"];
  $last_login=$_SESSION["last_login"];
  $polsek_id = $_SESSION["polsek_id"]??0;
  $polres_id = $_SESSION["polres_id"]??0;
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
    <title>Dashboard Polres | Peta Digital</title>
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


  <body
        data-lat-provinsi="<?php echo htmlspecialchars($lat_provinsi, ENT_QUOTES); ?>"
        data-lng-provinsi="<?php echo htmlspecialchars($lng_provinsi, ENT_QUOTES); ?>">

    <!-- ===============================================-->
    <!--    Main Content-->
    <!-- ===============================================-->
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
                              <h6 class="mb-0" id="stat-label-3">Label 3</h6>
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
                  <select class="form-select form-select-sm pe-4" id="mapTypeSelect">
                      <option value="umum" selected>Peta Umum</option>                     
                      <option value="kriminalitas">Peta Kriminalitas</option>
                     
                    
                  </select> 
                  <select class="form-select form-select-sm pe-4 ms-2" id="mapYearSelect">
                    <!-- Opsi tahun akan diisi otomatis lewat JS -->
                  </select>                
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
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                  </div>
                </div>
              </div>
            </div>
            <!-- Modal Detail Kamtibmas -->
            <div class="modal fade" id="modalDetailKamtibmas" tabindex="-1">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Detail Data Kamtibmas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body" id="modalDetailBodyKamtibmas">
                    <!-- detail data tampil di sini -->
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
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
            <!-- Modal Detail Lokasi -->
            <div class="modal fade" id="modalDetailLokasi" tabindex="-1">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Detail Data Lokasi Penting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body" id="modalDetailBodyLokasi">
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
 
 
<script>
  let currentMapType = 'umum';     
  let currentMapYear = new Date().getFullYear();
  let lastCheckedKategoriIds = [];
  var latProvinsi = parseFloat($('body').data('lat-provinsi'));
  var lngProvinsi = parseFloat($('body').data('lng-provinsi'));
  function renderMapYearDropdown(min, max) {
    const yearSelect = document.getElementById('mapYearSelect');
    yearSelect.innerHTML = '';
    for(let y = max; y >= min; y--){
      const opt = document.createElement('option');
      opt.value = y;
      opt.textContent = y;
      yearSelect.appendChild(opt);
    }
    // Set default (tahun sekarang)
    yearSelect.value = new Date().getFullYear();
  }
  renderMapYearDropdown(2020, new Date().getFullYear()); // misal dari tahun 2020
  function hideAllDatatableWrappers() {
    // Hide semua wrapper datatables
    document.querySelectorAll('.dataTables_wrapper').forEach(function(wrapper){
      wrapper.classList.add('d-none');
    });
  }
  function showTableWilayah(type, level, wilayah_id) {
    // First, hide all datatable wrappers
    hideAllDatatableWrappers();
    // Hide semua table
    const tableKamtibmasMenonjol = document.getElementById('tableKamtibmasMenonjol');
    tableKamtibmasMenonjol.classList.add('d-none');
    const tableLalin = document.getElementById('tableLalin');
    tableLalin.classList.add('d-none');
    const tableKriminalitas = document.getElementById('tableKriminalitas');
    tableKriminalitas.classList.add('d-none');
    const tableBencana = document.getElementById('tableBencana');
    tableBencana.classList.add('d-none');

    // Set card title
    document.getElementById('cardTitleWilayah').innerText = type.charAt(0).toUpperCase() + type.slice(1);

    // Show card wrapper
    const cardDataWilayah = document.getElementById('cardDataWilayah');
    cardDataWilayah.classList.remove('d-none');

    // Switch fetch data
    switch(type) {
      case 'kamtibmas': 
        showTableKamtibmasMenonjol(level, wilayah_id,lastCheckedKategoriIds,currentMapYear);
        setTimeout(function(){
          var wrappermenonjol = document.getElementById('tableKamtibmasMenonjol').closest('.dataTables_wrapper');
          if(wrappermenonjol) wrappermenonjol.classList.remove('d-none');
        }, 200);
        break;
      case 'lalin':
        showTableLalin(level, wilayah_id,lastCheckedKategoriIds,currentMapYear);
          setTimeout(function(){
            var wrapper = document.getElementById('tableLalin').closest('.dataTables_wrapper');
            if(wrapper) wrapper.classList.remove('d-none');
          }, 200);
        break;
      case 'kriminalitas':
        showTableKriminalitas(level, wilayah_id,lastCheckedKategoriIds,currentMapYear);
          setTimeout(function(){
            var wrapper = document.getElementById('tableKriminalitas').closest('.dataTables_wrapper');
            if(wrapper) wrapper.classList.remove('d-none');
          }, 200);
         break;  
       case 'bencana':
        showTableBencana(level, wilayah_id,lastCheckedKategoriIds,currentMapYear);
          setTimeout(function(){
            var wrapper = document.getElementById('tableBencana').closest('.dataTables_wrapper');
            if(wrapper) wrapper.classList.remove('d-none');
          }, 200);
         break;
    }
  }
  //tabel bencana
  function showTableBencana(level, wilayah_id, filterKategoriIds = [], tahun = new Date().getFullYear()) {
    const tableBencana = document.getElementById('tableBencana');
    tableBencana.classList.remove('d-none');
    let params = [`type=bencana`, `level=${level}`, `id=${wilayah_id}`];
    if(filterKategoriIds && filterKategoriIds.length) params.push('kategori=' + filterKategoriIds.join(','));
    if(tahun) params.push('tahun=' + tahun);
    let url = 'data_wilayah.php?' + params.join('&');
    console.log("Fetching bencana with URL:", url);
    fetch(url)
      .then(res => res.json())
      .then(function(data) {
        console.log("data bencana :", data);

        // CEK dan destroy DataTable bila sudah pernah diinisialisasi
        if ($.fn.DataTable.isDataTable('#tableBencana')) {
          $('#tableBencana').DataTable().clear().destroy();
        }

        // Render datatable
         $('#tableBencana').DataTable({
          data: data,
          columns: [
            {title: "ID", data: "id"},
            {title: "Kategori", data: "kategori_nama"},
            {title: "Kabupaten", data: "kabupaten_nama"},
            {title: "Sumber Dokumen", data: "sumber_dokumen_nama"},
            {title: "Tahun", data: "sumber_tahun"},
             

            {
              title: "Aksi", data: null, orderable: false, searchable: false,
              render: function(data, type, row, meta) {
                return `<button type="button" class="btn btn-sm btn-primary btn-detail-bencana" 
                  data-id="${row.id}">Detail</button>`;
              }
            } 
          ],
          responsive: true,
          autoWidth: false
        });
        
      });
  }
  $(document).on('click', '.btn-detail-bencana', function() {
    var id = $(this).data('id');
    var table = $('#tableBencana').DataTable();
    var rowData = table.row($(this).parents('tr')).data();
    var html = `
       
        <table class="table table-bordered">
          <tr><th>ID</th><td>${rowData.id}</td></tr>
          <tr><th>Kategori</th><td>${rowData.kategori_nama}</td></tr>
          <tr><th>Kabupaten</th><td>${rowData.kabupaten_nama}</td></tr>
          <tr><th>Kecamatan</th><td>${rowData.kecamatan_nama}</td></tr>
          <tr><th>Desa</th><td>${rowData.desa_nama}</td></tr>
          <tr><th>Sumber Dokumen</th><td>${rowData.sumber_dokumen_nama}</td></tr>
          <tr><th>Tahun</th><td>${rowData.sumber_tahun}</td></tr>
          <tr><th>Penyebab</th><td>${rowData.penyebab}</td></tr>
          <tr><th>Tindak Lanjut</th><td>${rowData.tindaklanjut}</td></tr>
           <tr><th>Foto</th><td>
            ${rowData.foto ? `<img class="img-fluid" src="../public/upload/bencana/${rowData.foto}" alt="foto">` : '-'}
          </td></tr>
      </div>
    `;

    $('#modalDetailBodyBencana').html(html);
    $('#modalDetailBencana').modal('show');
  });
  
  //tabel kriminalitas
  function showTableKriminalitas(level, wilayah_id, filterKategoriIds = [], tahun = new Date().getFullYear()) {
    const tableKriminalitas = document.getElementById('tableKriminalitas');
    tableKriminalitas.classList.remove('d-none');
    let params = [`type=kriminalitas`, `level=${level}`, `id=${wilayah_id}`];
    if(filterKategoriIds && filterKategoriIds.length) params.push('kategori=' + filterKategoriIds.join(','));
    if(tahun) params.push('tahun=' + tahun);
    let url = 'data_wilayah2.php?' + params.join('&');
    fetch(url)
      .then(res => res.json())
      .then(function(data) {
        console.log("data kriminalitas :", data);

        // CEK dan destroy DataTable bila sudah pernah diinisialisasi
        if ($.fn.DataTable.isDataTable('#tableKriminalitas')) {
          $('#tableKriminalitas').DataTable().clear().destroy();
        }

        // Render datatable
         $('#tableKriminalitas').DataTable({
          data: data,
          columns: [
            {title: "ID", data: "id"},
            {title: "Kategori", data: "kategori_nama"},
            {title: "Sub Kategori", data: "sub_kategori_nama"},
            {title: "Polres", data: "polres_nama"},
            {title: "Kabupaten", data: "kabupaten_nama"},
            {title: "Sumber Dokumen", data: "sumber_nama"},
            {title: "Tahun", data: "sumber_tahun"},
            {title: "Jumlah", data: "poin"},
            {title: "Waktu Kejadian", data: "tanggal", render: function(data) {
              return data ? new Date(data).toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'}) + ' ' + new Date(data).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit', hour12:false}) + ' WIB' : '-';
            }},
            {title: "State", data: "state", render: function(data) {
              var badgeClass = (data === 'SELESAI') ? 'bg-success' : 'bg-warning';
              return '<span class="badge ' + badgeClass + '">' + (data || '-') + '</span>';
              }
            },

            {
              title: "Aksi", data: null, orderable: false, searchable: false,
              render: function(data, type, row, meta) {
                return `<button type="button" class="btn btn-sm btn-primary btn-detail-kriminalitas" 
                  data-id="${row.id}">Detail</button>`;
              }
            } 
          ],
          responsive: true,
          autoWidth: false
        });
        
      });
  }
  $(document).on('click', '.btn-detail-kriminalitas', function() {
    var id = $(this).data('id');
    var table = $('#tableKriminalitas').DataTable();
    var rowData = table.row($(this).parents('tr')).data();
    var html = `
       
        <table class="table table-bordered">
          <tr><th>ID</th><td>${rowData.id}</td></tr>
          <tr><th>Kategori</th><td>${rowData.kategori_nama}</td></tr>
          <tr><th>Sub Kategori</th><td>${rowData.sub_kategori_nama}</td></tr>
          <tr><th>Polres</th><td>${rowData.polres_nama}</td></tr>
          <tr><th>Kabupaten</th><td>${rowData.kabupaten_nama}</td></tr>
          <tr><th>Sumber Dokumen</th><td>${rowData.sumber_nama}</td></tr>
          <tr><th>Tahun</th><td>${rowData.sumber_tahun}</td></tr>
          <tr><th>Jumlah</th><td>${rowData.poin}</td></tr>
          <tr><th>Lokasi</th><td>${rowData.lokasi}</td></tr>
          <tr><th>Waktu Kejadian</th><td>${rowData.tanggal ? new Date(rowData.tanggal).toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'}) + ' ' + new Date(rowData.tanggal).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit', hour12:false}) + ' WIB' : '-'}</td></tr>
          <tr><th>Penanggung Jawab</th><td>${rowData.penanggungjawab}</td></tr>
          <tr><th>No LP</th><td>${rowData.no_lp}</td></tr>
          <tr><th>Penyebab</th><td>${rowData.penyebab}</td></tr>
          <tr><th>Keterangan</th><td>${rowData.keterangan}</td></tr>
          <tr><th>State</th><td><span class="badge ${rowData.state === 'SELESAI' ? 'bg-success' : 'bg-warning'}">${rowData.state || '-'}</span></td></tr>

      </div>
    `;

    $('#modalDetailBodyKriminalitas').html(html);
    $('#modalDetailKriminalitas').modal('show');
  });
  
  
  //kamtibmasmeononjol
  function showTableKamtibmasMenonjol(level, wilayah_id, filterKategoriIds = [], tahun = new Date().getFullYear()) {
    const tableKamtibmasMenonjol = document.getElementById('tableKamtibmasMenonjol');
    tableKamtibmasMenonjol.classList.remove('d-none');
    let params = [`type=kamtibmasmenonjol`, `level=${level}`, `id=${wilayah_id}`];
    if (filterKategoriIds && filterKategoriIds.length) params.push('kategori=' + filterKategoriIds.join(','));
    if (tahun) params.push('tahun=' + tahun);
    let url = 'data_wilayah.php?' + params.join('&');
    fetch(url)
      .then(res => res.json())
      .then(function(data) {

        // CEK dan destroy DataTable bila sudah pernah diinisialisasi
        if ($.fn.DataTable.isDataTable('#tableKamtibmasMenonjol')) {
          $('#tableKamtibmasMenonjol').DataTable().clear().destroy();
        }

        // Render datatable
        $('#tableKamtibmasMenonjol').DataTable({
          data: data,
          columns: [
            {title: "ID", data: "id"},
            {title: "Kategori", data: "kategori_nama"},
            {title: "Tanggal", data: "tanggal"},
            {
              title: "Permasalahan",
              data: "permasalahan",
              render: function(data, type, row, meta) {
                if (typeof data === 'string' && data.length > 100) {
                  return data.substring(0, 100) + '...';
                }
                return data;
              }
            },
            {title: "Polres", data: "polres_nama"},
            {title: "Sumber Dokumen", data: "sumber_dokumen_nama"},
            {title: "Tahun", data: "sumber_tahun"},

            {title:"State", data:"state", render: function(data) {
              var badgeClass = (data === 'SELESAI') ? 'bg-success' : 'bg-warning';
              return '<span class="badge ' + badgeClass + '">' + (data || '-') + '</span>';
            }
          },
          {
            title: "Aksi", data: null, orderable: false, searchable: false,
            render: function(data, type, row, meta) {
              return `<button type="button" class="btn btn-sm btn-primary btn-detail-kamtibmasmenonjol" 
                data-id="${row.id}">Detail</button>`;
              }
            } 
          ],
          responsive: true,
          autoWidth: false
        });
         
      });
  }
  $(document).on('click', '.btn-detail-kamtibmasmenonjol', function() {
    var id = $(this).data('id');
    var table = $('#tableKamtibmasMenonjol').DataTable();
    var rowData = table.row($(this).parents('tr')).data();
    var html = `
      
        <table class="table table-bordered">
          <tr><th>ID</th><td>${rowData.id}</td></tr>
          <tr><th>Kategori</th><td>${rowData.kategori_nama}</td></tr>
          <tr><th>Desa</th><td>${rowData.desa_nama}</td></tr>
          <tr><th>Polres</th><td>${rowData.polres_nama}</td></tr>
          <tr><th>Polsek</th><td>${rowData.polsek_nama}</td></tr>
          <tr><th>Nomor LP</th><td>${rowData.nomor_lp}</td></tr>
          <tr><th>Sumber Dokumen</th><td>${rowData.sumber_dokumen_nama}</td></tr>
          <tr><th>Tahun</th><td>${rowData.sumber_tahun}</td></tr>
          <tr><th>Tersangka</th><td>${rowData.tersangka}</td></tr>
          <tr><th>Permasalahan</th><td>${rowData.permasalahan}</td></tr>
          <tr><th>Penganganan</th><td>${rowData.penanganan}</td></tr>
          <tr><th>Tindak Lanjut</th><td>${rowData.tindak_lanjut}</td></tr>
          <tr><th>Modus</th><td>${rowData.modus_nama}</td></tr>
          <tr><th>Jenis TKP</th><td>${rowData.jenis_tkp_nama}</td></tr>
          <tr><th>Tanggal</th><td>${rowData.tanggal}</td></tr>
          <tr><th>Kasus Menonjol</th><td>${rowData.is_menonjol ? 'Ya' : 'Tidak'}</td></tr>
        </table>
    
    `;

    $('#modalDetailBodyKamtibmasMenonjol').html(html);
    $('#modalDetailKamtibmasMenonjol').modal('show');
  });
  //lalin
  function showTableLalin(level, wilayah_id, filterKategoriIds = [], tahun = new Date().getFullYear()) {
    const tableLalin = document.getElementById('tableLalin');
    tableLalin.classList.remove('d-none');
    let params = [`type=lalin`, `level=${level}`, `id=${wilayah_id}`];
    if(filterKategoriIds && filterKategoriIds.length) params.push('kategori=' + filterKategoriIds.join(','));
    if(tahun) params.push('tahun=' + tahun);
    let url = 'data_wilayah.php?' + params.join('&');
    fetch(url)
      .then(res => res.json())
      .then(function(data) {

        // CEK dan destroy DataTable bila sudah pernah diinisialisasi
        if ($.fn.DataTable.isDataTable('#tableLalin')) {
          $('#tableLalin').DataTable().clear().destroy();
        }

        // Render datatable
         $('#tableLalin').DataTable({
          data: data,
          columns: [
            {title: "ID", data: "id"},
            {title: "Kategori", data: "kategori_nama"},
            {title: "Nama", data: "nama"},
            {
              title: "Keterangan",
              data: "keterangan",
              render: function(data, type, row, meta) {
                if (typeof data === 'string' && data.length > 100) {
                  return data.substring(0, 100) + '...';
                }
                return data;
              }
            },
            {title: "Sumber Dokumen", data: "sumber_dokumen_nama"},
             {title: "Tahun", data: "sumber_tahun"},

            {title:"State", data:"state", render: function(data) {
                var badgeClass = (data === 'SELESAI') ? 'bg-success' : 'bg-warning';
                return '<span class="badge ' + badgeClass + '">' + (data || '-') + '</span>';
              }
            },
            {
              title: "Aksi", data: null, orderable: false, searchable: false,
              render: function(data, type, row, meta) {
                return `<button type="button" class="btn btn-sm btn-primary btn-detail-lalin" 
                  data-id="${row.id}">Detail</button>`;
              }
            } 
          ],
          responsive: true,
          autoWidth: false
        });
        
      });
  }
  $(document).on('click', '.btn-detail-lalin', function() {
    var id = $(this).data('id');
    var table = $('#tableLalin').DataTable();
    var rowData = table.row($(this).parents('tr')).data();
    var html = `
       
        <table class="table table-bordered">
          <tr><th>ID</th><td>${rowData.id}</td></tr>
          <tr><th>Kategori</th><td>${rowData.kategori_nama}</td></tr>
          <tr><th>Desa</th><td>${rowData.desa_nama}</td></tr>
          <tr><th>Kecamatan</th><td>${rowData.kecamatan_nama}</td></tr>
          <tr><th>Kabupaten</th><td>${rowData.kabupaten_nama}</td></tr>
          <tr><th>Sumber Dokumen</th><td>${rowData.sumber_dokumen_nama}</td></tr>
          <tr><th>Tahun</th><td>${rowData.sumber_tahun}</td></tr>
          <tr><th>Nama</th><td>${rowData.nama}</td></tr>
          <tr><th>Keterangan</th><td>${rowData.keterangan}</td></tr>
          <tr><th>Penanggung Jawab</th><td>${rowData.penanggungjawab}</td></tr>
          <tr><th>Penyebab</th><td>${rowData.penyebab}</td></tr>
          <tr><th>Tindak Lanjut</th><td>${rowData.tindak_lanjut}</td></tr>
          <tr><th>Foto</th><td>
            ${rowData.foto ? `<img src="../public/upload/lalin/${rowData.foto}" alt="foto" class="img-fluid">` : '-'}
          </td></tr>
        </table>
     
    `;

    $('#modalDetailBodyLalin').html(html);
    $('#modalDetailLalin').modal('show');
  });
 
  function getKonflikLegendHtml() {
  return `
    <h6 class="mb-2">Legenda Konflik</h6>
    <div>
      <i class="legend-icon bg-deep-red"></i> &gt; 30 konflik<br>
      <i class="legend-icon bg-red-dark"></i> &gt; 20 konflik<br>
      <i class="legend-icon bg-red-medium"></i> &gt; 10 konflik<br>
      <i class="legend-icon bg-orange"></i> &gt; 5 konflik<br>
      <i class="legend-icon bg-yellow"></i> &gt;= 1 konflik<br>
      <i class="legend-icon bg-green"></i> Tidak ada konflik
    </div>
  `;
}

function getKamtibmasLegendHtml() {
  return `
    <h6 class="mb-2">Legenda Kasus Menonjol</h6>
    <div>
      <i class="legend-icon bg-red-dark"></i> &gt; 20 kasus<br>
      <i class="legend-icon bg-red-medium"></i> &gt; 10 kasus<br>
      <i class="legend-icon bg-orange"></i> &gt; 5 kasus<br>
      <i class="legend-icon bg-yellow"></i> &gt;= 1 kasus<br>
      <i class="legend-icon bg-green"></i> Tidak ada kasus
    </div>
  `;
}

function getLalinLegendHtml() {
  return `
    <h6 class="mb-2">Legenda Lalu Lintas</h6>
    <div>
      <i class="legend-icon bg-red-dark"></i> &gt; 20 kejadian<br>
      <i class="legend-icon bg-red-medium"></i> &gt; 10 kejadian<br>
      <i class="legend-icon bg-orange"></i> &gt; 5 kejadian<br>
      <i class="legend-icon bg-yellow"></i> &gt;= 1 kejadian<br>
      <i class="legend-icon bg-green"></i> Tidak ada kejadian
    </div>
  `;
}

function getKriminalitasLegendHtml() {
  return `
    <h6 class="mb-2">Legenda Kriminalitas</h6>
    <div>
      <i class="legend-icon bg-red-dark"></i> &gt; 2.000 Kasus<br>
      <i class="legend-icon bg-red-medium"></i> &gt; 1.000 Kasus<br>
      <i class="legend-icon bg-orange"></i> &gt; 500 Kasus<br>
      <i class="legend-icon bg-yellow"></i> &gt;= 1 Kasus<br>
      <i class="legend-icon bg-green"></i> Tidak Ada Kasus
    </div>
  `;
}

function getBencanaLegendHtml() {
  return `
    <h6 class="mb-2">Legenda Bencana</h6>
    <div>
      <i class="legend-icon bg-red-dark"></i> &gt; 20 Bencana<br>
      <i class="legend-icon bg-red-medium"></i> &gt; 10 Bencana<br>
      <i class="legend-icon bg-orange"></i> &gt; 5 Bencana<br>
      <i class="legend-icon bg-yellow"></i> &gt;= 1 Bencana<br>
      <i class="legend-icon bg-green"></i> Tidak Ada Bencana
    </div>
  `;
}
  function renderMapFilterByType(tipe) {
  const filterDiv = document.getElementById('map-filter-overlay');
  const filterList = document.getElementById('filter-options-list');

  // Sembunyikan dulu
  filterDiv.classList.add('d-none');
  filterList.innerHTML = '';

  let endpoint = null;
  if (tipe === 'kriminalitas') endpoint = 'get_kriminal_kategori.php';
  else if (tipe === 'bencana') endpoint = 'get_bencana_kategori.php';
  else if (tipe === 'kamtibmas') endpoint = 'get_kamtibmas_kategori.php';
  else if (tipe === 'lalu-lintas') endpoint = 'get_lalin_kategori.php';

  if(endpoint){
    filterDiv.classList.remove('d-none');
    // LOAD dari endpoint
    fetch(endpoint)
      .then(res => res.json())
      .then(data => {
        // data = [ { id, nama }, ... ]
        renderMapFilterOptions(
          data.map(item => ({
            value: item.id,
            label: item.nama,
            checked: true // atur default jika mau
          }))
        );
      })
      .catch(err => {
        filterList.innerHTML = '<span class="text-danger">Gagal load filter</span>';
        console.error('load kategori error:', err);
      });
  }
}
function renderMapFilterOptions(options) {
  const html = options.map(opt =>
    `<label><input type="checkbox" value="${opt.value}" ${opt.checked ? 'checked' : ''}> ${opt.label}</label>`
  ).join('');
  document.getElementById('filter-options-list').innerHTML = html;
}
  const chart_kategori_container = document.getElementById('chart_kategori_container');
  const chart_kabupaten_container = document.getElementById('chart_kabupaten_container');
  const cardSubKategoriKriminal = document.getElementById('cardSubKategoriKriminal');
  var lat = latProvinsi;
  var lng = lngProvinsi;
	var lokasi_provinsi = [lat, lng];
  var map2 = L.map('map2').setView(lokasi_provinsi, 8);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map2);
  showDefaultMap();
  
 
  document.getElementById('mapTypeSelect').addEventListener('change', function(){
    const tipe = this.value;
    currentMapType = tipe;
    const legendDiv = document.getElementById('map-legend');  
    if(legendDiv) {
      if(tipe === 'konflik') legendDiv.innerHTML = getKonflikLegendHtml();
      else if(tipe === 'kamtibmas') legendDiv.innerHTML = getKamtibmasLegendHtml();
      else if(tipe === 'lalu-lintas') legendDiv.innerHTML = getLalinLegendHtml();
      else if(tipe === 'kriminalitas') legendDiv.innerHTML = getKriminalitasLegendHtml();
      else if(tipe === 'bencana') legendDiv.innerHTML = getBencanaLegendHtml();
      else legendDiv.innerHTML = '';
    }
    renderMapFilterByType(tipe);
    const cardStat = document.getElementById('card-stats'); 
    
    const mapTitle = document.getElementById('map-title');
    if (mapTitle) {
      mapTitle.innerHTML =
        tipe === 'kamtibmas'
          ? '<span class="fa fa-bookmark me-2 fs-0"></span>Peta Kasus Menonjol'
          : tipe === 'lalu-lintas'
          ? '<span class="fa fa-road me-2 fs-0"></span>Peta Lalu Lintas'
          : tipe === 'bencana'
          ? '<span class="fa fa-flash me-2 fs-0"></span>Peta Bencana'
          : tipe === 'kriminalitas'
          ? '<span class="fa fa-user-secret me-2 fs-0"></span>Peta Kriminalitas'
          : '<span class="fa fa-map me-2 fs-0"></span>Peta Dinamis Lainnya';
    }

    // Ubah title card chart konflik wilayah
    const chartTitle = document.getElementById('chart-title');
    if (chartTitle) {
      chartTitle.innerText =
         tipe === 'kamtibmas'
          ? 'Grafik Indeks Kamtibmas per Kabupaten'
          : tipe === 'lalu-lintas'
          ? 'Grafik Lalu Lintas per Kabupaten'
          : 'Grafik Wilayah Lainnya';
    }

    // Ubah title card chart kategori
    const chartKatTitle = document.getElementById('chart-kat-title');
    if (chartKatTitle) {
      chartKatTitle.innerText =
        tipe === 'konflik'
          ? 'Grafik Donat Konflik Berdasarkan Kategori'
          : tipe === 'kamtibmas'
          ? 'Grafik Donat Indeks Kamtibmas Berdasarkan Kategori'
          : tipe === 'lalu-lintas'
          ? 'Grafik Donat Lalu Lintas Berdasarkan Kategori'
          : 'Grafik Kategori Lainnya';
    }

    if (tipe === 'umum') {
      showDefaultMap();
      chart_kabupaten_container.className = 'col-md-6';
      chart_kabupaten_container.classList.add('d-none');
      chart_kategori_container.classList.add('d-none')
      cardStat.classList.add('d-none');
      cardSubKategoriKriminal.classList.add('d-none');
    }else if (tipe === 'kamtibmas') {
      showKamtibmasMap(lastCheckedKategoriIds,currentMapYear);
      loadKamtibmasBarChart('kabupaten',0, lastCheckedKategoriIds, currentMapYear);
      loadKamtibmasDonutChart('provinsi',0, lastCheckedKategoriIds, currentMapYear);
      chart_kabupaten_container.className = 'col-md-6';
      chart_kabupaten_container.classList.remove('d-none');
      chart_kategori_container.classList.remove('d-none');
      cardStat.classList.add('d-none');
      cardSubKategoriKriminal.classList.add('d-none');
    }else if (tipe === 'lalu-lintas') {
      showLalinMap(lastCheckedKategoriIds, currentMapYear)
      loadLalinBarChart('kabupaten', 0, lastCheckedKategoriIds, currentMapYear);
      loadLalinDonutChart('kabupaten', 0, lastCheckedKategoriIds, currentMapYear);
      chart_kabupaten_container.className = 'col-md-6';
      chart_kabupaten_container.classList.remove('d-none');
      chart_kategori_container.classList.remove('d-none');
      cardStat.classList.add('d-none');
      cardSubKategoriKriminal.classList.add('d-none');
    }else if (tipe === 'kriminalitas') {
      showKriminalitasMap(lastCheckedKategoriIds,currentMapYear);
      loadKriminalitasBarChart('kabupaten',0, lastCheckedKategoriIds, currentMapYear);
      loadKriminalitasDonutChart('provinsi',0, lastCheckedKategoriIds, currentMapYear);
      chart_kabupaten_container.className = 'col-md-6';
      chart_kabupaten_container.classList.add('d-none');
      chart_kategori_container.classList.add('d-none');
      cardStat.classList.add('d-none');
      loadKriminalitasStatistik(lastCheckedKategoriIds,currentMapYear);
      cardSubKategoriKriminal.classList.add('d-none');
      loadKriminalitasSubKategoriChart('provinsi',0, lastCheckedKategoriIds, currentMapYear);
      loadLokasiKejahatanChart('provinsi', 0, lastCheckedKategoriIds, currentMapYear);
      loadWaktuKejahatanChart('provinsi', 0, lastCheckedKategoriIds, currentMapYear);
    }else if (tipe === 'bencana') {
      showBencanaMap(lastCheckedKategoriIds, currentMapYear);
      loadBencanaBarChart('kabupaten', 0, lastCheckedKategoriIds, currentMapYear);
      loadBencanaDonutChart('kabupaten', 0, lastCheckedKategoriIds, currentMapYear);
      chart_kabupaten_container.className = 'col-md-6';
      chart_kabupaten_container.classList.remove('d-none');
      chart_kategori_container.classList.remove('d-none');
      cardStat.classList.add('d-none');
      cardSubKategoriKriminal.classList.add('d-none');
    } else {
      // Tambahkan fungsi peta lain di sini
      alert('Fungsi peta untuk "' + tipe + '" belum tersedia.');
    }
  });
  
  document.getElementById('mapYearSelect').addEventListener('change', function(){
    currentMapYear = this.value;
 
    // Reload map sesuai tipe saat ini dan tahun baru
    if(currentMapType === 'kriminalitas'){
      showKriminalitasMap(lastCheckedKategoriIds, currentMapYear);
      loadKriminalitasStatistik(lastCheckedKategoriIds, currentMapYear, 'provinsi', 0);
      loadKriminalitasBarChart('kabupaten',0, lastCheckedKategoriIds, currentMapYear);
      loadKriminalitasDonutChart('provinsi',0, lastCheckedKategoriIds, currentMapYear);
      loadKriminalitasSubKategoriChart('provinsi',0, lastCheckedKategoriIds, currentMapYear);
      loadLokasiKejahatanChart('provinsi', 0, lastCheckedKategoriIds, currentMapYear);
      loadWaktuKejahatanChart('provinsi', 0, lastCheckedKategoriIds, currentMapYear);
    }else if(currentMapType === 'kamtibmas'){
      showKamtibmasMap(lastCheckedKategoriIds, currentMapYear);
      loadKamtibmasBarChart('kabupaten',0, lastCheckedKategoriIds, currentMapYear);
      loadKamtibmasDonutChart('provinsi',0, lastCheckedKategoriIds, currentMapYear);
    }else if(currentMapType === 'lalu-lintas'){
      showLalinMap(lastCheckedKategoriIds, currentMapYear);
      loadLalinBarChart('kabupaten', 0, lastCheckedKategoriIds, currentMapYear);
      loadLalinDonutChart('kabupaten', 0, lastCheckedKategoriIds, currentMapYear);
    }else if(currentMapType === 'bencana'){
       showBencanaMap(lastCheckedKategoriIds, currentMapYear);
      loadBencanaBarChart('kabupaten', 0, lastCheckedKategoriIds, currentMapYear);
      loadBencanaDonutChart('kabupaten', 0, lastCheckedKategoriIds, currentMapYear);
    }
    // Tambah logic untuk map lain kalau perlu
  });

function loadKriminalitasStatistik( 
  kategoriFilter = [], 
  tahun = new Date().getFullYear(), 
  level = 'provinsi',
  wilayah_id = 0) {
    let url = 'get_statistik_kriminalitas.php';
    let params = [];
    if (kategoriFilter.length) params.push('kategori=' + kategoriFilter.join(','));
    if (tahun) params.push('tahun=' + tahun);
    if (level) params.push('level=' + level);
    if (wilayah_id) params.push('wilayah_id=' + wilayah_id);
    if(params.length) url += '?' + params.join('&');
    fetch(url)
      .then(res => res.json())
      .then(stat => {
        // Ganti label
        document.getElementById('stat-label-1').innerText = 'Crime Total';
        document.getElementById('stat-label-2').innerText = 'Crime Clearance';
        document.getElementById('stat-label-3').innerText = 'Crime Rate';
        // Isi value
        document.getElementById('stat-value-1').innerText = Number(stat.crime_total).toLocaleString('id-ID');
        document.getElementById('stat-value-2').innerText = Number(stat.crime_clearance).toLocaleString('id-ID');
        document.getElementById('stat-value-3').innerText = stat.crime_rate + ' %';
      })
      .catch(err => {
        // Isi fallback kalau error
        document.getElementById('stat-value-1').innerText = '0';
        document.getElementById('stat-value-2').innerText = '0';
        document.getElementById('stat-value-3').innerText = '0 %';
      });
}
//------------------------------------------
// Chart Lokasi Penting
//------------------------------------------
let myLokasiKatChart = null;
function loadLokasiKategoriBarChart() {
    fetch('lokasi_chart_kategori.php')
      .then(res => res.json())
      .then(data => {
        const labels = data.map(d => d.label);
        const totals = data.map(d => d.total);
        const warnaBar = data.map(d => d.color || '#6c3483');
        // Update judul card
        document.getElementById("chart-title").innerHTML =
            "<span class='fa fa-bar-chart me-2 fs-0'></span>Jumlah Lokasi Penting per Kategori";

        if(!window.myLokasiKatChart){
          window.myLokasiKatChart = echarts.init(document.getElementById('conflict_chart'));
        } else {
          window.myLokasiKatChart.clear();
        }
        window.myLokasiKatChart.setOption({
          tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
          grid: { left: '7%', right: '8%', bottom: '13%', top: '10%', containLabel: true },
          xAxis: { type: 'category', data: labels, axisLabel: { rotate:45 } },
          yAxis: { type: 'value', minInterval: 1 },
          series: [{
            name: 'Jumlah Lokasi',
            type: 'bar',
            barWidth: '48%',
            data: totals.map((v,i) => ({
              value: v,
              itemStyle: { color: warnaBar[i] }
            }))
          }]
        });
        // Auto-resize setelah lebar card diubah
        setTimeout(function(){
          if(window.myLokasiKatChart) window.myLokasiKatChart.resize();
        }, 300);
      });
}
//------------------------------------------
// Chart Bencana
//------------------------------------------
  let myBencanaChart = null;

  function loadBencanaBarChart(level = 'kabupaten', parent_id = 0, filterKategoriIds = [], tahun = new Date().getFullYear()) {
    let params = [`mode=${level}`, `parent_id=${parent_id}`];
    if(filterKategoriIds && filterKategoriIds.length) params.push('kategori=' + filterKategoriIds.join(','));
    if(tahun) params.push('tahun=' + tahun); 
    let url = 'bencana_chart_data.php?' + params.join('&');
    fetch(url)
      .then(res => res.json())
      .then(data => {
        const labels = data.map(d => d.label);
        const totals = data.map(d => d.total);

        // Update judul
        let title = "<span class='fa fa-bar-chart me-2 fs-0'></span>Grafik Bencana per Kabupaten";
        if (level === "kecamatan") title = "<span class='fa fa-bar-chart me-2 fs-0'></span>Grafik Bencana per Kecamatan";
        else if (level === "desa") title = "<span class='fa fa-bar-chart me-2 fs-0'></span>Grafik Bencana per Desa";
        document.getElementById("chart-title").innerHTML = title;

        if(!window.myBencanaChart){
          window.myBencanaChart = echarts.init(document.getElementById('conflict_chart'));
        } else {
          window.myBencanaChart.clear();
        }

        window.myBencanaChart.setOption({
          tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
          grid: { left: '5%', right: '8%', bottom: '13%', top: '10%', containLabel: true },
          xAxis: { type: 'category', data: labels, axisLabel: { rotate:45 } },
          yAxis: { type: 'value', minInterval: 1 },
          series: [{
            name: 'Jumlah Bencana',
            type: 'bar',
            barWidth: '48%',
            itemStyle: { color: '#e309c6ff' },
            data: totals
          }]
        });

        // Auto-resize setelah lebar card diubah
        setTimeout(function(){
          if(window.myBencanaChart) window.myBencanaChart.resize();
        }, 300);
      });
  }
  let myBencanaKatChart = null;
function loadBencanaDonutChart(level = 'kabupaten', parent_id = 0, filterKategoriIds = [], tahun = new Date().getFullYear()) {
  let params = [`mode=${level}`, `parent_id=${parent_id}`];
  if(filterKategoriIds && filterKategoriIds.length) params.push('kategori=' + filterKategoriIds.join(','));
  if(tahun) params.push('tahun=' + tahun);
  let url = 'bencana_chart_kategori.php?' + params.join('&');
  fetch(url)
    .then(res => res.json())
    .then(function(data) {
      // Update judul
      let title = "<span class='fa fa-pie-chart me-2 fs-0'></span>Grafik Bencana Berdasarkan Kategori";
        if (level === "kabupaten") title = "<span class='fa fa-pie-chart me-2 fs-0'></span>Grafik Bencana per Kategori (Kabupaten)";
        else if (level === "kecamatan") title = "<span class='fa fa-pie-chart me-2 fs-0'></span>Grafik Bencana per Kategori (Kecamatan)";
        else if (level === "desa") title = "<span class='fa fa-pie-chart me-2 fs-0'></span>Grafik Bencana per Kategori (Desa)";

        document.getElementById("chart-kat-title").innerHTML = title;

        if(!window.myBencanaKatChart){
          window.myBencanaKatChart = echarts.init(document.getElementById('conflict_chart_kat'));
        } else {
          window.myBencanaKatChart.clear();
        }

        window.myBencanaKatChart.setOption({
          tooltip: {
            trigger: 'item',
            formatter: '{b}: {c} ({d}%)'
          },
          legend: {
            orient: 'vertical',
            left: 10,
            data: data.map(d => d.label)
          },
          series: [{
            name: 'Kategori Lalin',
            type: 'pie',
            radius: ['40%', '70%'], // donut chart
            avoidLabelOverlap: false,
            label: {
              show: true,
              position: 'outside',
              formatter: '{b}\n{c}'
            },
            emphasis: {
              label: {
                show: true,
                fontSize: '16',
                fontWeight: 'bold'
              }
            },
            labelLine: { show: true },
            data: data.map((d,i) => ({
              value: Number(d.total),
              name: d.label
            }))
          }]
        });
      });
}
//------------------------------------------
// Chart Lalu Lintas
//------------------------------------------
  let myLalinChart = null;

  function loadLalinBarChart(level = 'kabupaten', parent_id = 0, filterKategoriIds = [], tahun = new Date().getFullYear()) {
    let params = [`mode=${level}`, `parent_id=${parent_id}`];
    if(filterKategoriIds && filterKategoriIds.length) params.push('kategori=' + filterKategoriIds.join(','));
    if(tahun) params.push('tahun=' + tahun);
    let url = 'lalin_chart_data.php?' + params.join('&');
    fetch(url)
      .then(res => res.json())
      .then(data => {
        const labels = data.map(d => d.label);
        const totals = data.map(d => d.total);

        // Update judul
        let title = "<span class='fa fa-bar-chart me-2 fs-0'></span>Grafik Lalu Lintas per Kabupaten";
        if (level === "kecamatan") title = "<span class='fa fa-bar-chart me-2 fs-0'></span>Grafik Lalu Lintas per Kecamatan";
        else if (level === "desa") title = "<span class='fa fa-bar-chart me-2 fs-0'></span>Grafik Lalu Lintas per Desa";
        document.getElementById("chart-title").innerHTML = title;

        if(!window.myLalinChart){
          window.myLalinChart = echarts.init(document.getElementById('conflict_chart'));
        } else {
          window.myLalinChart.clear();
        }

        window.myLalinChart.setOption({
          tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
          grid: { left: '5%', right: '8%', bottom: '13%', top: '10%', containLabel: true },
          xAxis: { type: 'category', data: labels, axisLabel: { rotate:45 } },
          yAxis: { type: 'value', minInterval: 1 },
          series: [{
            name: 'Jumlah Lalu Lintas',
            type: 'bar',
            barWidth: '48%',
            itemStyle: { color: '#0984e3' },
            data: totals
          }]
        });

        // Auto-resize setelah lebar card diubah
        setTimeout(function(){
          if(window.myLalinChart) window.myLalinChart.resize();
        }, 300);
      });
  }
  let myLalinKatChart = null;
  function loadLalinDonutChart(level = 'kabupaten', parent_id = 0, filterKategoriIds = [], tahun = new Date().getFullYear()) {
    let params = [`mode=${level}`, `parent_id=${parent_id}`];
  if(filterKategoriIds && filterKategoriIds.length) params.push('kategori=' + filterKategoriIds.join(','));
  if(tahun) params.push('tahun=' + tahun);
  let url = 'lalin_chart_kategori.php?' + params.join('&');
      fetch(url)
        .then(res => res.json())
        .then(function(data) {
          // Update judul
          let title = "<span class='fa fa-pie-chart me-2 fs-0'></span>Grafik Lalu Lintas Berdasarkan Kategori";
          if (level === "kabupaten") title = "<span class='fa fa-pie-chart me-2 fs-0'></span>Grafik Lalu Lintas per Kategori (Kabupaten)";
          else if (level === "kecamatan") title = "<span class='fa fa-pie-chart me-2 fs-0'></span>Grafik Lalu Lintas per Kategori (Kecamatan)";
          else if (level === "desa") title = "<span class='fa fa-pie-chart me-2 fs-0'></span>Grafik Lalu Lintas per Kategori (Desa)";

          document.getElementById("chart-kat-title").innerHTML = title;

          if(!window.myLalinKatChart){
            window.myLalinKatChart = echarts.init(document.getElementById('conflict_chart_kat'));
          } else {
            window.myLalinKatChart.clear();
          }

          window.myLalinKatChart.setOption({
            tooltip: {
              trigger: 'item',
              formatter: '{b}: {c} ({d}%)'
            },
            legend: {
              orient: 'vertical',
              left: 10,
              data: data.map(d => d.label)
            },
            series: [{
              name: 'Kategori Lalin',
              type: 'pie',
              radius: ['40%', '70%'], // donut chart
              avoidLabelOverlap: false,
              label: {
                show: true,
                position: 'outside',
                formatter: '{b}\n{c}'
              },
              emphasis: {
                label: {
                  show: true,
                  fontSize: '16',
                  fontWeight: 'bold'
                }
              },
              labelLine: { show: true },
              data: data.map((d,i) => ({
                value: Number(d.total),
                name: d.label
              }))
            }]
          });
        });
  }
//-----
//Chart Kriminalitas 
// ------------------------------------------
let waktuKejahatanChart = null;
function loadWaktuKejahatanChart(
  level, parent_id=0,
  filterKategoriIds = [],
  tahun = new Date().getFullYear() 
) {
    let params = [];
    if(filterKategoriIds && filterKategoriIds.length) params.push('kategori=' + filterKategoriIds.join(','));
    if(tahun) params.push('tahun=' + tahun);
    if(level) params.push('level=' + level);
    if(parent_id) params.push('parent_id=' + parent_id);
    let url = 'kriminalitas_chart_waktu.php?' + params.join('&');

    fetch(url)
      .then(res => res.json())
      .then(data => {
        if(!waktuKejahatanChart){
          waktuKejahatanChart = echarts.init(document.getElementById('waktu_kejahatan_chart'));
        } else {
          waktuKejahatanChart.clear();
        }
        waktuKejahatanChart.setOption({
          tooltip: { trigger: 'axis' },
          grid: { left: '5%', right: '3%', bottom: '13%', top: '12%', containLabel: true },
          xAxis: {
            type: 'category',
            data: data.map(d => d.label),
            boundaryGap: false
          },
          yAxis: { type: 'value', minInterval: 1 },
          series: [{
            name: 'Kasus Kejahatan',
            type: 'line',
            data: data.map(d => d.total),
            symbolSize: 10, 
            lineStyle: { width: 3, color: '#e17055' },
            itemStyle: { color: '#e17055' },
            areaStyle: { color: '#fab1a0', opacity: 0.18 }
          }]
        });
        setTimeout(() => { if(waktuKejahatanChart) waktuKejahatanChart.resize(); },300);
      });
}
let lokasiKejahatanChart = null;
function loadLokasiKejahatanChart(level, parent_id=0, filterKategoriIds = [], tahun = new Date().getFullYear()) {
    let params = [];
    if(filterKategoriIds && filterKategoriIds.length) params.push('kategori=' + filterKategoriIds.join(','));
    if(tahun) params.push('tahun=' + tahun);
    if(level) params.push('level=' + level);
    if(parent_id) params.push('parent_id=' + parent_id);
    let url = 'kriminalitas_chart_lokasikejahatan.php?' + params.join('&');
    fetch(url)
      .then(res => res.json())
      .then(data => {
        if(!lokasiKejahatanChart){
          lokasiKejahatanChart = echarts.init(document.getElementById('lokasi_kejahatan_chart'));
        } else {
          lokasiKejahatanChart.clear();
        }
        lokasiKejahatanChart.setOption({
          tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
          legend: {
            orient: 'vertical',
            left: 10,
            data: data.map(d => d.label)
          },
          series: [{
            name: 'Lokasi Kejahatan',
            type: 'pie',
            radius: ['0%', '70%'], // donut
            avoidLabelOverlap: false,
            label: {
              show: true,
              position: 'outside',
              formatter: '{b}\n{c}'
            },
            labelLine: { show: true },
            data: data.map(d => ({
              value: Number(d.total),
              name: d.label
            }))
          }]
        });
        setTimeout(function(){
          if(lokasiKejahatanChart) lokasiKejahatanChart.resize();
        }, 300);
      });
}
let subKategoriKriminalChart = null; 
function loadKriminalitasSubKategoriChart(level, parent_id = 0, filterKategoriIds = [], tahun = new Date().getFullYear()) {
    let params = [`mode=${level}`, `parent_id=${parent_id}`];
    if (filterKategoriIds && filterKategoriIds.length) params.push('kategori=' + filterKategoriIds.join(','));
    if (tahun) params.push('tahun=' + tahun);
    let url = 'kriminalitas_chart_datasub.php?' + params.join('&');
    fetch(url)
      .then(res => res.json())
      .then(data => {
        const labels = data.map(d => d.label);
        const totals = data.map(d => d.total);
        if(!subKategoriKriminalChart){
          subKategoriKriminalChart = echarts.init(document.getElementById('sub_kategori_chart'));
        } else {
          subKategoriKriminalChart.clear();
        }
        subKategoriKriminalChart.setOption({
          tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
          grid: { left: '5%', right: '8%', bottom: '13%', top: '10%', containLabel: true },
          xAxis: { type: 'category', data: labels, axisLabel:{ rotate:45 } },
          yAxis: { type: 'value', minInterval: 1 },
          series: [{
            name: 'Total Kriminalitas',
            type: 'bar',
            barWidth: '48%',
            data: totals,
            itemStyle: { color: '#2b8ec0' }
          }]
        });
      });
    setTimeout(function(){
      if(window.subKategoriKriminalChart) window.subKategoriKriminalChart.resize();
    }, 300);
}
let myKriminalitasChart = null;
function loadKriminalitasBarChart(level, parent_id = 0, filterKategoriIds = [], tahun = new Date().getFullYear()) {
    let params = [`mode=${level}`, `parent_id=${parent_id}`];
    if (filterKategoriIds && filterKategoriIds.length) params.push('kategori=' + filterKategoriIds.join(','));
    if (tahun) params.push('tahun=' + tahun);
    let url = 'kriminalitas_chart_data.php?' + params.join('&');
    fetch(url)
      .then(res => res.json())
      .then(data => {
        const labels = data.map(d => d.label);
        const totals = data.map(d => d.total);
        let ct = document.getElementById("chart-title");
        if (ct) ct.innerHTML =
          level === 'kabupaten' ? "<span class='fa fa-bar-chart me-2 fs-0'></span>Grafik Total Kriminalitas per Kabupaten"
          : level === 'kecamatan' ? "<span class='fa fa-bar-chart me-2 fs-0'></span>Grafik Total Kriminalitas per Kecamatan"
          : "<span class='fa fa-bar-chart me-2 fs-0'></span>Grafik Total Kriminalitas per Desa";
        if(!myKriminalitasChart){
          myKriminalitasChart = echarts.init(document.getElementById('conflict_chart'));
        } else {
          myKriminalitasChart.clear();
        }
        myKriminalitasChart.setOption({
          tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
          grid: { left: '5%', right: '8%', bottom: '13%', top: '10%', containLabel: true },
          xAxis: { type: 'category', data: labels, axisLabel:{ rotate:45 } },
          yAxis: { type: 'value', minInterval: 1 },
          series: [{
            name: 'Total Kriminalitas',
            type: 'bar',
            barWidth: '48%',
            data: totals,
            itemStyle: { color: '#c0392b' }
          }]
        });
      });
       setTimeout(function(){
          if(window.myKriminalitasChart) window.myKriminalitasChart.resize();
        }, 300);
  }
let myKriminalitasKatChart = null;
function loadKriminalitasDonutChart(level, parent_id = 0, filterKategoriIds = [], tahun = new Date().getFullYear()) {
    const modeBackend = (level === 'kabupaten') ? 'kabupaten'
                      : (level === 'kecamatan') ? 'kecamatan'
                      : (level === 'desa') ? 'desa'
                      : 'kabupaten';  
    let params = [`mode=${modeBackend}`, `parent_id=${parent_id}`];
    if (filterKategoriIds && filterKategoriIds.length) params.push('kategori=' + filterKategoriIds.join(','));
    if (tahun) params.push('tahun=' + tahun);
    let url = 'kriminalitas_chart_kategori.php?' + params.join('&');
    fetch(url)
      .then(res => res.json())
      .then(function(data) {
        const labels = data.map(d => d.label);
        const totals = data.map(d => d.total);  
        // Update judul
        let title = "<span class='fa fa-pie-chart me-2 fs-0'></span>Donat Kriminalitas Berdasarkan Kategori (Semua Wilayah)";
        if (level === "kabupaten")      title = "<span class='fa fa-pie-chart me-2 fs-0'></span>Donat Kriminalitas per Kategori (Kabupaten Dipilih)";
        else if (level === "kecamatan") title = "<span class='fa fa-pie-chart me-2 fs-0'></span>Donat Kriminalitas per Kategori (Kecamatan Dipilih)";
        else if (level === "desa")      title = "<span class='fa fa-pie-chart me-2 fs-0'></span>Donat Kriminalitas per Kategori (Desa Dipilih)";
        document.getElementById("chart-kat-title").innerHTML = title;
        if(!window.myKriminalitasKatChart){
          window.myKriminalitasKatChart = echarts.init(document.getElementById('conflict_chart_kat'));
        } else {
          window.myKriminalitasKatChart.clear();
        }
        window.myKriminalitasKatChart.setOption({
          tooltip: {
            trigger: 'item',
            formatter: '{b}: {c} ({d}%)'
          },
          legend: {
            orient: 'vertical',
            left: 10,
            data: labels
          },
          series: [{
            name: 'Jumlah Kriminalitas',
            type: 'pie',
            radius: ['40%', '70%'], // donut chart
            avoidLabelOverlap: false,
            label: {
              show: true,
              position: 'outside',
              formatter: '{b}\n{c}'
            },
            emphasis: {
              label: {
                show: true,  
                fontSize: '16',
                fontWeight: 'bold'              }
            },
            labelLine: { show: true },
            data: data.map((d,i) => ({
              value: Number(d.total),
              name: d.label
            }))
          }]
        });
      });
}
//------------------------------------------
// Chart Kamtibmas
//------------------------------------------
  let myKamtibmasChart = null;
  function loadKamtibmasBarChart(level, parent_id = 0, filterKategoriIds = [], tahun = new Date().getFullYear()) {
     let params = [`mode=${level}`, `parent_id=${parent_id}`];
    if (filterKategoriIds && filterKategoriIds.length) params.push('kategori=' + filterKategoriIds.join(','));
    if (tahun) params.push('tahun=' + tahun);
    let url = 'kamtibmas_chart_data.php?' + params.join('&');
    fetch(url)
      .then(res => res.json())
      .then(data => {
        const labels = data.map(d => d.label);
        const totals = data.map(d => d.total);

        let ct = document.getElementById("chart-title");
        if (ct) ct.innerHTML =
          level === 'kabupaten' ? "<span class='fa fa-bar-chart me-2 fs-0'></span>Grafik Total Kasus Menonjol per Kabupaten"
          : level === 'kecamatan' ? "<span class='fa fa-bar-chart me-2 fs-0'></span>Grafik Total Kasus Menonjol per Kecamatan"
          : "<span class='fa fa-bar-chart me-2 fs-0'></span>Grafik Total Kasus Menonjol per Desa";

        if(!myKamtibmasChart){
          myKamtibmasChart = echarts.init(document.getElementById('conflict_chart'));
        } else {
          myKamtibmasChart.clear();
        }
        myKamtibmasChart.setOption({
          tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
          grid: { left: '5%', right: '8%', bottom: '13%', top: '10%', containLabel: true },
          xAxis: { type: 'category', data: labels, axisLabel:{ rotate:45 } },
          yAxis: { type: 'value', minInterval: 1 },
          series: [{
            name: 'Total Kasus',
            type: 'bar',
            barWidth: '48%',
            data: totals,
            itemStyle: { color: '#16a085' }
          }]
        });
      });
       setTimeout(function(){
          if(window.myKamtibmasChart) window.myKamtibmasChart.resize();
        }, 300);
  }
  let myKamtibmasKatChart = null;
  function loadKamtibmasDonutChart(level, parent_id = 0, filterKategoriIds = [], tahun = new Date().getFullYear()) {
    const modeBackend = (level === 'kabupaten') ? 'kabupaten'
                      : (level === 'kecamatan') ? 'kecamatan'
                      : (level === 'desa') ? 'desa'
                      : 'kabupaten';
    let params = [`mode=${modeBackend}`, `parent_id=${parent_id}`];
    if (filterKategoriIds && filterKategoriIds.length) params.push('kategori=' + filterKategoriIds.join(','));
    if (tahun) params.push('tahun=' + tahun);
    let url = 'kamtibmas_chart_kategori.php?' + params.join('&');
    fetch(url)
      .then(res => res.json())
      .then(function(data) {
        const labels = data.map(d => d.label);
        const totals = data.map(d => d.total);

        // Update judul
        let title = "<span class='fa fa-pie-chart me-2 fs-0'></span>Kasus Menonjol Berdasarkan Kategori (Semua Wilayah)";
        if (level === "kabupaten")      title = "<span class='fa fa-pie-chart me-2 fs-0'></span>Kasus Menonjol Berdasarkan Kategori (Kabupaten Dipilih)";
        else if (level === "kecamatan") title = "<span class='fa fa-pie-chart me-2 fs-0'></span>Kasus Menonjol Berdasarkan Kategori (Kecamatan Dipilih)";
        else if (level === "desa")      title = "<span class='fa fa-pie-chart me-2 fs-0'></span>Kasus Menonjol Berdasarkan Kategori (Desa Dipilih)";
        document.getElementById("chart-kat-title").innerHTML = title;

        if(!window.myKamtibmasKatChart){
          window.myKamtibmasKatChart = echarts.init(document.getElementById('conflict_chart_kat'));
        } else {
          window.myKamtibmasKatChart.clear();
        }

        window.myKamtibmasKatChart.setOption({
          tooltip: {
            trigger: 'item',
            formatter: '{b}: {c} ({d}%)'
          },
          legend: {
            orient: 'vertical',
            left: 10,
            data: labels
          },
          series: [{
            name: 'Jumlah Kasus',
            type: 'pie',
            radius: ['40%', '70%'], // donut chart
            avoidLabelOverlap: false,
            label: {
              show: true,
              position: 'outside',
              formatter: '{b}\n{c}'
            },
            emphasis: {
              label: {
                show: true,
                fontSize: '16',
                fontWeight: 'bold'
              }
            },
            labelLine: { show: true },
            data: data.map((d,i) => ({
              value: Number(d.total),
              name: d.label
            }))
          }]
        });
      });
}
 
  let chartLevel = 'kabupaten'; // default awal
  let parentId = 0; // 0: full provinsi
  let myEChart = null;

  // Inisialisasi echart js
  function loadBarChart(level, parent_id = 0) {
    fetch(`konflik_chart_data.php?mode=${level}&parent_id=${parent_id}`)
      .then(res => res.json())
      .then(function(data) {
        const labels = data.map(d => d.label);
        const totals = data.map(d => d.total);

        // Update judul
        const chartTitle = document.getElementById("chart-title");
        if(level === 'kabupaten') {
          chartTitle.innerHTML = "<span class='fa fa-bar-chart me-2 fs-0'></span>Grafik Total Konflik per Kabupaten";
        } else if(level==='kecamatan') {
          chartTitle.innerHTML = "<span class='fa fa-bar-chart me-2 fs-0'></span>Grafik Total Konflik per Kecamatan";
        } else {
          chartTitle.innerHTML = "<span class='fa fa-bar-chart me-2 fs-0'></span>Grafik Total Konflik per Desa";
        }

        // Inisialisasi atau update chart
        if(!myEChart){
          myEChart = echarts.init(document.getElementById('conflict_chart'));
        } else {
          myEChart.clear();
        }
        var option = {
          tooltip: {
            trigger: 'axis',
            axisPointer: { type: 'shadow' }
          },
          grid: { left: '5%', right: '8%', bottom: '12%', top: '10%', containLabel: true },
          xAxis: {
            type: 'category',
            data: labels,
            axisLabel: { rotate: 45, interval: 0 }
          },
          yAxis: {
            type: 'value',
            minInterval: 1
          },
          series: [{
            name: 'Total Konflik',
            type: 'bar',
            barWidth: '48%',
            data: totals,
            itemStyle: {
              color: '#3388ff'
            }
          }]
        };
        myEChart.setOption(option);
      });
       setTimeout(function(){
          if(window.myEchart) window.myEchart.resize();
        }, 300);
  }
  function showDefaultMap() { 
    // Fungsi untuk menampilkan peta default kosong
      if (window.kabupatenLayer) { map2.removeLayer(window.kabupatenLayer); window.kabupatenLayer = null; }
      if (window.kecamatanLayer) { map2.removeLayer(window.kecamatanLayer); window.kecamatanLayer = null; }
      if (window.desaLayer) { map2.removeLayer(window.desaLayer); window.desaLayer = null; }
      if(window.lalinMarkerLayer){ map2.removeLayer(window.lalinMarkerLayer); window.lalinMarkerLayer = null; }
      if(window.lokasiMarkerLayer){ map2.removeLayer(window.lokasiMarkerLayer); window.lokasiMarkerLayer = null; }
        if(window.bencanaMarkerLayer) { map2.removeLayer(window.bencanaMarkerLayer); window.bencanaMarkerLayer = null; }
         if(window.kriminalitasMarkerLayer) { map2.removeLayer(window.kriminalitasMarkerLayer); window.kriminalitasMarkerLayer = null; }
       
       // Hide semua table
      const cardDataWilayah = document.getElementById('cardDataWilayah');
      cardDataWilayah.classList.add('d-none');
      let url = 'umum_kabupaten_polres_geojson.php?polres_id=' + <?php echo $polres_id; ?>;
      fetch(url)
      .then(response => response.json())
      .then(function(geojson) {
        window.kabupatenLayer = L.geoJSON(geojson, {
          style: function(feature) {
            
            return {
              color: "#3388ff",
              weight: 2,
              opacity: 0.7,
              fillOpacity: 0.80,
              fillColor: '#e5e1e1'
            };
          },
          onEachFeature: function(feature, layer) {
            
            var kabupaten_id = feature.properties.id;
            layer.bindTooltip(feature.properties.nama, {
              permanent: true,
              direction: 'center',
              className: 'lbl-kabupaten'
            });
            var html = `<b>${feature.properties.nama}</b><br>
              <br>
              <button class="btn btn-zoom-kabupaten btn-primary btn-sm me-1 mb-1" data-kabid="${kabupaten_id}">Lihat Detail</button>`;
              layer.bindPopup(html);

              layer.on('popupopen', function() {
                layer.closeTooltip();
              });
              layer.on('popupclose', function() {
                layer.openTooltip();
              });
          }
        }).addTo(map2);
        if(window.kabupatenLayer && window.kabupatenLayer.getBounds && window.kabupatenLayer.getBounds().isValid()){
            map2.fitBounds(window.kabupatenLayer.getBounds(), {maxZoom: 14}); // maxZoom bisa kamu adjust!
        }
           const sumberOverlay = document.getElementById('sumberOverlay');
           sumberOverlay.innerHTML = ""; // selalu clear dulu sebelum isi ulang
           //sumberOverlay.style.display = "none";
      });
       
    }
  function showKonflikMap() {
    // Fungsi untuk menampilkan peta konflik
      if (window.kabupatenLayer) { map2.removeLayer(window.kabupatenLayer); window.kabupatenLayer = null; }
      if (window.kecamatanLayer) { map2.removeLayer(window.kecamatanLayer); window.kecamatanLayer = null; }
      if (window.desaLayer) { map2.removeLayer(window.desaLayer); window.desaLayer = null; }
      if(window.lalinMarkerLayer){ map2.removeLayer(window.lalinMarkerLayer); window.lalinMarkerLayer = null; }
      if(window.lokasiMarkerLayer){ map2.removeLayer(window.lokasiMarkerLayer); window.lokasiMarkerLayer = null; }
        if(window.bencanaMarkerLayer) { map2.removeLayer(window.bencanaMarkerLayer); window.bencanaMarkerLayer = null; }
       // Hide semua table
      const cardDataWilayah = document.getElementById('cardDataWilayah');
      cardDataWilayah.classList.add('d-none');
      fetch('konflik_kabupaten_geojson.php')
      .then(response => response.json())
      .then(function(geojson) {
        window.kabupatenLayer = L.geoJSON(geojson, {
          style: function(feature) {
            var jumlah = Number(feature.properties.total_konflik) || 0;
            return {
              color: "#3388ff",
              weight: 2,
              opacity: 0.7,
              fillOpacity: 0.60,
              fillColor: jumlah > 30 ? '#800026' :
                  jumlah > 20 ? '#BD0026' :
                  jumlah > 10 ? '#E31A1C' :
                  jumlah > 5  ? '#FC4E2A' :
                  jumlah > 0  ? '#FFEDA0' :
                                '#99f8a6'
            };
          },
          onEachFeature: function(feature, layer) {
            var jumlah = feature.properties.total_konflik ?? 0;
            var kab_id = feature.properties.id;
            var kategori = feature.properties.kategori_konflik || [];
            let kategoriHtml = '';
            if(kategori.length) {
              kategoriHtml = '<hr class="my-2 mb-1">Kategori konflik:<ul class="ps-3 mb-0">';
              kategori.forEach(function(kat){
                kategoriHtml += `<li><b>${kat.label}</b>: ${kat.total}</li>`;
              });
              kategoriHtml += '</ul>';
            }
            var html = `<b>${feature.properties.nama}</b><br>
                        Total konflik: <b>${jumlah}</b> 
                        ${kategoriHtml}
                        <br><br> 
                        <button class="btn btn-zoom-kabupaten btn-primary btn-sm me-1 mb-1" data-kabid="${kab_id}">Lihat Detail</button>`;
            layer.bindTooltip(feature.properties.nama, {permanent:false, direction:'center'});
            layer.on('click', function(e){
              layer.bindPopup(html).openPopup();
            });
          }
        }).addTo(map2);
          const sumberOverlay = document.getElementById('sumberOverlay');
          if (sumberOverlay) {
            sumberOverlay.innerHTML = ""; // selalu clear dulu sebelum isi ulang
            let sumber = geojson.sumber_dokumen || [];
            let sumberHtml = sumber.length ? `<span class="fa fa-file-alt me-1"></span>Sumber: `
              + sumber.map(s => `<span class="text-primary">${s}</span>`).join(' <b>|</b> ')
              : '';
            sumberOverlay.innerHTML = sumberHtml;
          }
      });
      showTableWilayah('konflik', 'provinsi', 0);
    }
  function showKriminalitasMap(filterKategoriIds = [], tahun = new Date().getFullYear()) {
   // map2.setView(lokasi_provinsi, 8);
    if(window.kabupatenLayer) { map2.removeLayer(window.kabupatenLayer); window.kabupatenLayer = null; }
    if(window.kecamatanLayer) { map2.removeLayer(window.kecamatanLayer); window.kecamatanLayer = null; }
    if(window.desaLayer) { map2.removeLayer(window.desaLayer); window.desaLayer = null; }
    if(window.lalinMarkerLayer){ map2.removeLayer(window.lalinMarkerLayer); window.lalinMarkerLayer = null; }
    if(window.lokasiMarkerLayer){ map2.removeLayer(window.lokasiMarkerLayer); window.lokasiMarkerLayer = null; }
    if(window.bencanaMarkerLayer) { map2.removeLayer(window.bencanaMarkerLayer); window.bencanaMarkerLayer = null; }
    if(window.kriminalitasMarkerLayer) { map2.removeLayer(window.kriminalitasMarkerLayer); window.kriminalitasMarkerLayer = null; }
    const cardDataWilayah = document.getElementById('cardDataWilayah');
    cardDataWilayah.classList.add('d-none');

    let polres_id= <?php echo $_SESSION['polres_id']; ?>;
    let endpoint = 'kriminalitas_polres_geojson.php';  
    let params = [];
    if (filterKategoriIds.length) params.push('kategori=' + filterKategoriIds.join(','));
    if (tahun) params.push('tahun=' + tahun);
    if (polres_id) params.push('polres_id=' + polres_id);
    if(params.length) endpoint += '?' + params.join('&');
    fetch(endpoint)
      .then(res => res.json())
      .then(function(geojson){
        window.kabupatenLayer = L.geoJSON(geojson, {
          style: function(feature) {
            var jumlah = Number(feature.properties.total_kriminalitas) || 0;
      
            return {
              color: "#16a085",
              weight: 2,
              opacity: 0.7,
              fillOpacity: 0.57,
              fillColor:   
                  jumlah > 2000 ? '#BD0026' :
                  jumlah > 1000 ? '#E31A1C' :
                  jumlah > 500  ? '#FC4E2A' :
                  jumlah > 0  ? '#FFEDA0' :
                                '#99f8a6'
            };
          },
          onEachFeature: function(feature, layer) {
            var jumlah = feature.properties.total_kriminalitas ?? 0;
           
             var html = `<b>${feature.properties.nama}</b><br>
                        Total Kriminalitas : <b>${jumlah.toLocaleString('id-ID')}</b><br>
                        
                        <br><br>
                         `;
            layer.bindTooltip(feature.properties.nama, {permanent:false, direction:'center'});
            layer.on('click', function(e){
              layer.bindPopup(html).openPopup();
            });
             
          }
          
        }).addTo(map2);
         if(window.kabupatenLayer && window.kabupatenLayer.getBounds && window.kabupatenLayer.getBounds().isValid()){
            map2.fitBounds(window.kabupatenLayer.getBounds(), {maxZoom: 14}); // maxZoom bisa kamu adjust!
        }
          const sumberOverlay = document.getElementById('sumberOverlay');
          if (sumberOverlay) {
            sumberOverlay.innerHTML = ""; // selalu clear dulu sebelum isi ulang
            let sumber = geojson.sumber_dokumen || [];
            let sumberHtml = sumber.length ? `<span class="fa fa-file-alt me-1"></span>Sumber: `
              + sumber.map(s => `<span class="text-primary">${s}</span>`).join(' <b>|</b> ')
              : '';
            sumberOverlay.innerHTML = sumberHtml;
          }
      });
      let markerEndpoint = 'kriminalitas_marker_geojson.php';
      let markerParams = [];
      if (polres_id) markerParams.push('polres_id=' + polres_id);
      if (filterKategoriIds.length) markerParams.push('kategori=' + filterKategoriIds.join(','));
      if (tahun) markerParams.push('tahun=' + tahun);
      if(markerParams.length) markerEndpoint += '?' + markerParams.join('&');
      if(window.kriminalitasMarkerLayer) { map2.removeLayer(window.kriminalitasMarkerLayer); window.kriminalitasMarkerLayer=null;}
      fetch(markerEndpoint)
        .then(res => res.json())
        .then(function(geojson){
          var markerClusters = L.markerClusterGroup();
          var geoJsonLayer = L.geoJSON(geojson, {
            pointToLayer: function(feature, latlng) {
              var warna = (feature.properties.warna_marker || 'blue').toLowerCase();
              return L.marker(latlng, {
                icon: L.icon({
                  iconUrl: '../assets/img/marker-icon-' + warna + '.png',
                  iconSize: [25,41], iconAnchor: [12,41], popupAnchor: [1,-34],
                  shadowUrl: '../assets/img/marker-shadow.png', shadowSize: [41,41]
                })
              });
            },
            onEachFeature: function(feature, layer) {
              var p = feature.properties;
              var html = `<b>${p.no_lp || '-'}</b><br>
                          <b>Sub Kat:</b> ${p.sub_kategori_nama || '-'}<br>
                          <b>Lokasi:</b> ${p.lokasi || '-'}<br>
                          <b>Tanggal:</b> ${p.tanggal || '-'}
                          <br><br>
                          <a href="kriminalitas-view?id=${p.id}" class="btn btn-info btn-sm me-1 mb-1 text-white">Lihat Data</a>
                          `;
              layer.bindPopup(html);
            }
          });
          markerClusters.addLayer(geoJsonLayer);

          if(window.kriminalitasMarkerLayer){ map2.removeLayer(window.kriminalitasMarkerLayer);}
          window.kriminalitasMarkerLayer = markerClusters.addTo(map2);
        });
      showTableWilayah('kriminalitas', 'polres', polres_id);
  }
    function showKamtibmasMap(filterKategoriIds = [], tahun = new Date().getFullYear()) {
     map2.setView(lokasi_provinsi, 8);
    if(window.kabupatenLayer) { map2.removeLayer(window.kabupatenLayer); window.kabupatenLayer = null; }
    if(window.kecamatanLayer) { map2.removeLayer(window.kecamatanLayer); window.kecamatanLayer = null; }
    if(window.desaLayer) { map2.removeLayer(window.desaLayer); window.desaLayer = null; }
    if(window.lalinMarkerLayer){ map2.removeLayer(window.lalinMarkerLayer); window.lalinMarkerLayer = null; }
    if(window.lokasiMarkerLayer){ map2.removeLayer(window.lokasiMarkerLayer); window.lokasiMarkerLayer = null; }
      if(window.bencanaMarkerLayer) { map2.removeLayer(window.bencanaMarkerLayer); window.bencanaMarkerLayer = null; }
      // Hide semua table
      const cardDataWilayah = document.getElementById('cardDataWilayah');
      cardDataWilayah.classList.add('d-none');
      let endpoint = 'kamtibmas_kabupaten_geojson.php';
    let params = [];
    if(filterKategoriIds.length) params.push('kategori=' + filterKategoriIds.join(','));
    if(tahun) params.push('tahun=' + tahun);
    if(params.length) endpoint += '?' + params.join('&'); 
    fetch(endpoint)
      .then(res => res.json())
      .then(function(geojson){
        window.kabupatenLayer = L.geoJSON(geojson, {
          style: function(feature) {
            var jumlah = Number(feature.properties.total_kamtibmas) || 0;
            var hasMenonjol = feature.properties.has_menonjol == 1; // contoh properti untuk menandai wilayah menonjol 
            return {
              color: "#16a085",
              weight: 2,
              opacity: 0.7,
              fillOpacity: 0.57,
              fillColor:   
                  jumlah > 20 ? '#BD0026' :
                  jumlah > 10 ? '#E31A1C' :
                  jumlah > 5  ? '#FC4E2A' :
                  jumlah > 0  ? '#FFEDA0' :
                                '#99f8a6'
            };
          },
          onEachFeature: function(feature, layer) {
            var jumlah = feature.properties.total_kamtibmas ?? 0;
            var infoMenonjol = (feature.properties.has_menonjol == 1)
            ? `<span class="badge bg-danger ms-1">Ada Kasus Menonjol</span>`
            : '';
            var kab_id = feature.properties.id;
            var html = `<b>${feature.properties.nama}</b><br>
                        Total Kasus : <b>${jumlah}</b><br>
                         
                        <br><br>
                        <button class="btn btn-zoom-kabupaten btn-info btn-sm me-1 mb-1" data-kabid="${kab_id}">Lihat Detail</button>`;
            layer.bindTooltip(feature.properties.nama, {permanent:false, direction:'center'});
            layer.on('click', function(e){
              layer.bindPopup(html).openPopup();
            });
             
          }
          
        }).addTo(map2);
          const sumberOverlay = document.getElementById('sumberOverlay');
          if (sumberOverlay) {
            sumberOverlay.innerHTML = ""; // selalu clear dulu sebelum isi ulang
            let sumber = geojson.sumber_dokumen || [];
            let sumberHtml = sumber.length ? `<span class="fa fa-file-alt me-1"></span>Sumber: `
              + sumber.map(s => `<span class="text-primary">${s}</span>`).join(' <b>|</b> ')
              : '';
            sumberOverlay.innerHTML = sumberHtml;
          }
      });
      showTableWilayah('kamtibmas', 'provinsi', 0);
  }
   
  function showLalinMap(filterKategoriIds = [], tahun = new Date().getFullYear()) {
  map2.setView(lokasi_provinsi, 8);

  // Kosongkan layer drilldown lama
  if(window.kabupatenLayer) { map2.removeLayer(window.kabupatenLayer); window.kabupatenLayer = null; }
  if(window.kecamatanLayer) { map2.removeLayer(window.kecamatanLayer); window.kecamatanLayer = null; }
  if(window.desaLayer) { map2.removeLayer(window.desaLayer); window.desaLayer = null; }
  if(window.lalinMarkerLayer){ map2.removeLayer(window.lalinMarkerLayer); window.lalinMarkerLayer = null; }
  if(window.lokasiMarkerLayer){ map2.removeLayer(window.lokasiMarkerLayer); window.lokasiMarkerLayer = null; }
    if(window.bencanaMarkerLayer) { map2.removeLayer(window.bencanaMarkerLayer); window.bencanaMarkerLayer = null; }
    // Hide semua table
    const cardDataWilayah = document.getElementById('cardDataWilayah');
    if (cardDataWilayah) {
      cardDataWilayah.classList.add('d-none');
    }
    let url = 'lalin_kabupaten_geojson.php';
  let areaParam = [];
  if(filterKategoriIds.length) areaParam.push('kategori='+filterKategoriIds.join(','));
  if(tahun) areaParam.push('tahun='+tahun);
  if(areaParam.length) url += '?' + areaParam.join('&');
  fetch(url)
    .then(res => res.json())
    .then(function(geojson){
      window.kabupatenLayer = L.geoJSON(geojson, {
        style: function(feature) {
          var jumlah = Number(feature.properties.total_lalin) || 0;
          return {
            color: "#34495e",
            weight: 2,
            opacity: 0.7,
            fillOpacity: 0.57,
            fillColor:
              jumlah > 30 ? '#800026' :
                  jumlah > 20 ? '#BD0026' :
                  jumlah > 10 ? '#E31A1C' :
                  jumlah > 5  ? '#FC4E2A' :
                  jumlah > 0  ? '#FFEDA0' :
                                '#99f8a6'
          };
        },
        onEachFeature: function(feature, layer) {
          var jumlah = feature.properties.total_lalin ?? 0;
          var kab_id = feature.properties.id;
          var kategori = feature.properties.kategori_lalin ?? [];
           let kategoriHtml = '';
            if(kategori.length > 0) {
              kategoriHtml = '<hr class="my-2 mb-1">Kategori lalu lintas:<ul class="ps-3 mb-0">';
              kategori.forEach(function(kat){
                kategoriHtml += `<li><b>${kat.label}</b>: ${kat.total}</li>`;
              });
              kategoriHtml += '</ul>';
            }
          var html = `<b>${feature.properties.nama}</b><br>
                      Total Lalu Lintas: <b>${jumlah}</b>
                      ${kategoriHtml}
                      <br><br>
                      <button class="btn btn-zoom-kabupaten btn-info btn-sm me-1 mb-1" data-kabid="${kab_id}">Lihat Detail</button>`;
          layer.bindTooltip(feature.properties.nama, {permanent:false, direction:'center'});
          layer.on('click', function(e){ layer.bindPopup(html).openPopup(); });
        }
      }).addTo(map2);
      const sumberOverlay = document.getElementById('sumberOverlay');
          if (sumberOverlay) {
            sumberOverlay.innerHTML = ""; // selalu clear dulu sebelum isi ulang
            let sumber = geojson.sumber_dokumen || [];
            let sumberHtml = sumber.length ? `<span class="fa fa-file-alt me-1"></span>Sumber: `
              + sumber.map(s => `<span class="text-primary">${s}</span>`).join(' <b>|</b> ')
              : '';
            sumberOverlay.innerHTML = sumberHtml;
          }
    });
    // Hapus marker lama kalau ada
    if(window.lalinMarkerLayer) {
      map2.removeLayer(window.lalinMarkerLayer);
      window.lalinMarkerLayer = null;
    }

  let markerUrl = 'lalin_poin_geojson.php';
  let markerParam = [];
  if(filterKategoriIds.length) markerParam.push('kategori='+filterKategoriIds.join(','));
  if(tahun) markerParam.push('tahun='+tahun);
  if(markerParam.length) markerUrl += '?' + markerParam.join('&');
  fetch(markerUrl)
    .then(res => res.json())
    .then(function(geojson){   
      // Buat marker cluster group
      var markerClusters = L.markerClusterGroup();

      // Inisialisasi GeoJSON biasa, tiap point jadi marker
      var geoJsonLayer = L.geoJSON(geojson, {
        pointToLayer: function(feature, latlng) {
          var warna = (feature.properties.kategori_warna || 'blue').toLowerCase();
         
          return L.marker(latlng, {
            icon: L.icon({
                  iconUrl: '../assets/img/marker-icon-' + warna + '.png',
                  iconSize: [25,41], iconAnchor: [12,41], popupAnchor: [1,-34],
                  shadowUrl: '../assets/img/marker-shadow.png', shadowSize: [41,41]
                })
          });
        },
        onEachFeature: function(feature, layer) {
          var p = feature.properties;
          var html = `${p.kategori_nama || 'Kategori Lalin'}<br><b>${p.nama || 'Peristiwa Lalin'}</b><br>
                      Desa: <b>${p.desa_nama || '-'}</b><br>
                      Kec: <b>${p.kec_nama || '-'}</b><br>
                      Kab: <b>${p.kab_nama || '-'}</b><br>`;
          if(p.keterangan) html += `<i>${p.keterangan}</i><br>`;
          if(p.foto) html += `<img src="../public/upload/lalin/${p.foto}" alt="foto" class="img-fluid">`;
          layer.bindPopup(html);
        }
      });

      // Tambahkan geoJsonLayer ke cluster, lalu ke map
      markerClusters.addLayer(geoJsonLayer);

      // Hapus layer lalin lama jika ada
      if(window.lalinMarkerLayer){ map2.removeLayer(window.lalinMarkerLayer); window.lalinMarkerLayer = null; }
      window.lalinMarkerLayer = markerClusters.addTo(map2);
    });
    showTableWilayah('lalin', 'provinsi', 0);
}
function showBencanaMap(filterKategoriIds = [], tahun = new Date().getFullYear()) {
  map2.setView(lokasi_provinsi, 8);

  // Kosongkan layer drilldown lama
  if(window.kabupatenLayer) { map2.removeLayer(window.kabupatenLayer); window.kabupatenLayer = null; }
  if(window.kecamatanLayer) { map2.removeLayer(window.kecamatanLayer); window.kecamatanLayer = null; }
  if(window.desaLayer) { map2.removeLayer(window.desaLayer); window.desaLayer = null; }
  if(window.lalinMarkerLayer){ map2.removeLayer(window.lalinMarkerLayer); window.lalinMarkerLayer = null; }
  if(window.lokasiMarkerLayer){ map2.removeLayer(window.lokasiMarkerLayer); window.lokasiMarkerLayer = null; }
  if(window.bencanaMarkerLayer) { map2.removeLayer(window.bencanaMarkerLayer); window.bencanaMarkerLayer = null; }
    // Hide semua table
    const cardDataWilayah = document.getElementById('cardDataWilayah');
    if (cardDataWilayah) {
      cardDataWilayah.classList.add('d-none');
    }
  let params = [];
if (filterKategoriIds && filterKategoriIds.length) params.push('kategori=' + filterKategoriIds.join(','));
if (tahun) params.push('tahun=' + tahun);
// Untuk area/poligon:
let endpoint = 'bencana_kabupaten_geojson.php' + (params.length ? '?' + params.join('&') : '');
// Untuk marker/titik:
let endpoint2 = 'bencana_poin_geojson.php' + (params.length ? '?' + params.join('&') : '');
 
  fetch(endpoint)
    .then(res => res.json())
    .then(function(geojson){
      window.kabupatenLayer = L.geoJSON(geojson, {
        style: function(feature) {
          var jumlah = Number(feature.properties.total_bencana) || 0;
          return {
            color: "#34495e",
            weight: 2,
            opacity: 0.7,
            fillOpacity: 0.57,
            fillColor:
              jumlah > 30 ? '#800026' :
                  jumlah > 20 ? '#BD0026' :
                  jumlah > 10 ? '#E31A1C' :
                  jumlah > 5  ? '#FC4E2A' :
                  jumlah > 0  ? '#FFEDA0' :
                                '#99f8a6'
          };
        },
        onEachFeature: function(feature, layer) {
          var jumlah = feature.properties.total_bencana ?? 0;
          var kab_id = feature.properties.id;
          var kategori = feature.properties.kategori_bencana ?? [];
           let kategoriHtml = '';
            if(kategori.length > 0) {
              kategoriHtml = '<hr class="my-2 mb-1">Kategori bencana:<ul class="ps-3 mb-0">';
              kategori.forEach(function(kat){
                kategoriHtml += `<li><b>${kat.label}</b>: ${kat.total}</li>`;
              });
              kategoriHtml += '</ul>';
            }
          var html = `<b>${feature.properties.nama}</b><br>
                      Total Bencana: <b>${jumlah}</b>
                      ${kategoriHtml}
                      <br><br>
                      <button class="btn btn-zoom-kabupaten btn-info btn-sm me-1 mb-1" data-kabid="${kab_id}">Lihat Detail</button>`;
          layer.bindTooltip(feature.properties.nama, {permanent:false, direction:'center'});
          layer.on('click', function(e){ layer.bindPopup(html).openPopup(); });
        }
      }).addTo(map2);
      const sumberOverlay = document.getElementById('sumberOverlay');
          if (sumberOverlay) {
            sumberOverlay.innerHTML = ""; // selalu clear dulu sebelum isi ulang
            let sumber = geojson.sumber_dokumen || [];
            let sumberHtml = sumber.length ? `<span class="fa fa-file-alt me-1"></span>Sumber: `
              + sumber.map(s => `<span class="text-primary">${s}</span>`).join(' <b>|</b> ')
              : '';
            sumberOverlay.innerHTML = sumberHtml;
          }
    });
    // Hapus marker lama kalau ada
    if(window.bencanaMarkerLayer) {
      map2.removeLayer(window.bencanaMarkerLayer);
      window.bencanaMarkerLayer = null;
    }

  // Misal tanpa filter:
  fetch(endpoint2)
    .then(res => res.json())
    .then(function(geojson){   
      // Buat marker cluster group
      var markerClusters = L.markerClusterGroup();

      // Inisialisasi GeoJSON biasa, tiap point jadi marker
      var geoJsonLayer = L.geoJSON(geojson, {
        pointToLayer: function(feature, latlng) {
          var warna = (feature.properties.kategori_warna || 'blue').toLowerCase();
       
          return L.marker(latlng, {
            icon: L.icon({
                  iconUrl: '../assets/img/marker-icon-' + warna + '.png',
                  iconSize: [25,41], iconAnchor: [12,41], popupAnchor: [1,-34],
                  shadowUrl: '../assets/img/marker-shadow.png', shadowSize: [41,41]
                })
          });
        },
        onEachFeature: function(feature, layer) {
          var p = feature.properties;
          var html = `${p.kategori_nama || 'Kategori Bencana'}<br><b>${p.nama || 'Peristiwa Bencana'}</b><br>
                      Desa: <b>${p.desa_nama || '-'}</b><br>
                      Kec: <b>${p.kec_nama || '-'}</b><br>
                      Kab: <b>${p.kab_nama || '-'}</b><br>`;
          if(p.penyebab) html += `<i>${p.penyebab}</i><br>`;
          if(p.foto) html += `<img src="../public/upload/bencana/${p.foto}" alt="foto" class="img-fluid">`;
          layer.bindPopup(html);
        }
      });

      // Tambahkan geoJsonLayer ke cluster, lalu ke map
      markerClusters.addLayer(geoJsonLayer);

      // Hapus layer bencana lama jika ada
      if(window.bencanaMarkerLayer){ map2.removeLayer(window.bencanaMarkerLayer); window.bencanaMarkerLayer = null; }
      window.bencanaMarkerLayer = markerClusters.addTo(map2);
    });
    showTableWilayah('bencana', 'provinsi', 0);
}
function showLokasiMap() {
  map2.setView(lokasi_provinsi, 8);

  // Kosongkan layer drilldown lama
  if(window.kabupatenLayer) { map2.removeLayer(window.kabupatenLayer); window.kabupatenLayer = null; }
  if(window.kecamatanLayer) { map2.removeLayer(window.kecamatanLayer); window.kecamatanLayer = null; }
  if(window.desaLayer) { map2.removeLayer(window.desaLayer); window.desaLayer = null; }
  if(window.lalinMarkerLayer){ map2.removeLayer(window.lalinMarkerLayer); window.lalinMarkerLayer = null; }
  if(window.lokasiMarkerLayer){ map2.removeLayer(window.lokasiMarkerLayer); window.lokasiMarkerLayer = null; }
  // Hide semua table
  const cardDataWilayah = document.getElementById('cardDataWilayah');
  if (cardDataWilayah) {
    cardDataWilayah.classList.add('d-none');
  }

// Misal tanpa filter:
  fetch('lokasi_poin_geojson.php')
    .then(res => res.json())
    .then(function(geojson) {
      // Buat marker cluster group
      var markerClusters = L.markerClusterGroup();

      // Buat layer GeoJSON, tiap point jadi marker
      var geoJsonLayer = L.geoJSON(geojson, {
        pointToLayer: function(feature, latlng) {
          var warna = (feature.properties.kategori_warna || 'blue').toLowerCase();
   
          return L.marker(latlng, {
            icon: L.icon({
                  iconUrl: '../assets/img/marker-icon-' + warna + '.png',
                  iconSize: [25,41], iconAnchor: [12,41], popupAnchor: [1,-34],
                  shadowUrl: '../assets/img/marker-shadow.png', shadowSize: [41,41]
                })
          });
        },
        onEachFeature: function(feature, layer) {
          var p = feature.properties;
          var html = `<b>${p.nama || '-'}</b><br>
                      <span class="badge bg-secondary">${p.kategori_nama || ''}</span><br>
                      Alamat: ${p.alamat || '-'}<br>
                      HP: ${p.hp || '-'}<br>`;
          if(p.keterangan) html += `<i>${p.keterangan}</i><br>`;
          if(p.foto) html += `<img src="../public/upload/lokasi/${p.foto}" alt="foto" class="img-fluid">`;
          layer.bindPopup(html);
        }
      });

      markerClusters.addLayer(geoJsonLayer);

      // Hapus layer lama jika ada
      if(window.lokasiMarkerLayer) { map2.removeLayer(window.lokasiMarkerLayer); window.lokasiMarkerLayer = null; }
      window.lokasiMarkerLayer = markerClusters.addTo(map2);

      // Overlay sumber dokumen tetap
      const sumberOverlay = document.getElementById('sumberOverlay');
      if (sumberOverlay) {
        sumberOverlay.innerHTML = "";
        let sumber = geojson.sumber_dokumen || [];
        let sumberHtml = sumber.length ? `<span class="fa fa-file-alt me-1"></span>Sumber: `
          + sumber.map(s => `<span class="text-primary">${s}</span>`).join(' <b>|</b> ')
          : '';
        sumberOverlay.innerHTML = sumberHtml;
      }
    });
  showTableWilayah('lokasi', 'kabupaten', 0);
 }

 document.getElementById('filter-options-list').addEventListener('change', function(e){
    if(e.target.type === "checkbox") {
      lastCheckedKategoriIds = Array.from(this.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);
      if(currentMapType=='kriminalitas'){
        showKriminalitasMap(lastCheckedKategoriIds,currentMapYear);
        loadKriminalitasStatistik(lastCheckedKategoriIds, currentMapYear, 'provinsi', 0);
        loadKriminalitasBarChart('kabupaten',0,lastCheckedKategoriIds,currentMapYear);
        loadKriminalitasDonutChart('kabupaten',0,lastCheckedKategoriIds,currentMapYear);
        loadKriminalitasSubKategoriChart('provinsi',0,lastCheckedKategoriIds,currentMapYear);
        loadLokasiKejahatanChart('provinsi',0,lastCheckedKategoriIds,currentMapYear);
        loadWaktuKejahatanChart('provinsi',0,lastCheckedKategoriIds,currentMapYear);
      }else if(currentMapType=='bencana'){
        showBencanaMap(lastCheckedKategoriIds, currentMapYear);
        loadBencanaBarChart('kabupaten',0,lastCheckedKategoriIds,currentMapYear);
        loadBencanaDonutChart('kabupaten',0,lastCheckedKategoriIds,currentMapYear);
      }else if(currentMapType=='kamtibmas'){
        showKamtibmasMap(lastCheckedKategoriIds,currentMapYear);
        loadKamtibmasBarChart('kabupaten',0,lastCheckedKategoriIds,currentMapYear);
        loadKamtibmasDonutChart('provinsi',0,lastCheckedKategoriIds,currentMapYear);
      }else if(currentMapType=='lalu-lintas'){
        showLalinMap(lastCheckedKategoriIds,currentMapYear);
        loadLalinBarChart('kabupaten',0,lastCheckedKategoriIds,currentMapYear); 
        loadLalinDonutChart('kabupaten',0,lastCheckedKategoriIds,currentMapYear);
      }
    }
  });
  document.addEventListener('click', function(e) {
  // Cek jika peta konflik aktif (dropdown konflik)
  const tipe = document.getElementById('mapTypeSelect').value;
  if (tipe === 'umum') {
    // Kab
    if(e.target && e.target.classList.contains('btn-zoom-kabupaten')) {
      var kab_id = e.target.getAttribute('data-kabid');
      var activePopup = map2._popup;
      if(activePopup && activePopup._source && activePopup._source.getBounds) {
        map2.fitBounds(activePopup._source.getBounds());
      }
      if (window.kabupatenLayer) window.kabupatenLayer.setStyle({ fillOpacity: 0.20, fillColor: '#bbbbbb' });
      if (window.kecamatanLayer) { map2.removeLayer(window.kecamatanLayer); window.kecamatanLayer = null; }
      fetch('konflik_kecamatan_geojson.php?kabupaten_id=' + encodeURIComponent(kab_id))
      .then(res => res.json())
      .then(function(geojsonKec) {
        if (window.kecamatanLayer) { map2.removeLayer(window.kecamatanLayer); window.kecamatanLayer = null; }
        window.kecamatanLayer = L.geoJSON(geojsonKec, {
          style: function(feature) {
            var jumlah = Number(feature.properties.total_konflik) || 0;
            return {
              color: "#e67e22",
              weight: 2,
              opacity: 0.9,
              fillOpacity: 0.50,
              fillColor: '#bbbbbb'
            };
          },
          onEachFeature: function(feat, lyr) {
            var jml = feat.properties.total_konflik ?? 0;
            var kab_id = feat.properties.id;
            var html = `Kec. <b>${feat.properties.nama}</b> - <b>${feat.properties.kabupaten_nama}</b><br>
                       
                        <button class="btn btn-zoom-kecamatan btn-info btn-sm me-1 mb-1" data-kecid="${kab_id}">Lihat Detail</button>`;
            lyr.bindPopup(html);
            lyr.bindTooltip(feat.properties.nama, {permanent: false, direction: 'center'});
            lyr.on('click', function(e){ this.openPopup(); });
          }
        }).addTo(map2);
         
            
      });
       
    }

    // Kec
    if(e.target && e.target.classList.contains('btn-zoom-kecamatan')) {
      var kec_id = e.target.getAttribute('data-kecid');
      var activePopup = map2._popup;
      if(activePopup && activePopup._source && activePopup._source.getBounds) {
        map2.fitBounds(activePopup._source.getBounds());
      }
      if (window.kecamatanLayer) window.kecamatanLayer.setStyle({ fillOpacity: 0.20, fillColor: '#bbbbbb' });
      if (window.desaLayer) { map2.removeLayer(window.desaLayer); window.desaLayer = null; }
      fetch('konflik_desa_geojson.php?kecamatan_id=' + encodeURIComponent(kec_id))
      .then(res => res.json())
      .then(function(geojsonDes) {
        if (window.desaLayer) { map2.removeLayer(window.desaLayer); window.desaLayer = null; }
        window.desaLayer = L.geoJSON(geojsonDes, {
          style: function(feature) {
            var jumlah = Number(feature.properties.total_konflik) || 0;
            return {
              color: "#22e67aff",
              weight: 2,
              opacity: 0.9,
              fillOpacity: 0.50,
              fillColor:  '#ffffff'
            };
          },
          onEachFeature: function(feat, lyr) {
            var jml = feat.properties.total_konflik ?? 0;
            var html = `<div>
                          <b>${feat.properties.jenis} ${feat.properties.nama}</b><br>
                          Kecamatan: <b>${feat.properties.kecamatan_nama}</b><br>
                          Kabupaten: <b>${feat.properties.kabupaten_nama}</b><br>
                          
                      </div>`;
            lyr.bindPopup(html);
            lyr.bindTooltip(feat.properties.nama, {permanent: false, direction: 'center'});
            lyr.on('click', function(e){ this.openPopup(); });
          }
        }).addTo(map2);
        
           
      });
      
    }
  }else if(tipe==='kriminalitas'){
    // Kab
    if(e.target && e.target.classList.contains('btn-zoom-kabupaten')) {
      var kab_id = e.target.getAttribute('data-kabid');
      var activePopup = map2._popup;
      if(activePopup && activePopup._source && activePopup._source.getBounds) {
        map2.fitBounds(activePopup._source.getBounds());
      }
      if (window.kabupatenLayer) window.kabupatenLayer.setStyle({ fillOpacity: 0.20, fillColor: '#bbbbbb' });
      if (window.kecamatanLayer) { map2.removeLayer(window.kecamatanLayer); window.kecamatanLayer = null; }
      let url= 'kriminalitas_kecamatan_geojson.php?kabupaten_id=' + encodeURIComponent(kab_id)
          + '&kategori=' + lastCheckedKategoriIds.join(',')
          + '&tahun=' + currentMapYear;
        
      fetch(url)
      .then(res => res.json())
      .then(function(geojsonKec) {
        if (window.kecamatanLayer) { map2.removeLayer(window.kecamatanLayer); window.kecamatanLayer = null; }
        window.kecamatanLayer = L.geoJSON(geojsonKec, {
          style: function(feature) {
            var jumlah = Number(feature.properties.total_kriminalitas) || 0;
            return {
              color: "#e67e22",
              weight: 2,
              opacity: 0.9,
              fillOpacity: 0.50,
              fillColor:
                jumlah > 3000 ? '#800026' :
                jumlah > 2000 ? '#BD0026' :
                jumlah > 1000 ? '#E31A1C' :
                jumlah > 50  ? '#FC4E2A' :
                jumlah > 0  ? '#FFEDA0' :
                              '#99f8a6'
            };
          },
          onEachFeature: function(feat, lyr) {
            var jml = feat.properties.total_kriminalitas ?? 0;
            var kab_id = feat.properties.id;
            var html = `Kec. <b>${feat.properties.nama}</b> - <b>${feat.properties.kabupaten_nama}</b><br>
                        Total kriminalitas: <b>${jml}</b> <br><br>
                        <button class="btn btn-zoom-kecamatan btn-info btn-sm me-1 mb-1" data-kecid="${kab_id}">Lihat Detail</button>`;
            lyr.bindPopup(html);
            lyr.bindTooltip(feat.properties.nama, {permanent: false, direction: 'center'});
            lyr.on('click', function(e){ this.openPopup(); });
          }
        }).addTo(map2);
        // Bar chart per kecamatan
        loadKriminalitasBarChart('kecamatan', kab_id,lastCheckedKategoriIds,currentMapYear);
        loadKriminalitasDonutChart('kecamatan', kab_id,lastCheckedKategoriIds,currentMapYear);
        loadKriminalitasStatistik(lastCheckedKategoriIds, currentMapYear, 'kabupaten', kab_id);
        loadKriminalitasSubKategoriChart('kabupaten', kab_id,lastCheckedKategoriIds,currentMapYear);
        loadLokasiKejahatanChart('kabupaten', kab_id,lastCheckedKategoriIds,currentMapYear);
        loadWaktuKejahatanChart('kabupaten', kab_id,lastCheckedKategoriIds,currentMapYear);
        const sumberOverlay = document.getElementById('sumberOverlay');
            if (sumberOverlay) {
              sumberOverlay.innerHTML = ""; // selalu clear dulu sebelum isi ulang
              let sumber = geojsonKec.sumber_dokumen || [];
              let sumberHtml = sumber.length ? `<span class="fa fa-file-alt me-1"></span>Sumber: `
                + sumber.map(s => `<span class="text-primary">${s}</span>`).join(' <b>|</b> ')
                : '';
              sumberOverlay.innerHTML = sumberHtml;
            }
            
      });
      showTableWilayah('kriminalitas', 'kabupaten', kab_id);
    }
    // Kec
    if(e.target && e.target.classList.contains('btn-zoom-kecamatan')) {
      var kec_id = e.target.getAttribute('data-kecid');
      var activePopup = map2._popup;
      if(activePopup && activePopup._source && activePopup._source.getBounds) {
        map2.fitBounds(activePopup._source.getBounds());
      }
      if (window.kecamatanLayer) window.kecamatanLayer.setStyle({ fillOpacity: 0.20, fillColor: '#bbbbbb' });
      if (window.desaLayer) { map2.removeLayer(window.desaLayer); window.desaLayer = null; }
        fetch('kriminalitas_desa_geojson.php?kecamatan_id=' + encodeURIComponent(kec_id)
          + '&kategori=' + lastCheckedKategoriIds.join(',')
          + '&tahun=' + currentMapYear
        )
      .then(res => res.json())
      .then(function(geojsonDes) {
        if (window.desaLayer) { map2.removeLayer(window.desaLayer); window.desaLayer = null; }
        window.desaLayer = L.geoJSON(geojsonDes, {
          style: function(feature) {
            var jumlah = Number(feature.properties.total_kriminalitas) || 0;
            return {
              color: "#22e67aff",
              weight: 2,
              opacity: 0.9,
              fillOpacity: 0.50,
              fillColor:
                jumlah > 30 ? '#800026' :
                jumlah > 20 ? '#BD0026' :
                jumlah > 10 ? '#E31A1C' :
                jumlah > 5  ? '#FC4E2A' :
                jumlah > 0  ? '#FFEDA0' :
                              '#99f8a6'
            };
          },
          onEachFeature: function(feat, lyr) {
            var jml = feat.properties.total_kriminalitas ?? 0;
            var html = `<div>
                          <b>${feat.properties.jenis} ${feat.properties.nama}</b><br>
                          Kecamatan: <b>${feat.properties.kecamatan_nama}</b><br>
                          Kabupaten: <b>${feat.properties.kabupaten_nama}</b><br>
                          Total kriminalitas: <b>${jml}</b>
                      </div>`;
            lyr.bindPopup(html);
            lyr.bindTooltip(feat.properties.nama, {permanent: false, direction: 'center'});
            lyr.on('click', function(e){ this.openPopup(); });
          }
        }).addTo(map2);
        // Chart per desa
        loadKriminalitasBarChart('desa', kec_id,lastCheckedKategoriIds,currentMapYear);
        loadKriminalitasDonutChart('desa', kec_id,lastCheckedKategoriIds,currentMapYear);
        loadKriminalitasStatistik(lastCheckedKategoriIds, currentMapYear, 'kecamatan', kec_id);
        loadKriminalitasSubKategoriChart('kecamatan', kec_id,lastCheckedKategoriIds,currentMapYear);
        loadLokasiKejahatanChart('kecamatan', kec_id,lastCheckedKategoriIds,currentMapYear);
        loadWaktuKejahatanChart('kecamatan', kec_id,lastCheckedKategoriIds,currentMapYear);
        const sumberOverlay = document.getElementById('sumberOverlay');
          if (sumberOverlay) {
            sumberOverlay.innerHTML = ""; // selalu clear dulu sebelum isi ulang
            let sumber = geojsonDes.sumber_dokumen || [];
            let sumberHtml = sumber.length ? `<span class="fa fa-file-alt me-1"></span>Sumber: `
              + sumber.map(s => `<span class="text-primary">${s}</span>`).join(' <b>|</b> ')
              : '';
            sumberOverlay.innerHTML = sumberHtml;
          }
           
      });
      showTableWilayah('kriminalitas', 'kecamatan', kec_id);
    }
  } else if (tipe === 'konflik') {
    // Kab
    if(e.target && e.target.classList.contains('btn-zoom-kabupaten')) {
      var kab_id = e.target.getAttribute('data-kabid');
      var activePopup = map2._popup;
      if(activePopup && activePopup._source && activePopup._source.getBounds) {
        map2.fitBounds(activePopup._source.getBounds());
      }
      if (window.kabupatenLayer) window.kabupatenLayer.setStyle({ fillOpacity: 0.20, fillColor: '#bbbbbb' });
      if (window.kecamatanLayer) { map2.removeLayer(window.kecamatanLayer); window.kecamatanLayer = null; }
      fetch('konflik_kecamatan_geojson.php?kabupaten_id=' + encodeURIComponent(kab_id))
      .then(res => res.json())
      .then(function(geojsonKec) {
        if (window.kecamatanLayer) { map2.removeLayer(window.kecamatanLayer); window.kecamatanLayer = null; }
        window.kecamatanLayer = L.geoJSON(geojsonKec, {
          style: function(feature) {
            var jumlah = Number(feature.properties.total_konflik) || 0;
            return {
              color: "#e67e22",
              weight: 2,
              opacity: 0.9,
              fillOpacity: 0.50,
              fillColor:
                jumlah > 30 ? '#800026' :
                jumlah > 20 ? '#BD0026' :
                jumlah > 10 ? '#E31A1C' :
                jumlah > 5  ? '#FC4E2A' :
                jumlah > 0  ? '#FFEDA0' :
                              '#99f8a6'
            };
          },
          onEachFeature: function(feat, lyr) {
            var jml = feat.properties.total_konflik ?? 0;
            var kab_id = feat.properties.id;
            var html = `Kec. <b>${feat.properties.nama}</b> - <b>${feat.properties.kabupaten_nama}</b><br>
                        Total konflik: <b>${jml}</b> <br><br>
                        <button class="btn btn-zoom-kecamatan btn-info btn-sm me-1 mb-1" data-kecid="${kab_id}">Lihat Detail</button>`;
            lyr.bindPopup(html);
            lyr.bindTooltip(feat.properties.nama, {permanent: false, direction: 'center'});
            lyr.on('click', function(e){ this.openPopup(); });
          }
        }).addTo(map2);
        // Bar chart per kecamatan
        loadBarChart('kecamatan', kab_id);
        loadDonutKategori('kabupaten', kab_id);
        const sumberOverlay = document.getElementById('sumberOverlay');
            if (sumberOverlay) {
              sumberOverlay.innerHTML = ""; // selalu clear dulu sebelum isi ulang
              let sumber = geojsonKec.sumber_dokumen || [];
              let sumberHtml = sumber.length ? `<span class="fa fa-file-alt me-1"></span>Sumber: `
                + sumber.map(s => `<span class="text-primary">${s}</span>`).join(' <b>|</b> ')
                : '';
              sumberOverlay.innerHTML = sumberHtml;
            }
            
      });
      showTableWilayah('konflik', 'kabupaten', kab_id);
    }

    // Kec
    if(e.target && e.target.classList.contains('btn-zoom-kecamatan')) {
      var kec_id = e.target.getAttribute('data-kecid');
      var activePopup = map2._popup;
      if(activePopup && activePopup._source && activePopup._source.getBounds) {
        map2.fitBounds(activePopup._source.getBounds());
      }
      if (window.kecamatanLayer) window.kecamatanLayer.setStyle({ fillOpacity: 0.20, fillColor: '#bbbbbb' });
      if (window.desaLayer) { map2.removeLayer(window.desaLayer); window.desaLayer = null; }
      fetch('konflik_desa_geojson.php?kecamatan_id=' + encodeURIComponent(kec_id))
      .then(res => res.json())
      .then(function(geojsonDes) {
        if (window.desaLayer) { map2.removeLayer(window.desaLayer); window.desaLayer = null; }
        window.desaLayer = L.geoJSON(geojsonDes, {
          style: function(feature) {
            var jumlah = Number(feature.properties.total_konflik) || 0;
            return {
              color: "#22e67aff",
              weight: 2,
              opacity: 0.9,
              fillOpacity: 0.50,
              fillColor:
                jumlah > 30 ? '#800026' :
                jumlah > 20 ? '#BD0026' :
                jumlah > 10 ? '#E31A1C' :
                jumlah > 5  ? '#FC4E2A' :
                jumlah > 0  ? '#FFEDA0' :
                              '#99f8a6'
            };
          },
          onEachFeature: function(feat, lyr) {
            var jml = feat.properties.total_konflik ?? 0;
            var html = `<div>
                          <b>${feat.properties.jenis} ${feat.properties.nama}</b><br>
                          Kecamatan: <b>${feat.properties.kecamatan_nama}</b><br>
                          Kabupaten: <b>${feat.properties.kabupaten_nama}</b><br>
                          Total konflik: <b>${jml}</b>
                      </div>`;
            lyr.bindPopup(html);
            lyr.bindTooltip(feat.properties.nama, {permanent: false, direction: 'center'});
            lyr.on('click', function(e){ this.openPopup(); });
          }
        }).addTo(map2);
        // Chart per desa
        loadBarChart('desa', kec_id);
        loadDonutKategori('kecamatan', kec_id);
        const sumberOverlay = document.getElementById('sumberOverlay');
          if (sumberOverlay) {
            sumberOverlay.innerHTML = ""; // selalu clear dulu sebelum isi ulang
            let sumber = geojsonDes.sumber_dokumen || [];
            let sumberHtml = sumber.length ? `<span class="fa fa-file-alt me-1"></span>Sumber: `
              + sumber.map(s => `<span class="text-primary">${s}</span>`).join(' <b>|</b> ')
              : '';
            sumberOverlay.innerHTML = sumberHtml;
          }
           
      });
      showTableWilayah('konflik', 'kecamatan', kec_id);
    }
  }else if (tipe === 'kamtibmas') {
    // Kab klik
    if (e.target && e.target.classList.contains('btn-zoom-kabupaten')) {
      var kab_id = e.target.getAttribute('data-kabid');
      var activePopup = map2._popup;
      if(activePopup && activePopup._source && activePopup._source.getBounds) {
        map2.fitBounds(activePopup._source.getBounds());
      }
      if (window.kabupatenLayer) {
        clearLayerAnim(window.kabupatenLayer);
        window.kabupatenLayer.setStyle({ fillOpacity: 0.20, fillColor: '#bbbbbb' });
      }
      if (window.kecamatanLayer) { map2.removeLayer(window.kecamatanLayer); window.kecamatanLayer = null; }
      let url='kamtibmas_kecamatan_geojson.php?kabupaten_id='+encodeURIComponent(kab_id)+
      '&kategori='+lastCheckedKategoriIds.join(',')+
      '&tahun='+currentMapYear;
      fetch(url)
      .then(res => res.json())
      .then(function(geojsonKec) {
        if (window.kecamatanLayer) { map2.removeLayer(window.kecamatanLayer); window.kecamatanLayer = null; }
        window.kecamatanLayer = L.geoJSON(geojsonKec, {
          style: function(feature) {
            var jumlah = Number(feature.properties.total_kamtibmas) || 0;
       
            return {
              color: "#e67e22",
              weight: 2,
              opacity: 0.85,
              fillOpacity: 0.50,
              fillColor:  
                jumlah > 20 ? '#BD0026' :
                jumlah > 10 ? '#E31A1C' :
                jumlah > 5  ? '#FC4E2A' :
                jumlah > 0  ? '#FFEDA0' :
                              '#99f8a6'
            };
          },
          onEachFeature: function(feat, lyr) {
            var jml = feat.properties.total_kamtibmas ?? 0;
            
            var kec_id = feat.properties.id;
            var html = `Kec. <b>${feat.properties.nama}</b><br>
                        Total Kamtibmas: <b>${jml}</b> <br>
                       
                        <button class="btn btn-zoom-kecamatan btn-info btn-sm me-1 mb-1" data-kecid="${kec_id}">Lihat Detail</button>`;
            lyr.bindPopup(html);
            lyr.bindTooltip(feat.properties.nama, {permanent: false, direction: 'center'});
            lyr.on('click', function(e){ this.openPopup(); });
            
          }
        }).addTo(map2);
        loadKamtibmasBarChart('kecamatan', kab_id, lastCheckedKategoriIds, currentMapYear);
        loadKamtibmasDonutChart('kecamatan', kab_id, lastCheckedKategoriIds, currentMapYear);
        const sumberOverlay = document.getElementById('sumberOverlay');
          if (sumberOverlay) {
            sumberOverlay.innerHTML = ""; // selalu clear dulu sebelum isi ulang
            let sumber = geojsonKec.sumber_dokumen || [];
            let sumberHtml = sumber.length ? `<span class="fa fa-file-alt me-1"></span>Sumber: `
              + sumber.map(s => `<span class="text-primary">${s}</span>`).join(' <b>|</b> ')
              : '';
            sumberOverlay.innerHTML = sumberHtml;
          }
      });
      showTableWilayah('kamtibmas', 'kabupaten', kab_id);
    }

    // Kec klik
    if (e.target && e.target.classList.contains('btn-zoom-kecamatan')) {
      var kec_id = e.target.getAttribute('data-kecid');
      var activePopup = map2._popup;
      if(activePopup && activePopup._source && activePopup._source.getBounds) {
        map2.fitBounds(activePopup._source.getBounds());
      }
      if (window.kecamatanLayer) {
        clearLayerAnim(window.kecamatanLayer);
        window.kecamatanLayer.setStyle({ fillOpacity: 0.20, fillColor: '#bbbbbb' });
      }
      if (window.desaLayer) { map2.removeLayer(window.desaLayer); window.desaLayer = null; }
      let url='kamtibmas_desa_geojson.php?kecamatan_id='+encodeURIComponent(kec_id)+
      '&kategori='+lastCheckedKategoriIds.join(',')+
      '&tahun='+currentMapYear;

      fetch(url)
      .then(res => res.json())
      .then(function(geojsonDes) {
        if (window.desaLayer) { map2.removeLayer(window.desaLayer); window.desaLayer = null; }
        window.desaLayer = L.geoJSON(geojsonDes, {
          style: function(feature) {
            var jumlah = Number(feature.properties.total_kamtibmas) || 0;
            
            return {
              color: "#22e67aff",
              weight: 2,
              opacity: 0.85,
              fillOpacity: 0.50,
              fillColor:  
                jumlah > 20 ? '#BD0026' :
                jumlah > 10 ? '#E31A1C' :
                jumlah > 5  ? '#FC4E2A' :
                jumlah > 0  ? '#FFEDA0' :
                              '#99f8a6'
            };
          },
          onEachFeature: function(feat, lyr) {
            var jml = feat.properties.total_kamtibmas ?? 0;
             
            var html = `<div>
                          <b>${feat.properties.nama}</b><br>
                          Total Kamtibmas: <b>${jml}</b> <br>
                         
                        </div>`;
            lyr.bindPopup(html);
            lyr.bindTooltip(feat.properties.nama, {permanent: false, direction: 'center'});
            lyr.on('click', function(e){ this.openPopup(); });
             
          }
        }).addTo(map2);
        loadKamtibmasBarChart('desa', kec_id, lastCheckedKategoriIds, currentMapYear);
        loadKamtibmasDonutChart('desa', kec_id, lastCheckedKategoriIds, currentMapYear);
        const sumberOverlay = document.getElementById('sumberOverlay');
          if (sumberOverlay) {
            sumberOverlay.innerHTML = ""; // selalu clear dulu sebelum isi ulang
            let sumber = geojsonDes.sumber_dokumen || [];
            let sumberHtml = sumber.length ? `<span class="fa fa-file-alt me-1"></span>Sumber: `
              + sumber.map(s => `<span class="text-primary">${s}</span>`).join(' <b>|</b> ')
              : '';
            sumberOverlay.innerHTML = sumberHtml;
          }
      });
      showTableWilayah('kamtibmas', 'kecamatan', kec_id);
    }
  }else if (tipe === 'lalu-lintas') {
    // kab
    if(e.target && e.target.classList.contains('btn-zoom-kabupaten')) {
      var kab_id = e.target.getAttribute('data-kabid');
      var activePopup = map2._popup;
      if(activePopup && activePopup._source && activePopup._source.getBounds) {
        map2.fitBounds(activePopup._source.getBounds());
      }
      if (window.kabupatenLayer) window.kabupatenLayer.setStyle({ fillOpacity: 0.20, fillColor: '#bbbbbb' });
      if (window.kecamatanLayer) { map2.removeLayer(window.kecamatanLayer); window.kecamatanLayer = null; }
      let url='lalin_kecamatan_geojson.php?kabupaten_id='+encodeURIComponent(kab_id)+
        '&kategori='+lastCheckedKategoriIds.join(',')+
        '&tahun='+currentMapYear;
      fetch(url)
      .then(res => res.json())
      .then(function(geojsonKec) {
        if (window.kecamatanLayer) { map2.removeLayer(window.kecamatanLayer); window.kecamatanLayer = null; }
        window.kecamatanLayer = L.geoJSON(geojsonKec, {
          style: function(feature) {
            var jumlah = Number(feature.properties.total_lalin) || 0;
            return {
              color: "#e67e22",
              weight: 2,
              opacity: 0.9,
              fillOpacity: 0.50,
              fillColor:
                jumlah > 20 ? '#BD0026' :
                jumlah > 10 ? '#E31A1C' :
                jumlah > 5  ? '#FC4E2A' :
                jumlah > 0  ? '#FFEDA0' :
                              '#99f8a6'
            };
          },
          onEachFeature: function(feat, lyr) {
            var jml = feat.properties.total_lalin ?? 0;
            var kec_id = feat.properties.id;
            var kategori=feat.properties.kategori_lalin||[];
            let kategoriHtml = ''; 
            if(kategori.length > 0) {
              kategoriHtml = '<hr class="my-2 mb-1">Kategori lalu lintas:<ul class="pl-3 mb-0">';
              kategori.forEach(function(kat){
                kategoriHtml += `<li><b>${kat.label}</b>: ${kat.total}</li>`;
              });
              kategoriHtml += '</ul>';
            }
            var html = `Kec. <b>${feat.properties.nama}</b> - <b>${feat.properties.kabupaten_nama}</b><br>
                        Total lalu lintas: <b>${jml}</b> 
                        ${kategoriHtml}
                        <br><br>
                        <button class="btn btn-zoom-kecamatan btn-info btn-sm me-1 mb-1" data-kecid="${kec_id}">Lihat Detail</button>`;
            lyr.bindPopup(html);
            lyr.bindTooltip(feat.properties.nama, {permanent: false, direction: 'center'});
            lyr.on('click', function(e){ this.openPopup(); });
          }
        }).addTo(map2);
        // Bar chart per kecamatan
        loadLalinBarChart('kecamatan', kab_id, lastCheckedKategoriIds, currentMapYear);
        loadLalinDonutChart('kecamatan', kab_id, lastCheckedKategoriIds, currentMapYear);
        const sumberOverlay = document.getElementById('sumberOverlay');
          if (sumberOverlay) {
            sumberOverlay.innerHTML = ""; // selalu clear dulu sebelum isi ulang
            let sumber = geojsonKec.sumber_dokumen || [];
            let sumberHtml = sumber.length ? `<span class="fa fa-file-alt me-1"></span>Sumber: `
              + sumber.map(s => `<span class="text-primary">${s}</span>`).join(' <b>|</b> ')
              : '';
            sumberOverlay.innerHTML = sumberHtml;
          }
          showTableWilayah('lalin', 'kabupaten', kab_id);
      });
      if(window.lalinMarkerLayer) {
        map2.removeLayer(window.lalinMarkerLayer);
        window.lalinMarkerLayer = null;
      }

      // Misal tanpa filter:
    let urlPoints= 'lalin_poin_geojson.php?kabupaten_id='+encodeURIComponent(kab_id)+
  '&kategori='+lastCheckedKategoriIds.join(',')+
  '&tahun='+currentMapYear;
    fetch(urlPoints)
      .then(res => res.json())
      .then(function(geojson){
        // Buat marker cluster group
        var markerClusters = L.markerClusterGroup();

        // Buat geojson layer marker
        var geoJsonLayer = L.geoJSON(geojson, {
          pointToLayer: function(feature, latlng) {
            var warna = (feature.properties.kategori_warna || 'blue').toLowerCase();
            return L.marker(latlng, {
              icon: L.icon({
                  iconUrl: '../assets/img/marker-icon-' + warna + '.png',
                  iconSize: [25,41], iconAnchor: [12,41], popupAnchor: [1,-34],
                  shadowUrl: '../assets/img/marker-shadow.png', shadowSize: [41,41]
                })
            });
          },
          onEachFeature: function(feature, layer) {
            var p = feature.properties;
            var html = `${p.kategori_nama || 'Kategori Lalin'}<br><b>${p.nama || 'Peristiwa Lalin'}</b><br>
                        Desa: <b>${p.desa_nama || '-'}</b><br>
                        Kec: <b>${p.kec_nama || '-'}</b><br>
                        Kab: <b>${p.kab_nama || '-'}</b><br>`;
            if(p.keterangan) html += `<i>${p.keterangan}</i><br>`;
            if(p.foto) html += `<img src="../public/upload/lalin/${p.foto}" alt="foto" class="img-fluid">`;
            layer.bindPopup(html);
          }
        });

        // Tambahkan geoJsonLayer ke cluster, lalu ke map
        markerClusters.addLayer(geoJsonLayer);

        // Hapus markerLayer lama jika ada
        if(window.lalinMarkerLayer){ map2.removeLayer(window.lalinMarkerLayer); window.lalinMarkerLayer = null; }
        window.lalinMarkerLayer = markerClusters.addTo(map2);
      });
    }
    // kec
    if(e.target && e.target.classList.contains('btn-zoom-kecamatan')) {
      var kec_id = e.target.getAttribute('data-kecid');
      var activePopup = map2._popup;
      if(activePopup && activePopup._source && activePopup._source.getBounds) {
        map2.fitBounds(activePopup._source.getBounds());
      }
      if (window.kecamatanLayer) window.kecamatanLayer.setStyle({ fillOpacity: 0.20, fillColor: '#bbbbbb' });
      if (window.desaLayer) { map2.removeLayer(window.desaLayer); window.desaLayer = null; }
      let url='lalin_desa_geojson.php?kecamatan_id='+encodeURIComponent(kec_id)+
  '&kategori='+lastCheckedKategoriIds.join(',')+
  '&tahun='+currentMapYear;
      fetch(url)
      .then(res => res.json())
      .then(function(geojsonDes) {
        if (window.desaLayer) { map2.removeLayer(window.desaLayer); window.desaLayer = null; }
        window.desaLayer = L.geoJSON(geojsonDes, {
          style: function(feature) {
            var jumlah = Number(feature.properties.total_lalin) || 0;
            return {
              color: "#22e67aff",
              weight: 2,
              opacity: 0.9,
              fillOpacity: 0.50,
              fillColor:              
                jumlah > 20 ? '#BD0026' :
                jumlah > 10 ? '#E31A1C' :
                jumlah > 5  ? '#FC4E2A' :
                jumlah > 0  ? '#FFEDA0' :
                              '#99f8a6'
            };
          },
          onEachFeature: function(feat, lyr) {
            var jml = feat.properties.total_lalin ?? 0;
            var kategori = feat.properties.kategori_lalin || [];
            let kategoriHtml = ''; 
            if(kategori.length > 0) {
              kategoriHtml = '<hr class="my-2 mb-1">Kategori lalu lintas:<ul class="pl-3 mb-0">';
              kategori.forEach(function(kat){
                kategoriHtml += `<li><b>${kat.label}</b>: ${kat.total}</li>`;
              });
              kategoriHtml += '</ul>';
            }
            var html = `<div>
                          <b>${feat.properties.jenis} ${feat.properties.nama}</b><br>
                          Kecamatan: <b>${feat.properties.kecamatan_nama}</b><br>
                          Kabupaten: <b>${feat.properties.kabupaten_nama}</b><br>
                          Total lalu lintas: <b>${jml}</b>
                          ${kategoriHtml}
                          <br><br>
                      </div>`;
            lyr.bindPopup(html);
            lyr.bindTooltip(feat.properties.nama, {permanent: false, direction: 'center'});
            lyr.on('click', function(e){ this.openPopup(); });
          }
        }).addTo(map2);
        // Chart per desa
        loadLalinBarChart('desa', kec_id, lastCheckedKategoriIds, currentMapYear);
        loadLalinDonutChart('desa', kec_id, lastCheckedKategoriIds, currentMapYear);
        const sumberOverlay = document.getElementById('sumberOverlay');
          if (sumberOverlay) {
            sumberOverlay.innerHTML = ""; // selalu clear dulu sebelum isi ulang
            let sumber = geojsonDes.sumber_dokumen || [];
            let sumberHtml = sumber.length ? `<span class="fa fa-file-alt me-1"></span>Sumber: `
              + sumber.map(s => `<span class="text-primary">${s}</span>`).join(' <b>|</b> ')
              : '';
            sumberOverlay.innerHTML = sumberHtml;
          }
          showTableWilayah('lalin', 'kecamatan', kec_id);
      });
      if(window.lalinMarkerLayer) {
        map2.removeLayer(window.lalinMarkerLayer);
        window.lalinMarkerLayer = null;
      }

      // Misal tanpa filter:
    fetch('lalin_poin_geojson.php?kecamatan_id='+encodeURIComponent(kec_id)+
  '&kategori='+lastCheckedKategoriIds.join(',')+
  '&tahun='+currentMapYear)
      .then(res => res.json())
      .then(function(geojson){
        // Buat marker cluster group
        var markerClusters = L.markerClusterGroup();

        // Buat geojson layer marker
        var geoJsonLayer = L.geoJSON(geojson, {
          pointToLayer: function(feature, latlng) {
            var warna = (feature.properties.kategori_warna || 'blue').toLowerCase();
            return L.marker(latlng, {
              icon: L.icon({
                  iconUrl: '../assets/img/marker-icon-' + warna + '.png',
                  iconSize: [25,41], iconAnchor: [12,41], popupAnchor: [1,-34],
                  shadowUrl: '../assets/img/marker-shadow.png', shadowSize: [41,41]
                })
            });
          },
          onEachFeature: function(feature, layer) {
            var p = feature.properties;
            var html = `${p.kategori_nama || 'Kategori Lalin'}<br><b>${p.nama || 'Peristiwa Lalin'}</b><br>
                        Desa: <b>${p.desa_nama || '-'}</b><br>
                        Kec: <b>${p.kec_nama || '-'}</b><br>
                        Kab: <b>${p.kab_nama || '-'}</b><br>`;
            if(p.keterangan) html += `<i>${p.keterangan}</i><br>`;
            if(p.foto) html += `<img src="../public/upload/lalin/${p.foto}" alt="foto" class="img-fluid">`;
            layer.bindPopup(html);
          }
        });

        // Tambahkan geoJsonLayer ke cluster group
        markerClusters.addLayer(geoJsonLayer);

        // Hapus marker layer lama jika ada
        if(window.lalinMarkerLayer){ map2.removeLayer(window.lalinMarkerLayer); window.lalinMarkerLayer = null; }
        window.lalinMarkerLayer = markerClusters.addTo(map2);
      });
    }
  }else if (tipe === 'bencana') {
    // kab
    if(e.target && e.target.classList.contains('btn-zoom-kabupaten')) {
      var kab_id = e.target.getAttribute('data-kabid');
      var activePopup = map2._popup;
      if(activePopup && activePopup._source && activePopup._source.getBounds) {
        map2.fitBounds(activePopup._source.getBounds());
      }
      if (window.kabupatenLayer) window.kabupatenLayer.setStyle({ fillOpacity: 0.20, fillColor: '#bbbbbb' });
      if (window.kecamatanLayer) { map2.removeLayer(window.kecamatanLayer); window.kecamatanLayer = null; }
      let url='bencana_kecamatan_geojson.php?kabupaten_id='+encodeURIComponent(kab_id)+
        '&kategori='+lastCheckedKategoriIds.join(',')+
        '&tahun='+currentMapYear;
      fetch(url)
      .then(res => res.json())
      .then(function(geojsonKec) {
        if (window.kecamatanLayer) { map2.removeLayer(window.kecamatanLayer); window.kecamatanLayer = null; }
        window.kecamatanLayer = L.geoJSON(geojsonKec, {
          style: function(feature) {
            var jumlah = Number(feature.properties.total_bencana) || 0;
            return {
              color: "#e67e22",
              weight: 2,
              opacity: 0.9,
              fillOpacity: 0.50,
              fillColor:
                jumlah > 20 ? '#BD0026' :
                jumlah > 10 ? '#E31A1C' :
                jumlah > 5  ? '#FC4E2A' :
                jumlah > 0  ? '#FFEDA0' :
                              '#99f8a6'
            };
          },
          onEachFeature: function(feat, lyr) {
            var jml = feat.properties.total_bencana ?? 0;
            var kec_id = feat.properties.id;
            var kategori=feat.properties.kategori_bencana||[];
            let kategoriHtml = ''; 
            if(kategori.length > 0) {
              kategoriHtml = '<hr class="my-2 mb-1">Kategori bencana:<ul class="pl-3 mb-0">';
              kategori.forEach(function(kat){
                kategoriHtml += `<li><b>${kat.label}</b>: ${kat.total}</li>`;
              });
              kategoriHtml += '</ul>';
            }
            var html = `Kec. <b>${feat.properties.nama}</b> - <b>${feat.properties.kabupaten_nama}</b><br>
                        Total bencana: <b>${jml}</b> 
                        ${kategoriHtml}
                        <br><br>
                        <button class="btn btn-zoom-kecamatan btn-info btn-sm me-1 mb-1" data-kecid="${kec_id}">Lihat Detail</button>`;
            lyr.bindPopup(html);
            lyr.bindTooltip(feat.properties.nama, {permanent: false, direction: 'center'});
            lyr.on('click', function(e){ this.openPopup(); });
          }
        }).addTo(map2);
        // Bar chart per kecamatan
        loadBencanaBarChart('kecamatan', kab_id, lastCheckedKategoriIds, currentMapYear);
        loadBencanaDonutChart('kecamatan', kab_id, lastCheckedKategoriIds, currentMapYear);
        const sumberOverlay = document.getElementById('sumberOverlay');
          if (sumberOverlay) {
            sumberOverlay.innerHTML = ""; // selalu clear dulu sebelum isi ulang
            let sumber = geojsonKec.sumber_dokumen || [];
            let sumberHtml = sumber.length ? `<span class="fa fa-file-alt me-1"></span>Sumber: `
              + sumber.map(s => `<span class="text-primary">${s}</span>`).join(' <b>|</b> ')
              : '';
            sumberOverlay.innerHTML = sumberHtml;
          }
          showTableWilayah('bencana', 'kabupaten', kab_id);
      });
      if(window.bencanaMarkerLayer) {
        map2.removeLayer(window.bencanaMarkerLayer);
        window.bencanaMarkerLayer = null;
      }

      // Misal tanpa filter:
    let urlPoints= 'bencana_poin_geojson.php?kabupaten_id='+encodeURIComponent(kab_id)+
  '&kategori='+lastCheckedKategoriIds.join(',')+
  '&tahun='+currentMapYear;
    fetch(urlPoints)
      .then(res => res.json())
      .then(function(geojson){
        // Buat marker cluster group
        var markerClusters = L.markerClusterGroup();

        // Buat geojson layer marker
        var geoJsonLayer = L.geoJSON(geojson, {
          pointToLayer: function(feature, latlng) {
            var warna = (feature.properties.kategori_warna || 'blue').toLowerCase();
       
            return L.marker(latlng, {
              icon: L.icon({
                  iconUrl: '../assets/img/marker-icon-' + warna + '.png',
                  iconSize: [25,41], iconAnchor: [12,41], popupAnchor: [1,-34],
                  shadowUrl: '../assets/img/marker-shadow.png', shadowSize: [41,41]
                })
            });
          },
          onEachFeature: function(feature, layer) {
            var p = feature.properties;
            var html = `${p.kategori_nama || 'Kategori Bencana'}<br><b>${p.nama || 'Peristiwa Bencana'}</b><br>
                        Desa: <b>${p.desa_nama || '-'}</b><br>
                        Kec: <b>${p.kec_nama || '-'}</b><br>
                        Kab: <b>${p.kab_nama || '-'}</b><br>`;
            if(p.keterangan) html += `<i>${p.keterangan}</i><br>`;
            if(p.foto) html += `<img src="../public/upload/bencana/${p.foto}" alt="foto" class="img-fluid">`;
            layer.bindPopup(html);
          }
        });

        // Tambahkan geoJsonLayer ke cluster, lalu ke map
        markerClusters.addLayer(geoJsonLayer);

        // Hapus markerLayer lama jika ada
        if(window.bencanaMarkerLayer){ map2.removeLayer(window.bencanaMarkerLayer); window.bencanaMarkerLayer = null; }
        window.bencanaMarkerLayer = markerClusters.addTo(map2);
      });
    }
    // kec
    if(e.target && e.target.classList.contains('btn-zoom-kecamatan')) {
      var kec_id = e.target.getAttribute('data-kecid');
      var activePopup = map2._popup;
      if(activePopup && activePopup._source && activePopup._source.getBounds) {
        map2.fitBounds(activePopup._source.getBounds());
      }
      if (window.kecamatanLayer) window.kecamatanLayer.setStyle({ fillOpacity: 0.20, fillColor: '#bbbbbb' });
      if (window.desaLayer) { map2.removeLayer(window.desaLayer); window.desaLayer = null; }
      let url='bencana_desa_geojson.php?kecamatan_id='+encodeURIComponent(kec_id)+
  '&kategori='+lastCheckedKategoriIds.join(',')+
  '&tahun='+currentMapYear;
      fetch(url)
      .then(res => res.json())
      .then(function(geojsonDes) {
        if (window.desaLayer) { map2.removeLayer(window.desaLayer); window.desaLayer = null; }
        window.desaLayer = L.geoJSON(geojsonDes, {
          style: function(feature) {
            var jumlah = Number(feature.properties.total_bencana) || 0;
            return {
              color: "#22e67aff",
              weight: 2,
              opacity: 0.9,
              fillOpacity: 0.50,
              fillColor:              
                jumlah > 20 ? '#BD0026' :
                jumlah > 10 ? '#E31A1C' :
                jumlah > 5  ? '#FC4E2A' :
                jumlah > 0  ? '#FFEDA0' :
                              '#99f8a6'
            };
          },
          onEachFeature: function(feat, lyr) {
            var jml = feat.properties.total_bencana ?? 0;
            var kategori = feat.properties.kategori_bencana || [];
            let kategoriHtml = ''; 
            if(kategori.length > 0) {
              kategoriHtml = '<hr class="my-2 mb-1">Kategori bencana:<ul class="pl-3 mb-0">';
              kategori.forEach(function(kat){
                kategoriHtml += `<li><b>${kat.label}</b>: ${kat.total}</li>`;
              });
              kategoriHtml += '</ul>';
            }
            var html = `<div>
                          <b>${feat.properties.jenis} ${feat.properties.nama}</b><br>
                          Kecamatan: <b>${feat.properties.kecamatan_nama}</b><br>
                          Kabupaten: <b>${feat.properties.kabupaten_nama}</b><br>
                          Total lalu lintas: <b>${jml}</b>
                          ${kategoriHtml}
                          <br><br>
                      </div>`;
            lyr.bindPopup(html);
            lyr.bindTooltip(feat.properties.nama, {permanent: false, direction: 'center'});
            lyr.on('click', function(e){ this.openPopup(); });
          }
        }).addTo(map2);
        // Chart per desa
        loadBencanaBarChart('desa', kec_id, lastCheckedKategoriIds, currentMapYear);
        loadBencanaDonutChart('desa', kec_id, lastCheckedKategoriIds, currentMapYear);
        const sumberOverlay = document.getElementById('sumberOverlay');
          if (sumberOverlay) {
            sumberOverlay.innerHTML = ""; // selalu clear dulu sebelum isi ulang
            let sumber = geojsonDes.sumber_dokumen || [];
            let sumberHtml = sumber.length ? `<span class="fa fa-file-alt me-1"></span>Sumber: `
              + sumber.map(s => `<span class="text-primary">${s}</span>`).join(' <b>|</b> ')
              : '';
            sumberOverlay.innerHTML = sumberHtml;
          }
          showTableWilayah('bencana', 'kecamatan', kec_id);
      });
      if(window.bencanaMarkerLayer) {
        map2.removeLayer(window.bencanaMarkerLayer);
        window.bencanaMarkerLayer = null;
      }

      // Misal tanpa filter:
    fetch('bencana_poin_geojson.php?kecamatan_id='+encodeURIComponent(kec_id)+
  '&kategori='+lastCheckedKategoriIds.join(',')+
  '&tahun='+currentMapYear)
      .then(res => res.json())
      .then(function(geojson){
        // Buat marker cluster group
        var markerClusters = L.markerClusterGroup();

        // Buat geojson layer marker
        var geoJsonLayer = L.geoJSON(geojson, {
          pointToLayer: function(feature, latlng) {
            var warna = (feature.properties.kategori_warna || 'blue').toLowerCase();
            return L.marker(latlng, {
              icon: L.icon({
                  iconUrl: '../assets/img/marker-icon-' + warna + '.png',
                  iconSize: [25,41], iconAnchor: [12,41], popupAnchor: [1,-34],
                  shadowUrl: '../assets/img/marker-shadow.png', shadowSize: [41,41]
                })
            });
          },
          onEachFeature: function(feature, layer) {
            var p = feature.properties;
            var html = `${p.kategori_nama || 'Kategori Bencana'}<br><b>${p.nama || 'Peristiwa Bencana'}</b><br>
                        Desa: <b>${p.desa_nama || '-'}</b><br>
                        Kec: <b>${p.kec_nama || '-'}</b><br>
                        Kab: <b>${p.kab_nama || '-'}</b><br>`;
            if(p.keterangan) html += `<i>${p.keterangan}</i><br>`;
            if(p.foto) html += `<img src="../public/upload/bencana/${p.foto}" alt="foto" class="img-fluid">`;
            layer.bindPopup(html);
          }
        });

        // Tambahkan geoJsonLayer ke cluster group
        markerClusters.addLayer(geoJsonLayer);

        // Hapus marker layer lama jika ada
        if(window.lalinMarkerLayer){ map2.removeLayer(window.lalinMarkerLayer); window.lalinMarkerLayer = null; }
        window.lalinMarkerLayer = markerClusters.addTo(map2);
      });
    }
  } else if (tipe === 'lokasi-penting') {
    // Tambahkan logika drilldown untuk peta lokasi di sini jika diperlukan
  }
});
    map2.invalidateSize();

  
</script>

  </body>

</html>
<?php
} else {
  header("Location: ../index");
}
?>