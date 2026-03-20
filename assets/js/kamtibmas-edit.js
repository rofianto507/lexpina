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
   var polsekId = $('body').data('polsek-id') || '';   
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