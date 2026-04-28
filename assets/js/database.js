     
    $(document).ready(function() {
      $('#dokumenTable').DataTable({
        "autoWidth": false,
        "order": [[ 0, "desc" ]],
        "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ]
        
      });
    });
 
    // Hapus dokumen
    $(document).on('click', '.btnHapusDokumen', function() {
      var id = $(this).data('id');
      var judul = $(this).data('judul');
      $('#hapus_id_dokumen').val(id);
      $('#hapus_judul').text(judul);
      $('#modalHapusDokumen').modal('show');
    });