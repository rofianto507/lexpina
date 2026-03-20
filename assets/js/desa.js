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
        $('#desaTable').DataTable({
          "autoWidth": false,
          "order": [[ 4, "asc" ]],
          initComplete: function () {
            this.api().columns([4]).every( function () {
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
      $(document).on('click', '.btnEditdesa', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#edit_id').val(id);
        $('#edit_jenis').val($(this).data('jenis'));
        $('#edit_nama').val(nama);
        $('#edit_kecamatan_id').val($(this).data('kecamatan-id'));
        $('#edit_kode').val($(this).data('kode'));
        $('#modalEditdesa').modal('show');
      });

      // Handle tombol Hapus (isi nama di modal konfirmasi)
      $(document).on('click', '.btnHapusdesa', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#hapus_id').val(id);
        $('#hapus_nama').text(nama);
        $('#modalHapusdesa').modal('show');
      });