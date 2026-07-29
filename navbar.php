<nav class="main-nav">
    <div class="menu-toggle" id="mobile-menu">
        <i class="fa-solid fa-bars"></i>
    </div>

    <ul>
        <li><a href="index" class="<?php echo ($active_page == 'beranda') ? 'active' : ''; ?>">Beranda</a></li>
        
        <li class="dropdown">
            <a href="#" class="<?php echo ($active_page == 'database') ? 'active' : ''; ?>">Data Base <i class="fa-solid fa-chevron-down"></i></a>
            <div class="dropdown-content">
                <div class="dropdown-column">
                    <a href="database?kategori=peraturan">Peraturan</a>
                    <a href="database?kategori=peraturan-konsolidasi">Peraturan Konsolidasi</a>
                    <a href="database?kategori=karya-ilmiah">Karya Ilmiah</a>
                    <a href="database?kategori=jurnal">Jurnal</a>
                </div>
                <div class="dropdown-column">
                    <a href="database?kategori=putusan">Putusan</a>
                    <a href="database?kategori=template-perjanjian">Template Perjanjian</a>
                    <a href="database?kategori=artikel">Artikel</a>
                </div>
            </div>
        </li>

        <li><a href="berita" class="<?php echo ($active_page == 'berita') ? 'active' : ''; ?>">Berita</a></li>
        <li><a href="tentang" class="<?php echo ($active_page == 'tentang') ? 'active' : ''; ?>">Tentang</a></li>
    </ul>
</nav>