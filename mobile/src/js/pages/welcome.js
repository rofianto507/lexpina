export function Welcome(container) {
  container.innerHTML = `
    <div class="welcome-page">

      <!-- Status Bar -->
      <div class="status-bar">
        <span id="jam">00:00</span>
        <span>📶 🔋</span>
      </div>

      <!-- Logo -->
      <div class="hero-section">
        <div class="logo-wrap">
          <div class="logo-icon">🗺️</div>
        </div>
        <h1>PetaDigi</h1>
        <p class="subtitle">MOBILE APP</p>
      </div>

      <!-- Banner -->
      <div class="banner">
        <div class="banner-icon">👮</div>
        <h2>Selamat Datang</h2>
        <p>Sistem Informasi Anggota Lapangan</p>
      </div>

      <!-- Feature Grid -->
      <div class="feature-grid">
        <div class="feature-card">
          <span>📍</span><p>Peta Digital</p>
        </div>
        <div class="feature-card">
          <span>📋</span><p>Laporan</p>
        </div>
        <div class="feature-card">
          <span>👥</span><p>Anggota</p>
        </div>
        <div class="feature-card">
          <span>🔔</span><p>Notifikasi</p>
        </div>
      </div>

      <!-- Buttons -->
      <div class="btn-group">
        <button class="btn-masuk" onclick="window.location.hash='#/login'">
          🔐 Masuk ke Aplikasi
        </button>
        <button class="btn-daftar" onclick="window.location.hash='#/register'">
          Daftar Akun Baru
        </button>
      </div>

      <div class="version">v1.0.0 • PetaDigi Mobile © 2026</div>

    </div>
  `;

  // Jam realtime
  function updateJam() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2, '0');
    const m = String(now.getMinutes()).padStart(2, '0');
    const el = document.getElementById('jam');
    if (el) el.textContent = h + ':' + m;
  }
  updateJam();
  setInterval(updateJam, 1000);
}