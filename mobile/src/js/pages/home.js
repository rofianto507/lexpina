export function Home(container) {
    const dummyRiwayat = [
  {
    waktu: "2026-04-09 08:04",
    lokasi: "Jl. Ahmad Yani",
    kegiatan: "Patroli pagi, situasi aman",
    petugas: "Budi Setiawan"
  },
  {
    waktu: "2026-04-08 18:23",
    lokasi: "Perum Griya Indah",
    kegiatan: "Antisipasi balap liar",
    petugas: "Siti Marlina"
  },
  {
    waktu: "2026-04-08 15:10",
    lokasi: "Pasar Baru",
    kegiatan: "Pantau keramaian, edukasi prokes",
    petugas: "Andi Pratama"
  },
  {
    waktu: "2026-04-07 23:17",
    lokasi: "Jl. Bandara",
    kegiatan: "Cegah tindak kriminal malam",
    petugas: "Bayu Rahman"
  },
  {
    waktu: "2026-04-07 14:44",
    lokasi: "Kampung Nelayan",
    kegiatan: "Sosialisasi kamtibmas",
    petugas: "Yuni Wulandari"
  }
];

  const user = JSON.parse(localStorage.getItem('user') || '{}');
  container.innerHTML = `
    <div class="home-page-mobile">
      <div class="home-topbar">
        <div class="topbar-left">
          <div class="user-photo-mini">
            <img src="../public/upload/pengguna/${user.foto || 'user.png'}" alt="User Photo" />
          </div>
          <div class="topbar-greet">
            <div class="greet-title">${user.nama ? user.nama : 'Anggota'}</div>
            <div class="user-nama">${user.username ? user.username : 'NRP'}</div>
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
       <!-- Statistik Dashboard -->
      <div class="home-card-row">
        <div class="home-card stat-perencanaan">
          <div class="card-title">Perencanaan</div>
          <div class="card-value">12</div>
        </div>
        <div class="home-card stat-patroli">
          <div class="card-title">Patroli</div>
          <div class="card-value">34</div>
        </div>
        <div class="home-card stat-riwayat">
          <div class="card-title">Riwayat</div>
          <div class="card-value">9</div>
        </div>
      </div>
        <div class="home-riwayat-card">
      <div class="riwayat-title">Riwayat 5 Patroli Terakhir</div>
      <ul class="riwayat-list">
        ${dummyRiwayat.map(item => `
          <li>
            <div class="riwayat-kegiatan"><b>${item.kegiatan}</b></div>
            <div class="riwayat-info">
              <span class="riwayat-lokasi"><svg width="15" height="15" viewBox="0 0 24 24" fill="#90caf9" style="vertical-align:middle; margin-bottom:2px"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 17.75C9 16.25 7 12.97 7 9.5c0-2.48 2.02-4.5 4.5-4.5s4.5 2.02 4.5 4.5c0 3.47-2 6.75-4.5 10.25z"/><circle cx="12" cy="9.5" r="2.25"/></svg> ${item.lokasi}</span>
              <span class="riwayat-petugas"><svg width="15" height="15" viewBox="0 0 24 24" fill="#aed581" style="vertical-align:middle; margin-bottom:2px"><circle cx="12" cy="8" r="3.2"/><path d="M12 12c-3.3 0-6 2.4-6 5.3V20h12v-2.7c0-2.9-2.7-5.3-6-5.3z"/></svg> ${item.petugas}</span>
            </div>
            <div class="riwayat-waktu">${item.waktu}</div>
          </li>
        `).join('')}
      </ul>
    </div>
      <nav class="bottom-nav">
        <a class="nav-item active" href="#/home" title="Beranda">
            <!-- Home icon -->
            <svg class="icon-svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#29b6f6" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 11.5L12 4l9 7.5"/><path d="M5 12v7a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-3a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v3a2 2 0 0 0 2 2h0a2 2 0 0 0 2-2v-7"/>
            </svg>
            <span>Beranda</span>
        </a>
        <a class="nav-item" href="#/perencanaan" title="Perencanaan">
            <svg class="icon-svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#adccea" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="16" rx="2" /><path d="M16 2v4M8 2v4" /><path d="M3 10h18" />
            </svg>
            <span>Perencanaan</span>
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

}