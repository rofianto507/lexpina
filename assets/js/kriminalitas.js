var polsekId = $('body').data('polsek-id') || '';
var latProvinsi = parseFloat($('body').data('lat-provinsi'));
var lngProvinsi = parseFloat($('body').data('lng-provinsi'));
 // Onload & onChange state
document.addEventListener('DOMContentLoaded', updateSubState);
document.getElementById('state').addEventListener('change', updateSubState);

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
      $('#kriminalitasTable').DataTable({
        "autoWidth": false,
        "order": [[ 0, "desc" ]],
        "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
        initComplete: function () {
            this.api().columns([2,3,6,7,9,10]).every( function () {
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
    const subKategoriSelect = document.getElementById('sub_kategori_id');
    const subKategoriChoices = new Choices(subKategoriSelect, {
      removeItemButton: true,
      placeholder: true,
      searchEnabled: true
    });
    $('#kategori_id').change(function() {
      var kategoriId = $(this).val();      
      subKategoriChoices.clearStore(); // hapus data di Choices
      subKategoriChoices.clearChoices();
      subKategoriChoices.setChoices(
        [ { value: '', label: '- Pilih Sub Kategori -', selected: true, disabled: false } ],
        'value', 'label', false
      );
      $('#sub_kategori_id').prop('disabled', true);

      if(kategoriId) {
        $.get('get_sub_kategori.php', {kategori_id: kategoriId}, function(data) {
          // Format sesuai Choices
          let list = data.map(v => ({ value: v.id, label: v.nomor_urut + '. ' + v.nama, selected: false }));
          // Tetap tambahkan opsi placeholder:
          list.unshift({ value: '', label: '- Pilih Sub Kategori -', selected: true, disabled: false });
          subKategoriChoices.setChoices(list, 'value', 'label', true);
          $('#sub_kategori_id').prop('disabled', false);
        }, 'json');
      }
    });
     $('#kabupaten_id').change(function() {
      var kabupatenId = $(this).val();
 
      $('#kecamatan_id').prop('disabled', true).html('<option value="">- Pilih Kecamatan -</option>');
      $('#desa_id').prop('disabled', true).html('<option value="">- Pilih Desa -</option>');
      if(kabupatenId) {
        $.get('get_kecamatan.php', {kabupaten_id: kabupatenId, polsek_id: polsekId}, function(data) {
          var opt = '<option value="">- Pilih Kecamatan -</option>';
          var firstId = ""; // simpan id kecamatan pertama
          $.each(data, function(i, v) {
            if(i === 0) firstId = v.id;
            opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>';
          });
          $('#kecamatan_id').html(opt).prop('disabled', false);
          // **Pilih otomatis kecamatan pertama jika ada**
          if(firstId) {
            $('#kecamatan_id').val(firstId).trigger('change');
          }
        }, 'json');
      }
    });
    $('#kecamatan_id').change(function() {
      var kecamatanId = $(this).val();
      $('#desa_id').prop('disabled', true).html('<option value="">- Pilih Desa -</option>');
      if(kecamatanId) {
        $.get('get_desa.php', {kecamatan_id: kecamatanId}, function(data) {
          var opt = '<option value="">- Pilih Desa -</option>';
          var firstId = "";
          $.each(data, function(i, v) {
            if(i === 0) firstId = v.id;
            opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>';
          });
          $('#desa_id').html(opt).prop('disabled', false);
          // **Pilih otomatis desa pertama jika ada**
          if(firstId) {
            $('#desa_id').val(firstId);
          }
        }, 'json');
      }
    });
    // Polres → Polsek
    $('#polres_id').change(function() {
      var polresId = $(this).val();
 
      $('#polsek_id').prop('disabled', true).html('<option value="">- Pilih Polsek -</option>');
      if(polresId) {
        $.get('get_polsek.php', {polres_id: polresId, polsek_id: polsekId}, function(data) {
          var opt = '<option value="">- Pilih Polsek -</option>';
          var firstId = "";
          $.each(data, function(i, v) {
            if(i === 0) firstId = v.id;
            opt += '<option value="'+v.id+'">'+v.nama+'</option>';
          });
          $('#polsek_id').html(opt).prop('disabled', false);
          // **Pilih otomatis polsek pertama jika ada**
          if(firstId) {
            $('#polsek_id').val(firstId);
          }
        }, 'json');
      }
    });
     // ============================================
// CASCADE: Modal Import LP A
// ============================================
$('#impA_kategori_id').change(function() {
    var kategoriId = $(this).val();
    $('#impA_sub_kategori_id').prop('disabled', true).html('<option value="">- Pilih Sub Kategori -</option>');
    if(kategoriId) {
        $.get('get_sub_kategori.php', {kategori_id: kategoriId}, function(data) {
            var opt = '<option value="">- Pilih Sub Kategori -</option>';
            $.each(data, function(i, v) { opt += '<option value="'+v.id+'">'+v.nama+'</option>'; });
            $('#impA_sub_kategori_id').html(opt).prop('disabled', false);
        }, 'json');
    }
});

$('#impA_kabupaten_id').change(function() {
    var kabupatenId = $(this).val();
  
    $('#impA_kecamatan_id').prop('disabled', true).html('<option value="">- Pilih Kecamatan -</option>');
    $('#impA_desa_id').prop('disabled', true).html('<option value="">- Pilih Desa -</option>');
    if(kabupatenId) {
        $.get('get_kecamatan.php', {kabupaten_id: kabupatenId, polsek_id: polsekId}, function(data) {
            var opt = '<option value="">- Pilih Kecamatan -</option>';
            var firstId = "";
            $.each(data, function(i, v) { if(i===0) firstId=v.id; opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>'; });
            $('#impA_kecamatan_id').html(opt).prop('disabled', false);
            if(firstId) $('#impA_kecamatan_id').val(firstId).trigger('change');
        }, 'json');
    }
});

$('#impA_kecamatan_id').change(function() {
    var kecamatanId = $(this).val();
    $('#impA_desa_id').prop('disabled', true).html('<option value="">- Pilih Desa -</option>');
    if(kecamatanId) {
        $.get('get_desa.php', {kecamatan_id: kecamatanId}, function(data) {
            var opt = '<option value="">- Pilih Desa -</option>';
            var firstId = "";
            $.each(data, function(i, v) { if(i===0) firstId=v.id; opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>'; });
            $('#impA_desa_id').html(opt).prop('disabled', false);
            if(firstId) $('#impA_desa_id').val(firstId);
        }, 'json');
    }
});

$('#impA_polres_id').change(function() {
    var polresId = $(this).val();
   
    $('#impA_polsek_id').prop('disabled', true).html('<option value="">- Pilih Polsek -</option>');
    if(polresId) {
        $.get('get_polsek.php', {polres_id: polresId, polsek_id: polsekId}, function(data) {
            var opt = '<option value="">- Pilih Polsek -</option>';
            var firstId = "";
            $.each(data, function(i, v) { if(i===0) firstId=v.id; opt += '<option value="'+v.id+'">'+v.nama+'</option>'; });
            $('#impA_polsek_id').html(opt).prop('disabled', false);
            if(firstId) $('#impA_polsek_id').val(firstId);
        }, 'json');
    }
});

    // ============================================
      // CASCADE: Modal Import Word (BARU)
      // ============================================
      $('#imp_kategori_id').change(function() {
        var kategoriId = $(this).val();
        $('#imp_sub_kategori_id').prop('disabled', true).html('<option value="">- Pilih Sub Kategori -</option>');
        if(kategoriId) {
          $.get('get_sub_kategori.php', {kategori_id: kategoriId}, function(data) {
            var opt = '<option value="">- Pilih Sub Kategori -</option>';
            $.each(data, function(i, v) { opt += '<option value="'+v.id+'">'+v.nama+'</option>'; });
            $('#imp_sub_kategori_id').html(opt).prop('disabled', false);
          }, 'json');
        }
      });

      $('#imp_kabupaten_id').change(function() {
        var kabupatenId = $(this).val();
   
        $('#imp_kecamatan_id').prop('disabled', true).html('<option value="">- Pilih Kecamatan -</option>');
        $('#imp_desa_id').prop('disabled', true).html('<option value="">- Pilih Desa -</option>');
        if(kabupatenId) {
          $.get('get_kecamatan.php', {kabupaten_id: kabupatenId, polsek_id: polsekId}, function(data) {
            var opt = '<option value="">- Pilih Kecamatan -</option>';
            var firstId = "";
            $.each(data, function(i, v) { if(i===0) firstId=v.id; opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>'; });
            $('#imp_kecamatan_id').html(opt).prop('disabled', false);
            if(firstId) $('#imp_kecamatan_id').val(firstId).trigger('change');
          }, 'json');
        }
      });

      $('#imp_kecamatan_id').change(function() {
        var kecamatanId = $(this).val();
        $('#imp_desa_id').prop('disabled', true).html('<option value="">- Pilih Desa -</option>');
        if(kecamatanId) {
          $.get('get_desa.php', {kecamatan_id: kecamatanId}, function(data) {
            var opt = '<option value="">- Pilih Desa -</option>';
            var firstId = "";
            $.each(data, function(i, v) { if(i===0) firstId=v.id; opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>'; });
            $('#imp_desa_id').html(opt).prop('disabled', false);
            if(firstId) $('#imp_desa_id').val(firstId);
          }, 'json');
        }
      });

      $('#imp_polres_id').change(function() {
        var polresId = $(this).val();
  
        $('#imp_polsek_id').prop('disabled', true).html('<option value="">- Pilih Polsek -</option>');
        if(polresId) {
          $.get('get_polsek.php', {polres_id: polresId, polsek_id: polsekId}, function(data) {
            var opt = '<option value="">- Pilih Polsek -</option>';
            var firstId = "";
            $.each(data, function(i, v) { if(i===0) firstId=v.id; opt += '<option value="'+v.id+'">'+v.nama+'</option>'; });
            $('#imp_polsek_id').html(opt).prop('disabled', false);
            if(firstId) $('#imp_polsek_id').val(firstId);
          }, 'json');
        }
      });
 $('#fileWordImport').change(function() {
        $('#btnParseWord').prop('disabled', !this.files.length);
        // Reset
        $('#stepForm').hide();
        $('#stepUpload').show();
        $('#parseError').hide();
      });

      $('#btnParseWord').click(function() {
        var fileInput = $('#fileWordImport')[0];
        if(!fileInput.files.length) return;

        var formData = new FormData();
        formData.append('file_word', fileInput.files[0]);

        $('#parseLoading').show();
        $('#parseError').hide();
        $('#btnParseWord').prop('disabled', true);

        $.ajax({
          url: 'parse_word_lp.php',
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          dataType: 'json',
          success: function(response) {
            
            $('#parseLoading').hide();
            $('#btnParseWord').prop('disabled', false);

            if(response.error) {
              $('#parseError').text(response.error).show();
              return;
            }

            if(response.success && response.data) {
              var d = response.data;
              console.log("Data parsed from Word:", d);
              // Format tanggal kejadian
              if(d.waktu_kejadian_formatted) {
                $('#imp_waktu_kejadian').val(d.waktu_kejadian_formatted);
              }
              // Isi field otomatis dari hasil parse
              $('#imp_no_lp').val(d.no_lp || '');
              $('#imp_pelapor').val(d.pelapor || '');
              $('#imp_lokasi').val(d.tempat_kejadian || '');
              $('#imp_keterangan').val(d.apa_yang_terjadi || '');
              $('#imp_tindak_pidana').val(d.tindak_pidana || '');
              $('#imp_terlapor').val(d.terlapor || '');
              $('#imp_korban').val(d.korban || '');
              $('#imp_latitude').val(d.latitude || '');
              $('#imp_longitude').val(d.longitude || '');
              $('#imp_saksi').val(d.saksi || '');
              $('#imp_barang_bukti').val(d.barang_bukti || '');
              $('#imp_uraian').val(d.uraian || '');

              // Tanggal laporan
              if(d.tanggal_laporan_formatted) {
                $('#imp_tanggal_laporan').val(d.tanggal_laporan_formatted);
              }

              // Tampilkan form
              $('#stepUpload').hide();
              $('#stepForm').show();
            }
          },
          error: function(xhr, status, errorMsg) {
            $('#parseLoading').hide();
            $('#btnParseWord').prop('disabled', false);

            var detail = 'Terjadi kesalahan saat memproses file.';
            try {
                var resp = JSON.parse(xhr.responseText);
                if(resp.error) {
                    detail = resp.error;
                    if(resp.file) detail += '\nFile: ' + resp.file;
                    if(resp.line) detail += '\nLine: ' + resp.line;
                }
            } catch(e) {
                if(xhr.responseText) {
                    detail += '\n\nServer response:\n' + xhr.responseText.substring(0, 500);
                }
                detail += '\nHTTP Status: ' + status + ' ' + errorMsg;
            }

            $('#parseError').html('<strong>Error:</strong><pre>' + 
                $('<span>').text(detail).html() + '</pre>').show();
        }
        });
      });

      // Tombol Upload Ulang
      $('#btnBackToUpload').click(function() {
        $('#stepForm').hide();
        $('#stepUpload').show();
        $('#fileWordImport').val('');
        $('#btnParseWord').prop('disabled', true);
      });
 const subStateOptions = {
      "PROSES": [
        {value:"PROSES LIDIK", label:"PROSES LIDIK"},
        {value:"PROSES SIDIK", label:"PROSES SIDIK"}
      ],
      "SELESAI": [
        {value:"P21", label:"P21"},
        {value:"SP3 - TDK CUKUP BUKTI", label:"SP3 - TDK CUKUP BUKTI"},
        {value:"SP3 - BUKAN PKR PIDANA", label:"SP3 - BUKAN PKR PIDANA"},
        {value:"SP3 - ADUAN DICABUT", label:"SP3 - ADUAN DICABUT"},
        {value:"SP3 - NEBIS IN IDEM", label:"SP3 - NEBIS IN IDEM"},
        {value:"SP3 - TSK MATI", label:"SP3 - TSK MATI"},
        {value:"SP3 - TSK GILA", label:"SP3 - TSK GILA"},
        {value:"SP3 - KADALUARSA/LIMPAH", label:"SP3 - KADALUARSA/LIMPAH"},
        {value:"DILIMPAHKAN INSTANSI LAIN", label:"DILIMPAHKAN INSTANSI LAIN"},
        {value:"RESORATIF JUSTICE", label:"RESORATIF JUSTICE"}
      ]
    };
    function updateImportSubState() {
        var state = $('#imp_state').val();
        var sel = $('#imp_sub_state');
        sel.empty();
        if(subStateOptions[state]) {
          subStateOptions[state].forEach(function(opt) {
            sel.append('<option value="'+opt.value+'">'+opt.label+'</option>');
          });
        }
      }
      updateImportSubState();
      $('#imp_state').change(updateImportSubState);
    function updateSubState() {
      const state = document.getElementById('state').value;
      const subStateSel = document.getElementById('sub_state');
      subStateSel.innerHTML = ""; // clear opsi lama
      if (subStateOptions[state]) {
        subStateOptions[state].forEach(opt => {
          const o = document.createElement('option');
          o.value = opt.value; o.textContent = opt.label;
          subStateSel.appendChild(o);
        });
      } else {
        subStateSel.innerHTML = '<option value="">- tidak tersedia -</option>';
      }
    }    

    // ============================================
  // IMPORT LP A: Parse File
  // ============================================
  $('#fileWordImportA').change(function() {
      $('#btnParseWordA').prop('disabled', !this.files.length);
      $('#stepFormA').hide();
      $('#stepUploadA').show();
      $('#parseErrorA').hide();
  });

  $('#btnParseWordA').click(function() {
      var fileInput = $('#fileWordImportA')[0];
      if(!fileInput.files.length) return;

      var formData = new FormData();
      formData.append('file_word', fileInput.files[0]);

      $('#parseLoadingA').show();
      $('#parseErrorA').hide();
      $('#btnParseWordA').prop('disabled', true);

      $.ajax({
          url: 'parse_word_lp_a.php',
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          dataType: 'json',
          success: function(response) {
              $('#parseLoadingA').hide();
              $('#btnParseWordA').prop('disabled', false);

              if(response.error) {
                  $('#parseErrorA').text(response.error).show();
                  return;
              }

              if(response.success && response.data) {
                  var d = response.data;
                  console.log(d);
                  // Data otomatis
                  $('#impA_no_lp').val(d.no_lp || '');
                  $('#impA_lokasi').val(d.tempat_kejadian || '');
                  $('#impA_keterangan').val(d.apa_yang_terjadi || '');
                  $('#impA_terlapor').val(d.terlapor || '');
                  $('#impA_korban').val(d.korban || '');
                  $('#impA_latitude').val(d.latitude || '');
                  $('#impA_longitude').val(d.longitude || '');
                  $('#impA_pelapor').val(d.pelapor || '');

                   
                  if(d.tanggal_formatted) {
                      $('#impA_tanggal').val(d.tanggal_formatted);
                  }

                  // Tanggal laporan
                  if(d.tanggal_laporan_formatted) {
                      $('#impA_tanggal_laporan').val(d.tanggal_laporan_formatted);
                  }

                  // Bagaimana terjadi → uraian kronologi
                  // Gabungkan "bagaimana_terjadi" + "uraian" dari tabel bawah
                  var kronologi = d.bagaimana_terjadi || '';
                  $('#impA_uraian_kronologi').val(kronologi);

                  // Tabel bawah
                  $('#impA_tindak_pidana').val(d.tindak_pidana || '');
                  $('#impA_saksi').val(d.saksi || '');
                  $('#impA_barang_bukti').val(d.barang_bukti || '');
                  $('#impA_uraian_singkat').val(d.uraian || '');

                  // Tampilkan form
                  $('#stepUploadA').hide();
                  $('#stepFormA').show();
              }
          },
          error: function(xhr, status, errorMsg) {
            $('#parseLoadingA').hide();
            $('#btnParseWordA').prop('disabled', false);

            var detail = 'Terjadi kesalahan saat memproses file.';
            try {
                var resp = JSON.parse(xhr.responseText);
                if(resp.error) {
                    detail = resp.error;
                    if(resp.file) detail += '\nFile: ' + resp.file;
                    if(resp.line) detail += '\nLine: ' + resp.line;
                }
            } catch(e) {
                if(xhr.responseText) {
                    detail += '\n\nServer response:\n' + xhr.responseText.substring(0, 500);
                }
                detail += '\nHTTP Status: ' + status + ' ' + errorMsg;
            }

            $('#parseErrorA').html('<strong>Error:</strong><pre>' + 
                $('<span>').text(detail).html() + '</pre>').show();
          }
      });
  });
    // Tombol Upload Ulang
  $('#btnBackToUploadA').click(function() {
      $('#stepFormA').hide();
      $('#stepUploadA').show();
      $('#fileWordImportA').val('');
      $('#btnParseWordA').prop('disabled', true);
  });
  // Sub State LP A
  function updateImportASubState() {
      var state = $('#impA_state').val();
      var sel = $('#impA_sub_state');
      sel.empty();
      if(subStateOptions[state]) {
          subStateOptions[state].forEach(function(opt) {
              sel.append('<option value="'+opt.value+'">'+opt.label+'</option>');
          });
      }
  }  
   
  updateImportASubState();
    $('#latitude').change(function(){
        var lat = $(this).val();
        var lng = $('#longitude').val();
        if(markerTambah) mapTambah.removeLayer(markerTambah);
        markerTambah = L.marker([lat, lng], {
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
      $('#longitude').change(function(){
        var lng = $(this).val();
        var lat = $('#latitude').val();
        if(markerTambah) mapTambah.removeLayer(markerTambah);
        markerTambah = L.marker([lat, lng], {
          icon: L.icon({
            iconUrl: '../assets/img/marker-icon.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowUrl: '../assets/img/marker-shadow.png',
            shadowSize: [41, 41]
          })        }).addTo(mapTambah);
        markerTambah.bindPopup('Koordinat:<br>'+lat+', '+lng).openPopup();
      });

  $('#impA_state').change(updateImportASubState);
     var map, marker;
    var mapTambah, mapEdit, markerTambah, markerEdit;
    function initMapTambah(lat, lng) {
      if(!mapTambah) {
        mapTambah = L.map('kriminalitasMapTambah').setView([lat, lng], 8);
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
          //markerTambah = L.marker(e.latlng).addTo(mapTambah);
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
    // Hapus kriminalitas
    $(document).on('click', '.btnHapuskriminalitas', function() {
      var id = $(this).data('id');
      var subKategori = $(this).data('sub-kategori');
      $('#hapus_id_kriminalitas').val(id);
      $('#modalHapuskriminalitas').modal('show');
    });
   
     // Modal Tambah
    $('#modalTambahkriminalitas').on('shown.bs.modal', function (e) {
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
    $('#modalTambahkriminalitas').on('hidden.bs.modal', function(){
      if(mapTambah){ mapTambah.remove(); mapTambah = null; markerTambah = null; }
      $('#kriminalitasMapTambah').html('');
    });
    function loadLpStatistik( 
      kategoriFilter = [], 
      tahun = new Date().getFullYear(), 
      level = 'provinsi',
      polres_id = '',
      polsek_id = '') {
       // let url = 'get_statistik_lp.php';    
        let url = `get_statistik_lp.php?tahun=${tahun}&level=${level}&polres_id=${polres_id}&polsek_id=${polsek_id}`;
        fetch(url)
          .then(res => res.json())
          .then(stat => {
            // Ganti label
            document.getElementById('stat-label-1').innerText = 'LP A';
            document.getElementById('stat-label-2').innerText = 'LP B';
            document.getElementById('stat-label-3').innerText = 'Lainnya';
            // Isi value
            document.getElementById('stat-value-1').innerText = Number(stat.total_lp_a).toLocaleString('id-ID');
            document.getElementById('stat-value-2').innerText = Number(stat.total_lp_b).toLocaleString('id-ID');
            document.getElementById('stat-value-3').innerText = Number(stat.total_lp_plainnya).toLocaleString('id-ID');
          })
          .catch(err => {
              // Isi fallback kalau error
              document.getElementById('stat-value-1').innerText = '0';
              document.getElementById('stat-value-2').innerText = '0';
              document.getElementById('stat-value-3').innerText = '0';
            });
      }
      // Load statistik saat halaman siap
    document.addEventListener('DOMContentLoaded', function() {
      loadLpStatistik();
    });
    fetch('get_polres.php')
      .then(r => r.json())
      .then(list => {
        const sel = document.getElementById('filter-polres');
        list.forEach(row => {
          let opt = document.createElement('option');
          opt.value = row.id;
          opt.innerText = row.nama;
          sel.appendChild(opt);
        });
      });
    // Event: Load polsek setiap kali polres diganti
    document.getElementById('filter-polres').addEventListener('change', function() {
      const polresId = this.value;
      const polsekSel = document.getElementById('filter-polsek');
      polsekSel.innerHTML = '<option value="">-- Pilih Polsek --</option>';
      polsekSel.disabled = true;
      if(polresId) {
        fetch(`get_polsek.php?polres_id=${polresId}`)
          .then(r => r.json())
          .then(list => {
            list.forEach(row => {
              let opt = document.createElement('option');
              opt.value = row.id;
              opt.innerText = row.nama;
              polsekSel.appendChild(opt);
            });
            polsekSel.disabled = false;
          });
      }
      // Set ulang filter statistik
      loadLpStatistik([], new Date().getFullYear(), 'polres', polresId, '');
    });

    // Event: Ubah statistik jika polsek dipilih
    document.getElementById('filter-polsek').addEventListener('change', function() {
      const polresId = document.getElementById('filter-polres').value;
      const polsekId = this.value;
      loadLpStatistik([], new Date().getFullYear(), 'polsek', polresId, polsekId);
    });

    $(document).ready(function(){
      // Saat modal tambah dibuka: reset state dan hide tanggal_selesai
      $('#formTambahkriminalitas').on('reset', function(){
        $('#formTanggalSelesai').hide();
        $('#tanggal_selesai').val('');
      });

      // Cek saat select state berubah
      $('#state').on('change', function(){
        if($(this).val() === 'SELESAI') {
          $('#formTanggalSelesai').show();

          // Isi default hari ini jika kosong
          if(!$('#tanggal_selesai').val()) {
            let now = new Date();
            let str = now.toISOString().slice(0,16); // 'YYYY-MM-DDTHH:mm'
            $('#tanggal_selesai').val(str);
          }
        } else {
          $('#formTanggalSelesai').hide();
          $('#tanggal_selesai').val('');
        }
      });

      // Trigger dahulu untuk kondisi default
      $('#state').trigger('change');
    });