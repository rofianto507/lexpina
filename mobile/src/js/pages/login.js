export function Login(container) {
  container.innerHTML = `
    <div class="login-page">
      <div class="login-card">
        <div class="login-logo">
          <img src="src/assets/icon.png" class="login-icon" alt="Logo PetaDigi" loading="lazy" />
        </div>
        <div class="login-title">Masuk ke PetaDigi</div>
        <form id="loginform" class="login-form" autocomplete="on">
          <input type="text" name="username" placeholder="NIP / Email" required autocomplete="username" />
          <input type="password" name="password" placeholder="Kata Sandi" required autocomplete="current-password" />
          <button type="submit">Masuk</button>
        </form>
        <div class="login-error" style="color:#e53935;margin-top:12px;display:none"></div>
      </div>
    </div>
  `;

  // Ambil node form dan error box
  const form = container.querySelector('#loginform');
  const errBox = container.querySelector('.login-error');

  form.onsubmit = async e => {
    e.preventDefault();
    errBox.style.display = 'none';

    const username = form.username.value.trim();
    const password = form.password.value.trim();
    try {
      const res = await fetch('../api/login_anggota.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ username, password })
      });
      const data = await res.json();
      if (!res.ok || !data.success) throw new Error(data.error || 'Gagal login');
      localStorage.setItem('user', JSON.stringify(data.user));
      window.location.hash = '#/home';
    } catch (err) {
      errBox.textContent = err.message;
      errBox.style.display = 'block';
    }
  };
}