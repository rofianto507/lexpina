function openNotifModal(element, notifId) {
            // 1. Isi konten modal dengan data dari atribut elemen yang diklik
            document.getElementById('modalNotifTitle').innerText = element.getAttribute('data-title');
            document.getElementById('modalNotifDate').innerText = element.getAttribute('data-date');
            document.getElementById('modalNotifContent').innerText = element.getAttribute('data-content');
            
            // Atur gaya ikon
            const iconDiv = document.getElementById('modalNotifIcon');
            const color = element.getAttribute('data-color');
            iconDiv.innerHTML = '<i class="fa-solid ' + element.getAttribute('data-icon') + '"></i>';
            iconDiv.style.backgroundColor = color + '15'; // Transparansi 15% untuk background ikon
            iconDiv.style.color = color;

            // 2. Tampilkan Modal
            const modal = document.getElementById('notifDetailModal');
            modal.style.visibility = 'visible';
            modal.style.opacity = '1';

            // 3. Jika statusnya belum dibaca, proses AJAX untuk update database
            if (element.classList.contains('unread-notif')) {
                // Hapus gaya "belum dibaca" (hilangkan background biru & border) secara instan di UI
                element.classList.remove('unread-notif');
 

                // Kurangi angka badge merah di sidebar profil & header secara real-time
                const badges = document.querySelectorAll('.notif-badge-profile, .profile-nav-list span[style*="background: #e74c3c"]');
                badges.forEach(badge => {
                    let count = parseInt(badge.innerText);
                    if (count > 1) {
                        badge.innerText = count - 1;
                    } else {
                        badge.style.display = 'none'; // Sembunyikan badge jika angkanya sudah 0
                    }
                });

                // Kirim perintah update ke server di balik layar (tanpa reload)
                fetch('proses_notifikasi.php?action=read&id=' + notifId + '&ajax=1')
                    .then(response => response.json())
                    .catch(error => console.error('Error updating notif:', error));
            }
        }

        function closeNotifModal() {
            const modal = document.getElementById('notifDetailModal');
            modal.style.visibility = 'hidden';
            modal.style.opacity = '0';
        }
