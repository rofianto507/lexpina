var polsekId = $('body').data('polsek-id') || '';
$('#kabupaten_id_lokasi').change(function() {
        var kabupatenId = $(this).val();
        $('#kecamatan_id_lokasi').prop('disabled', true).html('<option value="">- Pilih Kecamatan -</option>');
        $('#desa_id_lokasi').prop('disabled', true).html('<option value="">- Pilih Desa -</option>');
        if(kabupatenId) {
          $.get('get_kecamatan.php', {kabupaten_id: kabupatenId, polsek_id: polsekId}, function(data) {
            var opt = '<option value="">- Pilih Kecamatan -</option>';
            var firstId = ""; // simpan id kecamatan pertama
            $.each(data, function(i, v) {
              if(i === 0) firstId = v.id;
              opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>';
            });
            $('#kecamatan_id_lokasi').html(opt).prop('disabled', false);
            // **Pilih otomatis kecamatan pertama jika ada**
            if(firstId) {
              $('#kecamatan_id_lokasi').val(firstId).trigger('change');
            }
          }, 'json');
        }
      });

      $('#kecamatan_id_lokasi').change(function() {
        var kecamatanId = $(this).val();
        $('#desa_id_lokasi').prop('disabled', true).html('<option value="">- Pilih Desa -</option>');
        if(kecamatanId) {
          $.get('get_desa.php', {kecamatan_id: kecamatanId}, function(data) {
            var opt = '<option value="">- Pilih Desa -</option>';
            var firstId = "";
            $.each(data, function(i, v) {
              if(i === 0) firstId = v.id;
              opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>';
            });
            $('#desa_id_lokasi').html(opt).prop('disabled', false);
            // **Pilih otomatis desa pertama jika ada**
            if(firstId) {
              $('#desa_id_lokasi').val(firstId);
            }
          }, 'json');
        }
      });
