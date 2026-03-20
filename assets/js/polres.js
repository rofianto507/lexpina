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
    autoWidth:false
  });
});

$(document).on('click', '.btnEditPolres', function() {
  var id = $(this).data('id');
  var nama = $(this).data('nama');

  $('#edit_id').val(id);
  $('#edit_nama').val(nama);

  $('#listPolsek').html('<li>Loading...</li>');

  $.get('get_polsek.php',{polres_id:id},function(res){
    if(Array.isArray(res) && res.length>0){
      var html=res.map(function(p){
        return '<li class="list-group-item">'+p.nama+'</li>';
      }).join('');
      $('#listPolsek').html(html);
    }else{
      $('#listPolsek').html('<li class="list-group-item"><em>Tidak ada polsek</em></li>');
    }
  });

  $('#modalEditPolres').modal('show');
});

$(document).on('click', '.btnHapusPolres', function() {
  var id = $(this).data('id');
  var nama = $(this).data('nama');

  $('#hapus_id').val(id);
  $('#hapus_nama').text(nama);

  $('#modalHapusPolres').modal('show');
});