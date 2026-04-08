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

// Password Strength Meter
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
    if (!val) {
        text = "";
        color = "text-danger";
    } else if(strength === 5) {
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

  $(function(){
  let sudahPrefillPolsek = false;

  function showPolresPolsek() {
    var akses = $('#aksesInp').val();
    if(akses==="POLRES") {
      $('#groupPolres').removeClass('d-none');
      $('#groupPolsek').addClass('d-none');
      $("#polres_idInp").prop('required',true);
      $("#polsek_idInp").val('').prop('required',false);
    } else if(akses==="POLSEK") {
      $('#groupPolres').removeClass('d-none');
      $('#groupPolsek').removeClass('d-none');
      $("#polres_idInp").prop('required',true);
      $("#polsek_idInp").prop('required',true);
      var pid = $("#polres_idInp").val();
      // Pemanggilan AJAX Polsek hanya kalau bukan prefill edit
      if(!sudahPrefillPolsek) {
        loadPolsek(pid, null);
      }
      sudahPrefillPolsek = false;
    } else {
      $('#groupPolres').hide();
      $('#groupPolsek').hide();
      $("#polres_idInp").prop('required',false).val('');
      $("#polsek_idInp").prop('required',false).val('');
    }
  }

  // Prefill ketika halaman baru dibuka
  var akses_awal = document.getElementById('aksesInp').getAttribute('data-awal') || document.getElementById('aksesInp').value || "";
 
  if(akses_awal === "POLRES") {
    $('#groupPolres').removeClass('d-none');
    $('#groupPolsek').addClass('d-none');
    $("#polres_idInp").prop('required',true);
    // Pastikan value polres_id terpilih dari server
  } else if(akses_awal === "POLSEK") {
    $('#groupPolres').removeClass('d-none');
    $('#groupPolsek').removeClass('d-none');
    $("#polres_idInp").prop('required',true);
    $("#polsek_idInp").prop('required',true);
    var polres_awal = parseInt(document.getElementById('polres_idInp').getAttribute('data-awal')) || 0;
    var polsek_awal = parseInt(document.getElementById('polsek_idInp').getAttribute('data-awal')) || 0;
    
    $('#polres_idInp').val(polres_awal);
    // Prefill: jalankan loadPolsek AJAX dengan selected, pasang flag supaya tidak terbuka kedua kali
    loadPolsek(polres_awal, polsek_awal);
    sudahPrefillPolsek = true;
  }

  $('#aksesInp, #polres_idInp').on('change', showPolresPolsek);

  function loadPolsek(polres_id, selected) {
    $('#polsek_idInp').html('<option value="">Memuat data...</option>');
    if(!polres_id) {
      $('#polsek_idInp').html('<option value="">- Pilih Polsek -</option>');
      $('#polsek_idInp').val('');
      return;
    }
    $.getJSON('get_polsek_user.php', {polres_id: polres_id}, function(resp) {
      var html = '<option value="">- Pilih Polsek -</option>';
      if(resp && resp.length){
        for(var i=0; i<resp.length; i++) {
          html += '<option value="'+resp[i].id+'"'+(selected==resp[i].id?' selected':'')+'>'+resp[i].nama+'</option>';
        }
      }
      $('#polsek_idInp').html(html);
      // Prefill value polsek di dropdown setelah AJAX selesai
      if(selected && resp.filter(x => String(x.id) === String(selected)).length > 0) {
        $('#polsek_idInp').val(selected);
      }
    });
  }
});