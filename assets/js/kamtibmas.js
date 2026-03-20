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
      $('#kamtibmasTable').DataTable({
        "autoWidth": false,
        "order": [[ 0, "desc" ]],
        "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ]
        
      });
    });
      var polsekId = $('body').data('polsek-id') || '';
 
      
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
      const polresId = this.value;
     
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
    // Hapus kamtibmas
    $(document).on('click', '.btnHapusKamtibmas', function() {
      var id = $(this).data('id');
      var permasalahan = $(this).data('permasalahan');
      $('#hapus_id_kamtibmas').val(id);
      $('#hapus_permasalahan').text(permasalahan);
      $('#modalHapusKamtibmas').modal('show');
    });