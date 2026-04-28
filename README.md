# ⚖️ LexPina — Platform Hukum Digital Indonesia

**LexPina** adalah platform web berbasis PHP yang menyediakan layanan akses dokumen hukum, langganan premium, dan informasi hukum terkini untuk masyarakat Indonesia.

---

## ✨ Fitur Utama

- **Database Hukum** — Akses ribuan dokumen hukum (UU, PP, Perda, dll.) berdasarkan kategori
- **Berita Hukum** — Artikel dan berita hukum terkini
- **Langganan Premium** — Sistem membership berbayar dengan checkout & konfirmasi pembayaran
- **Autentikasi** — Login biasa & Login dengan Google (OAuth)
- **Dashboard Admin** — Manajemen data, transaksi, pengguna, dan statistik real-time
- **Notifikasi** — Sistem notifikasi untuk pengguna terkait status langganan & saran
- **Saran & Masukan** — Fitur pengiriman saran dari pengguna ke admin
- **Profil Pengguna** — Manajemen akun dan data anggota
- **CSRF Protection** — Keamanan form dengan token CSRF

---

## 🛠️ Teknologi

| Layer | Teknologi |
|---|---|
| Backend | PHP 8+, PDO MySQL |
| Database | MySQL / MariaDB |
| Frontend | Bootstrap 5, jQuery 3.7 |
| Chart | Apache ECharts |
| Tabel | DataTables 1.13.7 |
| Auth | Session PHP + Google OAuth |
| Word | PHPWord |

---

## 🚀 Cara Instalasi

### Prasyarat
- XAMPP / Laragon / PHP 8+ + MySQL
- Web browser modern

### Langkah-langkah

1. **Clone repository**
   ```bash
   git clone https://github.com/rofianto507/lexpina.git
   cd lexpina
   ```

2. **Salin file konfigurasi**
   ```bash
   cp config/configuration.example.php config/configuration.php
   ```

3. **Edit konfigurasi database**
   ```php
   // config/configuration.php
   $host = 'localhost';
   $db   = 'lexpina_db';   // nama database Anda
   $user = 'root';
   $pass = '';
   ```

4. **Import database**
   - Buat database baru: `lexpina_db`
   - Import file SQL yang tersedia

5. **Buat folder uploads** (jika belum ada)
   ```bash
   mkdir uploads
   ```

6. **Akses aplikasi**
   ```
   http://localhost/lexpina
   ```

---

## 📁 Struktur Folder

```
lexpina/
├── app/            # Halaman admin (dashboard, database, transaksi, anggota, dll.)
├── assets/         # CSS, JS, gambar
├── config/         # Konfigurasi database (configuration.php)
├── api/            # Endpoint API (login anggota, dll.)
├── public/         # File publik
├── uploads/        # File upload (bukti transfer, foto profil, dll.)
├── vendors/        # Library pihak ketiga
├── PHPWord/        # Library parsing dokumen Word
├── index.php       # Halaman utama / landing page
├── login.php       # Halaman login
├── langganan.php   # Halaman paket langganan
├── checkout.php    # Halaman checkout pembayaran
├── berita.php      # Halaman berita hukum
└── logout.php      # Proses logout
```

---

## 👤 Role Pengguna

| Role | Hak Akses |
|---|---|
| **ADMIN** | Akses penuh semua fitur & manajemen |
| **MEMBER** | Akses dokumen premium & fitur lengkap |
| **PENGGUNA** | Akses terbatas, perlu upgrade ke Member |

---

## 💳 Alur Langganan

1. Pengguna memilih paket di halaman **Langganan**
2. Diarahkan ke halaman **Checkout** dengan kode unik transfer
3. Pengguna transfer sesuai nominal & upload bukti pembayaran
4. Admin memvalidasi transaksi di dashboard
5. Akses Member otomatis aktif setelah divalidasi

---

## 📸 Screenshot

> *(Tambahkan screenshot di sini)*

---

## 📄 Lisensi

© 2025 PT LexPina Hukum Indonesia. Seluruh hak cipta dilindungi undang-undang.

---

## 🤝 Kontribusi

Pull request dan issue sangat disambut. Silakan fork dan buat branch baru untuk setiap fitur/perbaikan.
