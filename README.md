# 🗺️ PetaDigi — Peta Digital Kamtibmas Polda Sumsel

Aplikasi web berbasis PHP untuk menampilkan **Peta Digital Keamanan dan Ketertiban Masyarakat (Kamtibmas)** wilayah Polda Sumatera Selatan.

---

## ✨ Fitur Utama

- **Peta Interaktif** — Visualisasi data Kamtibmas berbasis Leaflet.js per Kabupaten, Kecamatan, dan Desa
- **Peta Kriminalitas** — Sebaran titik kejahatan, kategori, sub-kategori, tren, dan waktu
- **Peta Kasus Menonjol** — Data kamtibmas dan konflik wilayah
- **Peta Lalu Lintas** — Data kecelakaan dan pelanggaran lalu lintas
- **Peta Bencana** — Data kejadian bencana alam
- **Grafik & Statistik** — Chart ECharts untuk analisis data
- **Manajemen Data** — CRUD data oleh Polda, Polres, dan Polsek
- **Role-based Access** — POLDA, POLRES, POLSEK, DITLANTAS, dan Subdits
- **Import Word** — Parsing laporan LP Model A/B dari file `.docx`

---

## 🛠️ Teknologi

| Layer | Teknologi |
|---|---|
| Backend | PHP 8+, PDO MySQL |
| Database | MySQL / MariaDB |
| Frontend | Bootstrap 5, jQuery 3.7 |
| Peta | Leaflet.js 1.9.4 |
| Chart | Apache ECharts |
| Tabel | DataTables 1.13.7 |
| Word Parser | PHPWord |

---

## 🚀 Cara Instalasi

### Prasyarat
- XAMPP / Laragon / PHP 8+ + MySQL
- Web browser modern

### Langkah-langkah

1. **Clone repository**
   ```bash
   git clone https://github.com/YOUR_USERNAME/petadigi.git
   cd petadigi
   ```

2. **Salin file konfigurasi**
   ```bash
   cp config/configuration.example.php config/configuration.php
   ```

3. **Edit konfigurasi database**
   ```php
   // config/configuration.php
   $path = "http://localhost/pdk";  // sesuaikan URL
   $host = 'localhost';
   $db   = 'petadigi_db';           // nama database Anda
   $user = 'root';
   $pass = '';
   ```

4. **Import database**
   - Buat database baru: `petadigi_db`
   - Import file SQL yang tersedia

5. **Buat folder uploads** (jika belum ada)
   ```bash
   mkdir -p uploads app/uploads public/uploads
   ```

6. **Akses aplikasi**
   ```
   http://localhost/pdk
   ```

---

## 📁 Struktur Folder

```
pdk/
├── app/            # Halaman PHP utama (index, kriminalitas, bencana, dll.)
├── assets/         # CSS, JS, gambar
├── config/         # Konfigurasi database (configuration.php)
├── import/         # Script import GeoJSON wilayah
├── public/         # File publik
├── uploads/        # File upload (foto, lampiran)
├── vendors/        # Library pihak ketiga
└── index.php       # Entry point / halaman login
```

---

## 👤 Role Pengguna

| Role | Hak Akses |
|---|---|
| **POLDA** | Akses penuh semua data & fitur |
| **POLRES** | Data wilayah Polres sendiri |
| **POLSEK** | Data wilayah Polsek sendiri |
| **DITLANTAS** | Data lalu lintas |
| **Subdits** | Data sesuai subdit |

---

## 📸 Screenshot

> *(Tambahkan screenshot di sini)*

---

## 📄 Lisensi

Proyek ini dikembangkan untuk keperluan internal Polda Sumatera Selatan.

---

## 🤝 Kontribusi

Pull request dan issue sangat disambut. Silakan fork dan buat branch baru untuk setiap fitur/perbaikan.
