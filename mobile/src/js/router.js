import { Welcome } from './pages/welcome.js';
import { Login } from './pages/login.js';
import { Home } from './pages/home.js';
import {Perencanaan} from './pages/perencanaan.js';
import { Patroli } from './pages/patroli.js';
import { Profil } from './pages/profil.js';

const routes = {
    '/': Welcome,    // boleh Welcome, boleh redirect-home (lihat logic di bawah)
    '/welcome': Welcome,
    '/login': Login,
    '/home': Home,
    '/perencanaan': Perencanaan,
    '/patroli': Patroli,
    '/profil': Profil,
};

function isLoggedIn() {
  try {
    return !!JSON.parse(localStorage.getItem('user'));
  } catch { return false; }
}

// Modified router untuk melompat otomatis
export function router() {
  let path = window.location.hash.replace('#', '') || '/';

  // Intercept: jika belum login, ke login (kecuali memang di login/welcome)
  if (!isLoggedIn() && path !== '/login' && path !== '/welcome') {
    window.location.hash = '#/login';
    return;
  }

  // Jika sudah login dan path ke / atau /login, lompat ke /home
  if (isLoggedIn() && (path === '/' || path === '/login' || path === '/welcome')) {
    window.location.hash = '#/home';
    return;
  }

  // Load page sesuai rute
  const page = routes[path] || Login;
  const app  = document.getElementById('app');
  app.innerHTML = '';
  page(app);
}