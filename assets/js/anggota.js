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

      document.getElementById('togglePassword').addEventListener('click', function () {
          var passInput = document.getElementById('password_inp');
          var icon = document.getElementById('togglePasswordIcon');
          if (passInput.type === 'password') {
              passInput.type = 'text';
              icon.classList.remove('fa-eye-slash');
              icon.classList.add('fa-eye');
          } else {
              passInput.type = 'password';
              icon.classList.remove('fa-eye');
              icon.classList.add('fa-eye-slash');
          }
      });

     document.getElementById('password_inp').addEventListener('keyup', function() {
        var val = this.value;
        var strength = 0;
        if(val.length >= 8) strength++;
        if(/[A-Z]/.test(val)) strength++;
        if(/[a-z]/.test(val)) strength++;
        if(/[0-9]/.test(val)) strength++;
        if(/[\W_]/.test(val)) strength++;

        var text = "Password lemah!";
        var color = "text-danger";
        if(strength == 5) {
            text = "Password sangat kuat";
            color = "text-success";
        } else if (strength >= 4) {
            text = "Password kuat";
            color = "text-primary";
        } else if (strength >= 3) {
            text = "Password sedang";
            color = "text-warning";
        }
        var el = document.getElementById('passStrength');
        el.textContent = text;
        el.className = color + ' mt-1';
    }); 
    $(document).ready(function() {
        $('#anggotaTable').DataTable({
          "autoWidth": false,
          "order": [[ 0, "desc" ]],
          initComplete: function () {
            this.api().columns([3,4,5]).every( function () {
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
        $('select[name="akses_inp"]').on('change', function() {
          var akses = $(this).val();

          if(akses === 'POLRES') {

            $('#groupPolres').removeClass('d-none');
            $('#groupPolsek').addClass('d-none');

            $('#polres_idInp').prop('required', true);
            $('#polsek_idInp').val('');
            $('#polsek_idInp').prop('required', false);

          } 
          else if(akses === 'POLSEK') {

            $('#groupPolres').removeClass('d-none');
            $('#groupPolsek').removeClass('d-none');

            $('#polres_idInp').prop('required', true);
            $('#polsek_idInp').prop('required', true);

            $('#polsek_idInp').html('<option value="">- Pilih Polsek -</option>');
          } 
          else {

            $('#groupPolres').addClass('d-none');
            $('#groupPolsek').addClass('d-none');

            $('#polres_idInp').val('');
            $('#polsek_idInp').val('');

            $('#polres_idInp').prop('required', false);
            $('#polsek_idInp').prop('required', false);
          }
        });

          // -- load polsek ketika polres dipilih dan akses POLSEK
          $('#polres_idInp').on('change', function() {
            var akses = $('select[name="akses_inp"]').val();
            var polres_id = $(this).val();
            if(akses === 'POLSEK' && polres_id) {
              // AJAX load polsek berdasarkan polres
              $.ajax({
                url: 'get_polsek_user.php',
                type: 'GET',
                data: {polres_id: polres_id},
                dataType: 'json',
                success: function(data) {
                  var opt = '<option value="">- Pilih Polsek -</option>';
                  for(var i=0; i<data.length; i++) {
                    opt += '<option value="'+data[i].id+'">'+data[i].nama+'</option>';
                  }
                  $('#polsek_idInp').html(opt);
                }
              });
            } else {
              $('#polsek_idInp').html('<option value="">- Pilih Polsek -</option>');
            }
          });
      });
      
       

      // Modal Hapus
      $(document).on('click', '.btnHapusAnggota', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#hapus_id').val(id);
        $('#hapus_nama').text(nama);
        $('#modalHapusAnggota').modal('show');
      });
    
 