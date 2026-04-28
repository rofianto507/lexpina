<footer class="main-footer">
        <div class="footer-left">
            <img src="assets/img/logo.png" alt="Logo LexPina" class="footer-logo">
            <p>LexPina adalah basis data hukum yang kuat dan mudah digunakan, menyediakan akses ke peraturan, undang-undang, putusan pengadilan, dan informasi hukum dalam satu tempat untuk membantu pengguna menemukan jawaban hukum dengan cepat dan terpercaya.</p>
            <div class="social-icons">
                <a href="#" target="_blank" title="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                <a href="#" target="_blank" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" target="_blank" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#" target="_blank" title="X (Twitter)"><i class="fa-brands fa-x"></i></a>
            </div>
        </div>
        <div class="footer-right">
            <h3>Hubungi Kami</h3>
            <p>lexplna.help@gmail.com</p>
            <p>+62 82135840605</p>
            <p>Jl. Pengadegan Barat VII No. E/3, Pengadegan, Pancoran, Jakarta Selatan.</p>
            <button class="btn-saran" onclick="window.location.href='saran.php'">Saran & Masukan</button>
        </div>
    </footer>
    <div class="copyright">
        <p>&copy; 2026 Hak Cipta milik LexPina.com</p>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Isolasi dengan try-catch agar kebal dari error file lain
            try {
                const formLoginManual = document.getElementById('formLoginManual');
                if (formLoginManual) {
                    formLoginManual.addEventListener('submit', function(e) {
                        e.preventDefault(); // Kunci utamanya disini!
                        
                        const formData = new FormData(this);
                        const btnSubmit = document.getElementById('btnSubmitLogin');
                        const loginError = document.getElementById('loginErrorMessage');
                        
                        btnSubmit.disabled = true;
                        btnSubmit.innerText = 'Checking...';
                        if(loginError) loginError.style.display = 'none';

                        fetch('proses_login_manual.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                const pendingRedirect = localStorage.getItem('lexpina_redirect');
                                if (pendingRedirect) {
                                    localStorage.removeItem('lexpina_redirect');
                                    window.location.href = pendingRedirect;
                                } else {
                                    window.location.reload();
                                }
                            } else {
                                if(loginError) {
                                    loginError.innerText = data.message;
                                    loginError.style.display = 'block';
                                }
                                btnSubmit.disabled = false;
                                btnSubmit.innerText = 'Sign In';
                            }
                        })
                        .catch(error => console.error(error));
                    });
                }
            } catch (err) { console.error("Error Login:", err); }

            // Script untuk Trigger Modal dari halaman langganan
            try {
                const loginTriggers = document.querySelectorAll('.btn-login-trigger');
                const loginModal = document.getElementById('loginModal');
                
                if(loginTriggers.length > 0) {
                    loginTriggers.forEach(button => {
                        button.addEventListener('click', function() {
                            const targetUrl = this.getAttribute('data-checkout-url');
                            localStorage.setItem('lexpina_redirect', targetUrl);
                            if (loginModal) {
                                loginModal.classList.add('show');
                                document.body.style.overflow = 'hidden';
                            }
                        });
                    });
                }
            } catch (err) { console.error("Error Trigger:", err); }
        });
    </script>
</body>
</html>