var latProvinsi = parseFloat($('body').data('lat-provinsi'));
var lngProvinsi = parseFloat($('body').data('lng-provinsi'));     
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
       $(document).ready(function() {
        $('#LokasiTable').DataTable({
          "autoWidth": false,
          "order": [[ 0, "desc" ]],
          initComplete: function () {
            this.api().columns([3,4,5,6]).every( function () {
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
      $(document).on('click', '.btnEditLokasi', function() {
        var id = $(this).data('id');
        $('#edit_id').val(id);
        $('#edit_nama').val($(this).data('nama'));
        $('#edit_desa_id').val($(this).data('desa-id'));
        $('#edit_kategori_id').val($(this).data('kategori-id'));
        $('#edit_kategori_warna').val($(this).data('kategori-warna'));
        $('#edit_alamat').val($(this).data('alamat'));
        $('#edit_hp').val($(this).data('hp'));
        $('#edit_keterangan').val($(this).data('keterangan'));
        $('#edit_latitude').val($(this).data('latitude'));
        $('#edit_longitude').val($(this).data('longitude'));
        $('#foto_lama').val($(this).data('foto'));
       
        var foto = $(this).data('foto');
        if(foto) {
          $('#previewFotoEdit').attr('src', '../public/upload/lokasi/' + foto);
          $('#previewFotoEditWrapper').show();
        } else {
          $('#previewFotoEditWrapper').hide();
        }
        
        var kabupaten_id = $(this).data('kabupaten-id');
        var kecamatan_id = $(this).data('kecamatan-id');
        var desa_id = $(this).data('desa-id');
        $('#edit_kabupaten_id').val(kabupaten_id);
        if(kabupaten_id) {
              $.get('get_kecamatan.php', {kabupaten_id: kabupaten_id, polsek_id: polsekId}, function(data){
                var opt = '<option value="">- Pilih Kecamatan -</option>';
                $.each(data, function(i,v){
                  var sel = (v.id == kecamatan_id) ? 'selected' : '';
                  opt += '<option value="'+v.id+'" '+sel+'>'+v.nama+'</option>';
                });
                $('#edit_kecamatan_id').html(opt).prop('disabled', false);
                if(kecamatan_id) {
                  $.get('get_desa.php', {kecamatan_id: kecamatan_id}, function(dataDesa){
                    var optDesa = '<option value="">- Pilih Desa -</option>';
                    $.each(dataDesa, function(i, d){
                      // Pilih desa yang sesuai
                      var sel = (d.id == desa_id) ? 'selected' : '';
                      optDesa += '<option value="'+d.id+'" '+sel+'>'+d.nama+'</option>';
                    });
                    $('#edit_desa_id').html(optDesa).prop('disabled', false);
                  }, 'json');
                }
              }, 'json');
            }
        $('#modalEditLokasi').modal('show');
      });
        $('#edit_kabupaten_id').change(function() {
          var kabupatenId = $(this).val();
          $('#edit_kecamatan_id').html('<option value="">- Pilih Kecamatan -</option>');
          $('#edit_desa_id').html('<option value="">- Pilih Desa -</option>');
          if(kabupatenId) {
            $.get('get_kecamatan.php', {kabupaten_id: kabupatenId, polsek_id: polsekId}, function(data) {
              var opt = '<option value="">- Pilih Kecamatan -</option>';
              var firstId = "";
              $.each(data, function(i, v) {
                if(i===0) firstId = v.id;
                opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>';
              });
              $('#edit_kecamatan_id').html(opt);
              if(firstId) {
                $('#edit_kecamatan_id').val(firstId).trigger('change');
              }
            }, 'json');
          }
        });
          // Kecamatan → Desa
      $('#edit_kecamatan_id').change(function() {
        var kecamatanId = $(this).val();
        $('#edit_desa_id').html('<option value="">- Pilih Desa -</option>');
        if(kecamatanId) {
          $.get('get_desa.php', {kecamatan_id: kecamatanId}, function(data) {
            var opt = '<option value="">- Pilih Desa -</option>';
            var firstId = "";
            $.each(data, function(i, v) {
              if(i===0) firstId = v.id;
              opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>';
            });
            $('#edit_desa_id').html(opt);
            if(firstId) {
              $('#edit_desa_id').val(firstId);
            }
          }, 'json');
        }
      });
      // Modal Hapus
      $(document).on('click', '.btnHapusLokasi', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#hapus_id').val(id);
        $('#hapus_nama').text(nama);
        $('#modalHapusLokasi').modal('show');
      });
    var map, marker;

    var mapTambah, mapEdit, markerTambah, markerEdit;
    function initMapTambah(lat, lng) {
      if(!mapTambah) {
        mapTambah = L.map('lokasiMapTambah').setView([lat, lng], 8);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19}).addTo(mapTambah);
      } else {
        mapTambah.setView([lat, lng], 8);
        if(markerTambah) { mapTambah.removeLayer(markerTambah); markerTambah = null; }
      }
      mapTambah.invalidateSize();
    }

    function enableMapPickTambah() {
      if(!mapTambah) return;
      mapTambah.off('click');
      mapTambah.on('click', function(e){
          var lat = e.latlng.lat.toFixed(6);
          var lng = e.latlng.lng.toFixed(6);
          $('input[name="latitude"]').val(lat);
          $('input[name="longitude"]').val(lng);
          if(markerTambah) mapTambah.removeLayer(markerTambah);
         // markerTambah = L.marker(e.latlng).addTo(mapTambah);
          markerTambah = L.marker(e.latlng, {
          icon: L.icon({
            iconUrl: '../assets/img/marker-icon.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowUrl: '../assets/img/marker-shadow.png',
            shadowSize: [41, 41]
          })
        }).addTo(mapTambah);
          markerTambah.bindPopup('Koordinat:<br>'+lat+', '+lng).openPopup();
      });
    }

    function initMapEdit(lat, lng) {
      if(!mapEdit) {
        mapEdit = L.map('lokasiMapEdit').setView([lat, lng], 8);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19}).addTo(mapEdit);
      } else {
        mapEdit.setView([lat, lng], 8);
        if(markerEdit) { mapEdit.removeLayer(markerEdit); markerEdit = null; }
      }
      mapEdit.invalidateSize();
    }

    function enableMapPickEdit() {
      if(!mapEdit) return;
      mapEdit.off('click');
      mapEdit.on('click', function(e){
          var lat = e.latlng.lat.toFixed(6);
          var lng = e.latlng.lng.toFixed(6);
          
          $('#edit_latitude').val(lat);
          $('#edit_longitude').val(lng);
          if(markerEdit) mapEdit.removeLayer(markerEdit);
         // markerEdit = L.marker(e.latlng).addTo(mapEdit);
          markerEdit = L.marker(e.latlng, {
            icon: L.icon({
              iconUrl: '../assets/img/marker-icon.png',
              iconSize: [25, 41],
              iconAnchor: [12, 41],
              popupAnchor: [1, -34],
              shadowUrl: '../assets/img/marker-shadow.png',
              shadowSize: [41, 41]
            })
          }).addTo(mapEdit);
          markerEdit.bindPopup('Koordinat:<br>'+lat+', '+lng).openPopup();
      });
    }
    // Modal Tambah
    $('#modalTambahLokasi').on('shown.bs.modal', function (e) {
      var lat = $('input[name="latitude"]').val() || latProvinsi;
      var lng = $('input[name="longitude"]').val() || lngProvinsi;
      lat = parseFloat(lat); lng = parseFloat(lng);
      setTimeout(function(){
        initMapTambah(lat, lng);
        enableMapPickTambah();
        if(lat && lng && lat != latProvinsi) {
          if(markerTambah) mapTambah.removeLayer(markerTambah);
          markerTambah = L.marker([lat, lng]).addTo(mapTambah);
          markerTambah.bindPopup('Koordinat:<br>'+lat+', '+lng).openPopup();
        }
      }, 350);
    });
    $('#modalTambahLokasi').on('hidden.bs.modal', function(){
      if(mapTambah){ mapTambah.remove(); mapTambah = null; markerTambah = null; }
      $('#lokasiMapTambah').html('');
    });

    // Modal Edit
    $('#modalEditLokasi').on('shown.bs.modal', function (e) {
      var lat = $('#edit_latitude').val() || latProvinsi;
      var lng = $('#edit_longitude').val() || lngProvinsi;
      var kategori_warna = $('#edit_kategori_warna').val() || 'violet';
      lat = parseFloat(lat); lng = parseFloat(lng);
      setTimeout(function(){
        initMapEdit(lat, lng);
        enableMapPickEdit();
        if(lat && lng && lat != latProvinsi) {
          if(markerEdit) mapEdit.removeLayer(markerEdit);
          markerEdit = L.marker([lat, lng], {
             icon: L.icon({
              iconUrl: '../assets/img/marker-icon-'+kategori_warna+'.png',
              iconSize: [25, 41],
              iconAnchor: [12, 41],
              popupAnchor: [1, -34],
              shadowUrl: '../assets/img/marker-shadow.png',
              shadowSize: [41, 41]
            })
          }).addTo(mapEdit);
          markerEdit.bindPopup('Koordinat:<br>'+lat+', '+lng).openPopup();
        }
      }, 350);
    });
    $('#modalEditLokasi').on('hidden.bs.modal', function(){
      if(mapEdit){ mapEdit.remove(); mapEdit = null; markerEdit = null; }
      $('#lokasiMapEdit').html('');
    });