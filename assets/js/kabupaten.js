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
      // Handle tombol Edit (reload data di modal)
      $(document).on('click', '.btnEditKabupaten', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#edit_id').val(id);
        $('#edit_nama').val(nama);
        $('#edit_kode').val($(this).data('kode'));
        $('#edit_polres_id').val($(this).data('polres-id'));
        $('#modalEditKabupaten').modal('show');
        $('#listKecamatan').empty();
        $.getJSON('get_kecamatan.php?kabupaten_id=' + id, function(data) {
          $.each(data, function(i, k) {
            $('#listKecamatan').append('<li class="list-group-item"><span class="fw-bold badge bg-secondary">' + k.kode + '</span> ' + k.nama + '</li>');
          });
        });
      });

      // Handle tombol Hapus (isi nama di modal konfirmasi)
      $(document).on('click', '.btnHapusKabupaten', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#hapus_id').val(id);
        $('#hapus_nama').text(nama);
        $('#modalHapusKabupaten').modal('show');
      });