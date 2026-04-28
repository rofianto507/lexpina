<nav class="navbar navbar-light navbar-vertical navbar-expand-xl navbar-vibrant">
           
          <div class="d-flex align-items-center">
            <div class="toggle-icon-wrapper">

              <button class="btn navbar-toggler-humburger-icon navbar-vertical-toggle" data-bs-toggle="tooltip" data-bs-placement="left" title="Toggle Navigation"><span class="navbar-toggle-icon"><span class="toggle-line"></span></span></button>

            </div><a class="navbar-brand" href="<?php 
           if($akses=='ADMIN') echo 'index'; ?>">
              <div class="d-flex align-items-center py-3"><img class="me-2" src="../assets/img/icon.png" alt="" width="40" /><span class="font-sans-serif">LexPina</span>
              </div>
            </a>
          </div>
          <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
            <div class="navbar-vertical-content scrollbar">
              <ul class="navbar-nav flex-column mb-3" id="navbarVerticalNav">
                <li class="nav-item">
                  <?php if($akses == "ADMIN" ): ?>
                  <a class="nav-link <?php echo ($menu=='dashboard') ? 'active' : ''; ?>" href="index" role="button"  aria-expanded="false" >
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-pie-chart"></span></span><span class="nav-link-text ps-1">Dashboard</span>
                    </div>
                  </a>  
                
                  <?php endif; ?>
                </li>
                 
                
                <li class="nav-item">
                  <!-- label-->
                  <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                    <div class="col-auto navbar-vertical-label">Data Base
                    </div>
                    <div class="col ps-0">
                      <hr class="mb-0 navbar-vertical-divider" />
                    </div>
                  </div>
                  <a class="nav-link <?php echo ($menu=='peraturan') ? 'active' : ''; ?>" href="database?kategori=peraturan" role="button" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-folder"></span></span><span class="nav-link-text ps-1">Peraturan</span>
                    </div>
                  </a>
                   <a class="nav-link <?php echo ($menu=='peraturan-konsolidasi') ? 'active' : ''; ?>" href="database?kategori=peraturan-konsolidasi" role="button" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-folder"></span></span><span class="nav-link-text ps-1">Peraturan Konsolidasi</span>
                    </div>
                  </a>                  
                  <a class="nav-link <?php echo ($menu=='karya-ilmiah') ? 'active' : ''; ?>" href="database?kategori=karya-ilmiah" role="button" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-folder"></span></span><span class="nav-link-text ps-1">Karya Ilmiah</span>
                    </div>
                  </a>
                  <a class="nav-link <?php echo ($menu=='jurnal') ? 'active' : ''; ?>" href="database?kategori=jurnal" role="button" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-folder"></span></span><span class="nav-link-text ps-1">Jurnal</span>
                    </div>
                  </a>
                  <a class="nav-link <?php echo ($menu=='putusan') ? 'active' : ''; ?>" href="database?kategori=putusan" role="button" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-folder"></span></span><span class="nav-link-text ps-1">Putusan</span>
                    </div>
                  </a>
                  <a class="nav-link <?php echo ($menu=='template-perjanjian') ? 'active' : ''; ?>" href="database?kategori=template-perjanjian" role="button" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-folder"></span></span><span class="nav-link-text ps-1">Template Perjanjian</span>
                    </div>
                  </a>
                  <a class="nav-link <?php echo ($menu=='artikel') ? 'active' : ''; ?>" href="database?kategori=artikel" role="button" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-folder"></span></span><span class="nav-link-text ps-1">Artikel</span>
                    </div>
                  </a>
                </li>
                <li class="nav-item">
                  <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                     
                    <div class="col ps-0">
                      <hr class="mb-0 navbar-vertical-divider" />
                    </div>
                  </div>
                  <a class="nav-link dropdown-indicator" href="#transaksi" role="button" data-bs-toggle="collapse" aria-expanded="true" aria-controls="transaksi">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-shopping-cart"></span></span><span class="nav-link-text ps-1">Transaksi</span>
                    </div>
                  </a>
                  <ul class="nav collapse show" id="transaksi">

                    <li class="nav-item ">
                        <a class="nav-link <?php echo ($menu=='transaksi-pending') ? 'active' : ''; ?>" href="transaksi?status=PENDING" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Pending</span>
                        </div>
                      </a>
                    </li>
                    <li class="nav-item ">
                        <a class="nav-link <?php echo ($menu=='transaksi-lunas') ? 'active' : ''; ?>" href="transaksi?status=LUNAS" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Lunas</span>
                        </div>
                      </a>
                    </li>
                    <li class="nav-item ">
                        <a class="nav-link <?php echo ($menu=='transaksi-ditolak') ? 'active' : ''; ?>" href="transaksi?status=DITOLAK" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Ditolak</span>
                        </div>
                      </a>
                    </li>
                  </ul>
                </li>
                <li class="nav-item">
                  <!-- label-->
                  <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                    <div class="col-auto navbar-vertical-label">Interaksi
                    </div>
                    <div class="col ps-0">
                      <hr class="mb-0 navbar-vertical-divider" />
                    </div>
                  </div>
                  <a class="nav-link <?php echo ($menu=='berita') ? 'active' : ''; ?>" href="berita" role="button" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-newspaper-o"></span></span><span class="nav-link-text ps-1">Berita</span>
                    </div>
                  </a>
                  <a class="nav-link <?php echo ($menu=='saran') ? 'active' : ''; ?>" href="saran" role="button" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-envelope"></span></span><span class="nav-link-text ps-1">Saran & Masukan</span>
                    </div>
                  </a>
                  <a class="nav-link <?php echo ($menu=='banner') ? 'active' : ''; ?>" href="banner" role="button" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-bullhorn"></span></span><span class="nav-link-text ps-1">Banner Beranda</span>
                    </div>
                  </a>
                </li>
                  
                <li class="nav-item">
                  <!-- label-->
                  <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                    <div class="col-auto navbar-vertical-label">Konfigurasi
                    </div>
                    <div class="col ps-0">
                      <hr class="mb-0 navbar-vertical-divider" />
                    </div>
                  </div>
                   
                  <a class="nav-link dropdown-indicator" href="#master" role="button" data-bs-toggle="collapse" aria-expanded="true" aria-controls="master">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-database"></span></span><span class="nav-link-text ps-1">Master Data</span>
                    </div>
                  </a>
                  <ul class="nav collapse show" id="master">
                
                    <li class="nav-item ">
                        <a class="nav-link <?php echo ($menu=='kategori-berita') ? 'active' : ''; ?>" href="kategori-berita" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Kategori Berita</span>
                        </div>
                      </a>
                    </li>
                    <li class="nav-item ">
                        <a class="nav-link <?php echo ($menu=='produk') ? 'active' : ''; ?>" href="produk" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Data Produk</span>
                        </div>
                      </a>
                    </li>
                      
                  </ul>
                  <a class="nav-link <?php echo ($menu=='pengguna') ? 'active' : ''; ?>" href="pengguna" role="button" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-users"></span></span><span class="nav-link-text ps-1">Data Pengguna</span>
                    </div>
                  </a>
                   <a class="nav-link <?php echo ($menu=='member') ? 'active' : ''; ?>" href="member" role="button" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-star"></span></span><span class="nav-link-text ps-1">Data Member</span>
                    </div>
                  </a> 
                </li>
              </ul>
               
            </div>
          </div>
        </nav>