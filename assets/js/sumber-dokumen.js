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
        $('#PolresTable').DataTable({
          "autoWidth": false,
          "sorting": [[ 0, "desc" ]],
        });
      });
      // Handle tombol Edit (reload data di modal)
      $(document).on('click', '.btnEditSumber', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#edit_id').val(id);
        $('#edit_nama').val(nama);
        $('#edit_tipe').val($(this).data('tipe'));
        $('#edit_tahun').val($(this).data('tahun'));
        $('#edit_keterangan').val($(this).data('keterangan'));
        $('#modalEditSumber').modal('show');
      });

      // Handle tombol Hapus (isi nama di modal konfirmasi)
      $(document).on('click', '.btnHapusSumber', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#hapus_id').val(id);
        $('#hapus_nama').text(nama);
        $('#modalHapusSumber').modal('show');
      });