var polsekId = $('body').data('polsek-id') || '';
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
 // Form edit: filter sub kategori sesuai kategori_id baru
  $('select[name="kategori_id"]').change(function() {
    var kategoriId = $(this).val();
    $('#sub_kategori_id').prop('disabled', true).html('<option value="">- Pilih Sub Kategori -</option>');
    if(kategoriId) {
      $.get('get_sub_kategori.php', {kategori_id: kategoriId}, function(data) {
        var opt = '<option value="">- Pilih Sub Kategori -</option>';
        $.each(data, function(i, v) {
          opt += '<option value="'+v.id+'">'+v.nama+'</option>';
        });
        $('#sub_kategori_id').html(opt).prop('disabled', false);
      }, 'json');
    }
  });
  // Kabupaten → Kecamatan
  $('#kabupaten_id').change(function() {
    var kabupatenId = $(this).val();
   
    $('#kecamatan_id').html('<option value="">- Pilih Kecamatan -</option>');
    $('#desa_id').html('<option value="">- Pilih Desa -</option>');
    if(kabupatenId) {
      $.get('get_kecamatan.php', {kabupaten_id: kabupatenId, polsek_id: polsekId}, function(data) {
        var opt = '<option value="">- Pilih Kecamatan -</option>';
        var firstId = "";
        $.each(data, function(i, v) {
          if(i===0) firstId = v.id;
          opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>';
        });
        $('#kecamatan_id').html(opt);
        if(firstId) {
          $('#kecamatan_id').val(firstId).trigger('change');
        }
      }, 'json');
    }
  });

  // Kecamatan → Desa
  $('#kecamatan_id').change(function() {
    var kecamatanId = $(this).val();
    $('#desa_id').html('<option value="">- Pilih Desa -</option>');
    if(kecamatanId) {
      $.get('get_desa.php', {kecamatan_id: kecamatanId}, function(data) {
        var opt = '<option value="">- Pilih Desa -</option>';
        var firstId = "";
        $.each(data, function(i, v) {
          if(i===0) firstId = v.id;
          opt += '<option value="'+v.id+'">'+v.kode+' - '+v.nama+'</option>';
        });
        $('#desa_id').html(opt);
        if(firstId) {
          $('#desa_id').val(firstId);
        }
      }, 'json');
    }
  });
  // Polres → Polsek
  $('#polres_id').change(function() {
    var polresId = $(this).val();
 
    $('#polsek_id').html('<option value="">- Pilih Polsek -</option>');
    if(polresId) {
      $.get('get_polsek.php', {polres_id: polresId, polsek_id: polsekId}, function(data) {
        var opt = '<option value="">- Pilih Polsek -</option>';
        var firstId = "";
        $.each(data, function(i, v) {
          if(i===0) firstId = v.id;
          opt += '<option value="'+v.id+'">'+v.nama+'</option>';
        });
        $('#polsek_id').html(opt);
        if(firstId) {
          $('#polsek_id').val(firstId);
        }
      }, 'json');
    }
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

    function updateSubState(selectedSubState = null) {
        const state = document.querySelector('select[name="state"]').value;
        const subStateSel = document.getElementById('sub_state');
        subStateSel.innerHTML = ""; // clear

        if (subStateOptions[state]) {
            subStateOptions[state].forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.label;
                if (selectedSubState == opt.value) option.selected = true;
                subStateSel.appendChild(option);
            });
        } else {
            subStateSel.innerHTML = '<option value="">- Pilih Sub State -</option>';
        }
    }

    // Trigger on page load and when state changes
    document.addEventListener('DOMContentLoaded', function() {
        // On edit, isi default sesuai database
        let initialSubState = "<?=htmlspecialchars($data['sub_state'] ?? '')?>";
        updateSubState(initialSubState);
    });
    document.querySelector('select[name="state"]').addEventListener('change', function(){
        updateSubState();
    });
      

   var map, marker;
   var mapEdit, markerEdit;
   function initMapEdit(lat, lng) {
      if(!mapEdit) {
        mapEdit = L.map('kriminalitasMapEdit').setView([lat, lng], 8);
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
          $('#latitude').val(lat);
          $('#longitude').val(lng);
          if(markerEdit) mapEdit.removeLayer(markerEdit);
          //markerEdit = L.marker(e.latlng).addTo(mapEdit);
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
      $('#latitude').change(function(){
        var lat = $(this).val();
        var lng = $('#longitude').val();
        if(markerEdit) mapEdit.removeLayer(markerEdit);
        markerEdit = L.marker([lat, lng], {
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
      $('#longitude').change(function(){
        var lng = $(this).val();
        var lat = $('#latitude').val();
        if(markerEdit) mapEdit.removeLayer(markerEdit);
        markerEdit = L.marker([lat, lng], {
          icon: L.icon({
            iconUrl: '../assets/img/marker-icon.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowUrl: '../assets/img/marker-shadow.png',
            shadowSize: [41, 41]
          })        }).addTo(mapEdit);
        markerEdit.bindPopup('Koordinat:<br>'+lat+', '+lng).openPopup();
      });
   $(document).ready(function() {
    var lat = $('#latitude').val() || latProvinsi;
    var lng = $('#longitude').val() || lngProvinsi;
    lat = parseFloat(lat); lng = parseFloat(lng);

    console.log("Inisialisasi map dengan koordinat:", lat, lng);

    setTimeout(function(){
        initMapEdit(lat, lng);
        enableMapPickEdit();

        // PATCH: Marker akan selalu muncul jika lat lng valid
        if(!isNaN(lat) && !isNaN(lng)) {
            if(markerEdit) mapEdit.removeLayer(markerEdit);
            markerEdit = L.marker([lat, lng], {
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
            // Set input jika kosong
            if($('#latitude').val() === "") $('#latitude').val(lat);
            if($('#longitude').val() === "") $('#longitude').val(lng);
        } else {
            console.log("Marker tidak ditampilkan: lat/lng invalid!");
        }
    }, 350);
});
$(document).ready(function() {
  // Inisialisasi: tampilkan/hidden field tanggal selesai sesuai value saat load
  function showHideTanggalSelesai() {
    var state = $('#state').val();
    var $tgl = $('#tanggal_selesai');
    var $wrap = $('#formTanggalSelesai');
    if(state === 'SELESAI') {
      $wrap.show();
      // Jika kosong, isi default hari ini
      if(!$tgl.val()) {
        let now = new Date();
        $tgl.val(now.toISOString().slice(0,16)); // "YYYY-MM-DDTHH:mm"
      }
    } else {
      $wrap.hide();
      $tgl.val('');
    }
  }

  $('#state').on('change', showHideTanggalSelesai);

  // Panggil saat first load
  showHideTanggalSelesai();
});      