if (localStorage.getItem('theme') === 'dark') {
    document.documentElement.classList.add('dark-mode');
}
document.addEventListener('DOMContentLoaded', () => {
    const themeBtn = document.getElementById("btnThemeToggle");
    const themeIcon = themeBtn ? themeBtn.querySelector("i") : null;
    const htmlElement = document.documentElement; // Menargetkan tag <html>

    if (themeBtn && themeIcon) {
        // Cek status saat halaman dimuat
        if (localStorage.getItem("theme") === "dark") {
            themeIcon.classList.replace("fa-moon", "fa-sun");
        } else {
            themeIcon.classList.replace("fa-sun", "fa-moon");
        }

        // Aksi saat tombol diklik
        themeBtn.addEventListener("click", function() {
            htmlElement.classList.toggle("dark-mode");
            
            if (htmlElement.classList.contains("dark-mode")) {
                // Berubah jadi Dark Mode
                localStorage.setItem("theme", "dark");
                themeIcon.classList.replace("fa-moon", "fa-sun"); // Ganti ikon Matahari
            } else {
                // Kembali ke Light Mode
                localStorage.setItem("theme", "light");
                themeIcon.classList.replace("fa-sun", "fa-moon"); // Ganti ikon Bulan
            }
        });
    }
    const searchForm = document.querySelector('.search-container');
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    const clearBtn = document.getElementById('clearSearchBtn');
    let debounceTimer;

            // ==========================================
            // FUNGSI RIWAYAT PENCARIAN (LOCALSTORAGE)
            // ==========================================
            function getRecentSearches() {
                return JSON.parse(localStorage.getItem('lexpina_recent_searches')) || [];
            }

            function saveRecentSearch(keyword) {
                if (!keyword.trim()) return;
                let searches = getRecentSearches();
                searches = searches.filter(s => s.toLowerCase() !== keyword.toLowerCase()); // Hapus duplikat
                searches.unshift(keyword); // Taruh di paling atas
                if (searches.length > 5) searches.pop(); // Batasi maksimal 5 riwayat
                localStorage.setItem('lexpina_recent_searches', JSON.stringify(searches));
            }

            function showRecentSearches() {
                const searches = getRecentSearches();
                if (searches.length === 0) {
                    searchResults.style.display = 'none';
                    return;
                }

                let html = `
                <div class="recent-search-header">
                    <span>Riwayat Pencarian</span>
                    <button type="button" id="clearHistoryBtn">Hapus</button>
                </div>`;
                
                searches.forEach(s => {
                    html += `
                    <div class="recent-search-item" onclick="applyRecentSearch('${s.replace(/'/g, "\\'")}')">
                        <i class="fa-solid fa-clock-rotate-left"></i> <span>${s}</span>
                    </div>`;
                });

                searchResults.innerHTML = html;
                searchResults.style.display = 'block';

                // Fungsi tombol hapus riwayat
                document.getElementById('clearHistoryBtn').addEventListener('click', function(e) {
                    e.stopPropagation(); // Mencegah dropdown tertutup otomatis
                    localStorage.removeItem('lexpina_recent_searches');
                    searchResults.style.display = 'none';
                });
            }

            // Menerapkan kata kunci dari riwayat ke kotak pencarian
            window.applyRecentSearch = function(keyword) {
                searchInput.value = keyword;
                searchInput.focus();
                // Picu event 'input' agar AJAX langsung mencari kata kunci tersebut
                searchInput.dispatchEvent(new Event('input'));
            };

            if (searchForm) {
                searchForm.addEventListener('submit', function() {
                    saveRecentSearch(searchInput.value);
                });
            }

            if (searchInput) {
                searchInput.addEventListener('focus', function() {
                    if (this.value.trim().length === 0) {
                        showRecentSearches();
                    }
                });
            }

            // ==========================================
            // FUNGSI LIVE SEARCH AJAX
            // ==========================================
            if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        const keyword = this.value.trim();
                        clearBtn.style.display = keyword.length > 0 ? 'block' : 'none';

                    if (keyword.length > 1) {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => {
                            fetch('ajax_search.php?q=' + encodeURIComponent(keyword))
                                .then(response => response.text())
                                .then(data => {
                                    searchResults.innerHTML = data;
                                    searchResults.style.display = 'block';
                                });
                        }, 300);
                    } else if (keyword.length === 0) {
                        // Jika teks dihapus sampai kosong, kembali tampilkan riwayat
                        showRecentSearches();
                    } else {
                        searchResults.style.display = 'none';
                    }
                });
            }
            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    searchInput.focus();
                    this.style.display = 'none';
                    showRecentSearches(); // Munculkan riwayat saat teks dibersihkan
                });
            }
            if(searchResults) {
                document.addEventListener('click', function(event) {
                    if (!searchInput.contains(event.target) && !searchResults.contains(event.target)) {
                        searchResults.style.display = 'none';
                    }
                });
            }
    // --- FITUR MODAL LOGOUT ---
    const btnLogoutTrigger = document.getElementById('btnLogoutTrigger');
    const btnCancelLogout = document.getElementById('btnCancelLogout');
    const logoutModal = document.getElementById('logoutModal');

    if (btnLogoutTrigger && logoutModal && btnCancelLogout) {
        
        // Memunculkan Modal saat menu Keluar diklik
        btnLogoutTrigger.addEventListener('click', (e) => {
            e.preventDefault(); // Mencegah link pindah halaman
            logoutModal.classList.add('show');
            document.body.style.overflow = 'hidden'; // Matikan scroll layar belakang
        });

        // Menutup Modal saat tombol Batal diklik
        btnCancelLogout.addEventListener('click', () => {
            logoutModal.classList.remove('show');
            document.body.style.overflow = 'auto'; // Nyalakan scroll kembali
        });

        // Menutup Modal jika klik area gelap di luar kotak putih
        logoutModal.addEventListener('click', (e) => {
            if (e.target === logoutModal) {
                logoutModal.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        });
    }
    // --- FITUR LOGIN MODAL ---
    const btnOpenLogin = document.getElementById('btnOpenLogin');
    const btnCloseLogin = document.getElementById('btnCloseLogin');
    const loginModal = document.getElementById('loginModal');

    if (btnOpenLogin && loginModal && btnCloseLogin) {
        // Buka Modal
        btnOpenLogin.addEventListener('click', () => {
            loginModal.classList.add('show');
            document.body.style.overflow = 'hidden'; // Mencegah halaman utama bisa di-scroll saat modal terbuka
        });

        // Tutup Modal dari tombol X
        btnCloseLogin.addEventListener('click', () => {
            loginModal.classList.remove('show');
            document.body.style.overflow = 'auto'; // Kembalikan scroll
        });

        // Tutup Modal jika mengklik area gelap di luar kotak putih
        loginModal.addEventListener('click', (e) => {
            if (e.target === loginModal) {
                loginModal.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        });
    }

    const loginTriggers = document.querySelectorAll('.btn-login-trigger');

    loginTriggers.forEach(button => {
        button.addEventListener('click', function() {
            // 1. Simpan URL tujuan ke localStorage
            const targetUrl = this.getAttribute('data-checkout-url');
            localStorage.setItem('lexpina_redirect', targetUrl);

            // 2. Buka Modal Login
            if (loginModal) {
                loginModal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        });
    });
    if (btnOpenLogin) {
        btnOpenLogin.addEventListener('click', () => {
            localStorage.removeItem('lexpina_redirect');
        });
    }
    // --- FITUR FULL SCREEN PDF ---
    const btnFullscreen = document.getElementById('btnFullscreen');
    const pdfWrapper = document.getElementById('pdfWrapper');

    if (btnFullscreen && pdfWrapper) {
        btnFullscreen.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                // Masuk ke mode Full Screen
                pdfWrapper.requestFullscreen().catch(err => {
                    alert(`Error: Tidak bisa masuk mode layar penuh (${err.message})`);
                });
                btnFullscreen.innerHTML = '<i class="fa-solid fa-compress"></i> Kecilkan Layar';
            } else {
                // Keluar dari mode Full Screen
                document.exitFullscreen();
                btnFullscreen.innerHTML = '<i class="fa-solid fa-expand"></i> Layar Penuh';
            }
        });

        // Pantau perubahan mode layar penuh (jika user tekan tombol ESC)
        document.addEventListener('fullscreenchange', () => {
            if (!document.fullscreenElement) {
                btnFullscreen.innerHTML = '<i class="fa-solid fa-expand"></i> Layar Penuh';
            }
        });
    }
    // --- HAMBURGER MENU & DROPDOWN MOBILE ---
    const mobileMenu = document.getElementById('mobile-menu');
    const navMenu = document.querySelector('.main-nav ul');
    const dropdownBtn = document.querySelector('.dropdown > a');
    const dropdownContainer = document.querySelector('.dropdown');

    // Toggle untuk Hamburger Menu Utama
    if (mobileMenu && navMenu) {
        mobileMenu.addEventListener('click', () => {
            navMenu.classList.toggle('show');
        });
    }

    // Toggle khusus untuk Dropdown (Data Base) di Mobile
    if (dropdownBtn && dropdownContainer) {
        dropdownBtn.addEventListener('click', (e) => {
            // Hanya jalankan fungsi klik ini jika layar seukuran HP/Tablet
            if (window.innerWidth <= 768) {
                e.preventDefault(); // Cegah halaman reload agar submenu bisa terbuka
                dropdownContainer.classList.toggle('show-dropdown');
            }
        });
    }
    const cardContainer = document.getElementById('cardContainer');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');

    // --- PENGECEKAN ELEMEN ---
    // Jika elemen slider tidak ditemukan di halaman ini (seperti di tentang.php), 
    // maka hentikan eksekusi script di titik ini agar tidak terjadi error.
    if (!cardContainer || !prevBtn || !nextBtn) {
        return; 
    }

    // --- JIKA ELEMEN ADA (di index.php), JALANKAN KODE DI BAWAH INI ---
    const scrollAmount = 220; // Jarak geser saat tombol diklik
    let autoScrollSpeed = 1; // Kecepatan gerakan (pixel)
    let autoScrollInterval;
    let resumeTimeout; // Variabel untuk jeda waktu
    let isHovering = false; // Variabel untuk mengecek posisi mouse

    // Fungsi Auto Scroll
    function startAutoScroll() {
        clearInterval(autoScrollInterval); 
        autoScrollInterval = setInterval(() => {
            cardContainer.scrollLeft += autoScrollSpeed;

            if (cardContainer.scrollLeft >= (cardContainer.scrollWidth - cardContainer.offsetWidth)) {
                cardContainer.scrollLeft = 0;
            }
        }, 20);
    }

    function stopAutoScroll() {
        clearInterval(autoScrollInterval);
    }

    // Jalankan auto scroll pertama kali
    startAutoScroll();

    // Deteksi Hover Mouse
    cardContainer.addEventListener('mouseenter', () => {
        isHovering = true;
        stopAutoScroll();
    });
    
    cardContainer.addEventListener('mouseleave', () => {
        isHovering = false;
        startAutoScroll();
    });

    // Fungsi Klik Manual
    function manualScroll(amount) {
        stopAutoScroll(); 
        clearTimeout(resumeTimeout); 

        cardContainer.scrollBy({
            left: amount,
            behavior: 'smooth'
        });

        resumeTimeout = setTimeout(() => {
            if (!isHovering) {
                startAutoScroll();
            }
        }, 800);
    }

    // Event Listener Tombol
    nextBtn.addEventListener('click', () => {
        manualScroll(scrollAmount);
    });

    prevBtn.addEventListener('click', () => {
        manualScroll(-scrollAmount);
    });
    const formLoginManual = document.getElementById('formLoginManual');
    const loginError = document.getElementById('loginErrorMessage');
    if (formLoginManual) {
        formLoginManual.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const btnSubmit = document.getElementById('btnSubmitLogin');
            
            btnSubmit.disabled = true;
            btnSubmit.innerText = 'Checking...';
            loginError.style.display = 'none';

            fetch('proses_login_manual.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Gunakan logika redirect yang sama dengan login Google
                    const pendingRedirect = localStorage.getItem('lexpina_redirect');
                    if (pendingRedirect) {
                        localStorage.removeItem('lexpina_redirect');
                        window.location.href = pendingRedirect;
                    } else {
                        window.location.reload();
                    }
                } else {
                    loginError.innerText = data.message;
                    loginError.style.display = 'block';
                    btnSubmit.disabled = false;
                    btnSubmit.innerText = 'Sign In';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btnSubmit.disabled = false;
                btnSubmit.innerText = 'Sign In';
            });
        });
    }
    // Navigasi ke Modal Sign Up (Nanti kita buat modalknya)
    const btnToSignUp = document.getElementById('btnToSignUp');
    if(btnToSignUp) {
        btnToSignUp.addEventListener('click', () => {
            alert('Fitur Sign Up sedang disiapkan.'); // Sementara
            // Logic untuk menutup modal login dan membuka modal signup
        });
    }
});
// --- FUNGSI GOOGLE SSO CALLBACK ---
    
    // Fungsi untuk membedah token JWT dari Google
    function parseJwt(token) {
        var base64Url = token.split('.')[1];
        var base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        var jsonPayload = decodeURIComponent(window.atob(base64).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));
        return JSON.parse(jsonPayload);
    }

    // Fungsi ini dipanggil otomatis oleh Google saat login berhasil
    // Fungsi ini dipanggil otomatis oleh Google saat login berhasil
function handleGoogleLogin(response) {
        // 1. Tangkap Token JWT
        const responsePayload = parseJwt(response.credential);

        // 2. Siapkan data untuk dikirim ke Backend PHP
        const userData = {
            google_id: responsePayload.sub,
            nama: responsePayload.name,
            email: responsePayload.email,
            foto: responsePayload.picture
        };

        // 3. Kirim data menggunakan Fetch API (AJAX)
        fetch('proses_login_google.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(userData)
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                // CEK APAKAH ADA TITIPAN REDIRECT DARI HALAMAN LANGGANAN
                const pendingRedirect = localStorage.getItem('lexpina_redirect');
                
                if (pendingRedirect) {
                    // Hapus data titipan agar tidak nyangkut untuk login berikutnya
                    localStorage.removeItem('lexpina_redirect');
                    // Arahkan langsung ke halaman checkout
                    window.location.href = pendingRedirect;
                } else {
                    // Jika tidak ada titipan (login biasa dari header), refresh halaman
                    window.location.reload();
                }
            } else {
                alert('Gagal masuk: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghubungi server database.');
        });
    }