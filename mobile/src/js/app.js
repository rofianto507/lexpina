import { router } from './router.js';

function showSplash() {
  const splash = document.createElement('div');
  splash.className = 'splash';
  splash.innerHTML = `<div class="splash-logo"><img class="login-icon" src="src/assets/icon.png" alt="Logo"></div>`;
  document.body.appendChild(splash);

  setTimeout(() => {
    splash.classList.add('hide');
    setTimeout(() => {
        splash.remove();
        // Pindah ke #/login kalau di-root
        if (!location.hash || location.hash === '#/' || location.hash === '#') {
        window.location.hash = '#/login';
        }
        // Apapun posisi hash, panggil router agar halaman dirender
        router();
    }, 900);
    }, 2000);
}

let isFirstLoad = true;
window.addEventListener('DOMContentLoaded', () => {
  if (isFirstLoad) {
    showSplash();
    isFirstLoad = false;
  } else {
    router();
  }
});
window.addEventListener('hashchange', router);

// (Optional) Service Worker (akan error CSP di server kamu, abaikan)
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('sw.js')
      .then(() => console.log('SW registered'))
      .catch(err => console.log('SW error:', err));
  });
}