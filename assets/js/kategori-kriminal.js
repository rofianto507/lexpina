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
        $('#kategoriTable').DataTable({
          "autoWidth": false,

        });
      });
      // Handle tombol Edit (reload data di modal)
      $(document).on('click', '.btnEditKategori', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#edit_id').val(id);
        $('#edit_nama').val(nama);
        $('#modalEditKategori').modal('show');
      });

      // Handle tombol Hapus (isi nama di modal konfirmasi)
      $(document).on('click', '.btnHapusKategori', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#hapus_id').val(id);
        $('#hapus_nama').text(nama);
        $('#modalHapusKategori').modal('show');
      });