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
        $('#KabupatenTable').DataTable({
          "autoWidth": false,

        });
      });
     $(document).ready(function() {
        $('#kecamatanTable').DataTable({
          "autoWidth": false,
          "order": [[ 3, "asc" ]],
          initComplete: function () {
            this.api().columns([3, 4]).every( function () {
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
      $(document).on('click', '.btnEditkecamatan', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#edit_id').val(id);
        $('#edit_nama').val(nama);
        $('#edit_kabupaten_id').val($(this).data('kabupaten-id'));
        $('#edit_kode').val($(this).data('kode'));
        $('#listDesa').empty();

        // Dapatkan polsek_id dan polres_id dari data-kecamatan (bisa save di db atau join di query)
        var polsek_id = $(this).data('polsek-id');
        var polres_id = $(this).data('polres-id');
        setEditPolresPolsek(polres_id, polsek_id);
        

        // Load desa seperti biasa
        $.getJSON("get_desa.php?kecamatan_id=" + id, function(data) {
          $.each(data, function(index, desa) {
            $('#listDesa').append('<li class="list-group-item"><span class="fw-bold badge bg-secondary">' + desa.kode + '</span> ' + desa.nama + '</li>');
          });
        });

        $('#modalEditkecamatan').modal('show');
      });
      function setEditPolresPolsek(polres_id, polsek_id) {
        $('#edit_polres_id').val(polres_id).trigger('change');
        // Setelah polres dipilih, fetch polsek AJAX
        $.get('get_polsek.php', {polres_id: polres_id}, function(polseks) {
          var htmlPolsek = '<option value="">Pilih Polsek</option>';
          $.each(polseks, function(i, v) {
            htmlPolsek += '<option value="'+v.id+'"'+(v.id==polsek_id?' selected':'')+'>'+v.nama+'</option>';
          });
          $('#edit_polsek_id').html(htmlPolsek);
          // Penting: set value setelah isi option!
          $('#edit_polsek_id').val(polsek_id);
        }, 'json');
      }
      // Ubah polsek ketika pilih polres:
      $('#edit_polres_id').on('change', function(){
        var polres_id = $(this).val();
        $('#edit_polsek_id').html('<option value="">Memuat...</option>');
        if(polres_id) {
          $.get('get_polsek.php', {polres_id:polres_id}, function(polseks){
            var html = '<option value="">Pilih Polsek</option>';
            $.each(polseks, function(i, v){
              html += '<option value="'+v.id+'">'+v.nama+'</option>';
            });
            $('#edit_polsek_id').html(html);
          },'json');
        } else {
          $('#edit_polsek_id').html('<option value="">Pilih Polsek</option>');
        }
      });

      // Handle tombol Hapus (isi nama di modal konfirmasi)
      $(document).on('click', '.btnHapuskecamatan', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#hapus_id').val(id);
        $('#hapus_nama').text(nama);
        $('#modalHapuskecamatan').modal('show');
      });