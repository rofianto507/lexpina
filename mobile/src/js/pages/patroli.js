export function Patroli(container) {
  container.innerHTML = `<div class="page-center">Fitur Patroli (nantikan update!)</div>
   <nav class="bottom-nav">
        <a class="nav-item" href="#/home" title="Beranda">
            <!-- Home icon -->
            <svg class="icon-svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#29b6f6" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 11.5L12 4l9 7.5"/><path d="M5 12v7a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-3a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v3a2 2 0 0 0 2 2h0a2 2 0 0 0 2-2v-7"/>
            </svg>
            <span>Beranda</span>
        </a>
        <a class="nav-item active" href="#/patroli" title="Patroli">
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
  `;
}