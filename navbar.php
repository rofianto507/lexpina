<nav class="main-nav">
    <div class="menu-toggle" id="mobile-menu">
        <i class="fa-solid fa-bars"></i>
    </div>

    <ul>
        <li><a href="index.php" class="<?php echo ($active_page == 'beranda') ? 'active' : ''; ?>">Beranda</a></li>
        
        <li class="dropdown">
            <a href="#" class="<?php echo ($active_page == 'database') ? 'active' : ''; ?>">Data Base <i class="fa-solid fa-chevron-down"></i></a>
            <div class="dropdown-content">
                <div class="dropdown-column">
                    <a href="database.php?kategori=peraturan">Peraturan</a>
                    <a href="database.php?kategori=peraturan-konsolidasi">Peraturan Konsolidasi</a>
                    <a href="database.php?kategori=karya-ilmiah">Karya Ilmiah</a>
                    <a href="database.php?kategori=jurnal">Jurnal</a>
                </div>
                <div class="dropdown-column">
                    <a href="database.php?kategori=putusan">Putusan</a>
                    <a href="database.php?kategori=template-perjanjian">Template Perjanjian</a>
                    <a href="database.php?kategori=artikel">Artikel</a>
                </div>
            </div>
        </li>

        <li><a href="berita.php" class="<?php echo ($active_page == 'berita') ? 'active' : ''; ?>">Berita</a></li>
        <li><a href="tentang.php" class="<?php echo ($active_page == 'tentang') ? 'active' : ''; ?>">Tentang</a></li>
    </ul>
</nav>