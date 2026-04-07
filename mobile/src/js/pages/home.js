export function Home(container) {
  const user = JSON.parse(localStorage.getItem('user') || '{}');
  container.innerHTML = `
    <div class="home-page-mobile">
      <div class="home-topbar">
        <div class="topbar-left">
          <div class="user-photo-mini">
            <img src="../public/upload/pengguna/${user.foto || 'user.png'}" alt="User Photo" />
          </div>
          <div class="topbar-greet">
            <div class="greet-title">Selamat Datang</div>
            <div class="user-nama">${user.nama ? user.nama : 'Anggota'}</div>
          </div>
        </div>
        <div class="topbar-right">
          <button class="notif-btn" title="Notifikasi">
            <svg class="icon-svg" width="30" height="30" viewBox="0 0 24 24" fill="#fff" style="vertical-align:middle">
            <path d="M12 22c1.1 0 2-.9 2-2h-4a2 2 0 0 0 2 2zm6-6v-5c0-3.07-1.63-5.64-5-6.32V4a2 2 0 1 0-4 0v.68C7.64 5.36 6 7.92 6 11v5l-1.7 1.7a1 1 0 0 0 .7 1.7h14a1 1 0 0 0 .7-1.7L18 16z"/>
            </svg>
          </button>
        </div>
      </div>
      <div class="user-info-card">
        <div><b>Username:</b> <span>${user.username || '-'}</span></div>
        <div><b>Polres:</b> <span>${user.polres_nama || '-'}</span></div>
        <div><b>Polsek:</b> <span>${user.polsek_nama || '-'}</span></div>
      </div>
      <button id="logoutBtn" class="btn-logout">Logout</button>

      <nav class="bottom-nav">
        <a class="nav-item active" href="#/home" title="Beranda">
            <!-- Home icon -->
            <svg class="icon-svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#29b6f6" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 11.5L12 4l9 7.5"/><path d="M5 12v7a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-3a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v3a2 2 0 0 0 2 2h0a2 2 0 0 0 2-2v-7"/>
            </svg>
            <span>Beranda</span>
        </a>
        <a class="nav-item" href="#/patroli" title="Patroli">
            <!-- Walking/patrol icon -->
            <svg class="icon-svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#adccea" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="13" cy="4.5" r="2.25"/>
            <path d="M9.8 21.6l3.6-7.46c.25-.5.79-.82 1.36-.72l4.14.73"/>
            <path d="M13.4 12.2l-1.71-2.36a1.49 1.49 0 0 0-1.32-.58l-2.65.33a1.33 1.33 0 0 0-.91 2.09l2.21 2.86a2.74 2.74 0 0 1 .55 2.1l-.47 2.54"/>
            </svg>
            <span>Patroli</span>
        </a>
        <a class="nav-item" href="#/profil" title="Profil">
            <!-- User/profile icon -->
            <svg class="icon-svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#adccea" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="8.5" r="3.5"/>
            <path d="M19 21v-1a7 7 0 1 0-14 0v1"/>
            </svg>
            <span>Profil</span>
        </a>
        </nav>
    </div>
  `;

  // Event logout
  container.querySelector('#logoutBtn').onclick = () => {
    localStorage.removeItem('user');
    window.location.hash = '#/login';
  };
}