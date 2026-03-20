<nav class="navbar navbar-light navbar-vertical navbar-expand-xl navbar-vibrant">
           
          <div class="d-flex align-items-center">
            <div class="toggle-icon-wrapper">

              <button class="btn navbar-toggler-humburger-icon navbar-vertical-toggle" data-bs-toggle="tooltip" data-bs-placement="left" title="Toggle Navigation"><span class="navbar-toggle-icon"><span class="toggle-line"></span></span></button>

            </div><a class="navbar-brand" href="<?php 
            if($akses=='POLSEK') {echo 'indexpolres'; }else if($akses=='POLRES') {echo 'indexpolres'; }else if($akses=='POLDA') {echo 'index'; }else{echo 'indexsubdit';} ?>">
              <div class="d-flex align-items-center py-3"><img class="me-2" src="../assets/img/sumsel.png" alt="" width="40" /><span class="font-sans-serif">PetaDigi</span>
              </div>
            </a>
          </div>
          <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
            <div class="navbar-vertical-content scrollbar">
              <ul class="navbar-nav flex-column mb-3" id="navbarVerticalNav">
                <li class="nav-item">
                  <?php if($akses == "POLDA" ): ?>
                  <a class="nav-link <?php echo ($menu=='dashboard') ? 'active' : ''; ?>" href="index" role="button"  aria-expanded="false" >
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-pie-chart"></span></span><span class="nav-link-text ps-1">Dashboard</span>
                    </div>
                  </a>  
                  <?php elseif($akses == "POLRES" ): ?>  
                    <a class="nav-link <?php echo ($menu=='dashboardpolres') ? 'active' : ''; ?>" href="indexpolres" role="button"  aria-expanded="false" >
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-pie-chart"></span></span><span class="nav-link-text ps-1">Dashboard Polres</span>
                    </div>
                  </a>  
                  <?php elseif($akses == "POLSEK" ): ?>  
                    <a class="nav-link <?php echo ($menu=='dashboardpolsek') ? 'active' : ''; ?>" href="indexpolsek" role="button"  aria-expanded="false" >
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-pie-chart"></span></span><span class="nav-link-text ps-1">Dashboard Polsek</span>
                    </div>
                  </a>
                  <?php else: ?>  
                    <a class="nav-link <?php echo ($menu=='dashboardsubdit') ? 'active' : ''; ?>" href="indexpolsek" role="button"  aria-expanded="false" >
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-pie-chart"></span></span><span class="nav-link-text ps-1">Dashboard Subdit</span>
                    </div>
                  </a>
                  <?php endif; ?>
                </li>
                <li class="nav-item">
                  <!-- label-->
                  <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                    <div class="col-auto navbar-vertical-label">Peta Digital
                    </div>
                    <div class="col ps-0">
                      <hr class="mb-0 navbar-vertical-divider" />
                    </div>
                  </div>
                  <a class="nav-link <?php echo ($menu=='kriminalitas') ? 'active' : ''; ?>" href="kriminalitas" role="button" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-user-secret"></span></span><span class="nav-link-text ps-1">Kriminalitas</span>
                    </div>
                  </a>
                   <a class="nav-link <?php echo ($menu=='kamtibmas') ? 'active' : ''; ?>" href="kamtibmas" role="button" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-bookmark"></span></span><span class="nav-link-text ps-1">Kasus Menonjol</span>
                    </div>
                  </a>
                  
                  <a class="nav-link <?php echo ($menu=='bencana') ? 'active' : ''; ?>" href="bencana" role="button" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-flash"></span></span><span class="nav-link-text ps-1">Potensi Bencana</span>
                    </div>
                  </a>         
                  
                  <a class="nav-link <?php echo ($menu=='lokasi') ? 'active' : ''; ?> d-none" href="lokasi" role="button" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-thumbtack"></span></span><span class="nav-link-text ps-1">Lokasi Penting</span>
                    </div>
                  </a>
                     <a class="nav-link <?php echo ($menu=='lalu-lintas') ? 'active' : ''; ?>" href="lalu-lintas" role="button" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-road"></span></span><span class="nav-link-text ps-1">Lalu Lintas</span>
                    </div>
                  </a>
                  
                </li>
                 <?php if($akses == "POLDA" ): ?>
                <li class="nav-item">
                  <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                    <div class="col-auto navbar-vertical-label">Managemen Import
                    </div>
                    <div class="col ps-0">
                      <hr class="mb-0 navbar-vertical-divider" />
                    </div>
                  </div>
                  <a class="nav-link <?php echo ($menu=='lapbul') ? 'active' : ''; ?>" href="lapbul" role="button" aria-expanded="false">
                    <!--div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-calendar"></span></span><span class="nav-link-text ps-1">Import Lapbul</span>
                    </div-->
                  </a>                     
                </li>
                <?php endif; ?>    
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
                    <?php if($akses == "POLDA" || $akses == "POLRES" ): ?>
                    <li class="nav-item ">
                        <a class="nav-link <?php echo ($menu=='polres') ? 'active' : ''; ?>" href="polres" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Polres</span>
                        </div>
                      </a>
                    </li>
                      <?php endif; ?>
                      <?php if($akses == "POLDA" || $akses == "POLRES" || $akses == "POLSEK"): ?>
                    <li class="nav-item ">
                        <a class="nav-link <?php echo ($menu=='polsek') ? 'active' : ''; ?>" href="polsek" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Polsek</span>
                        </div>
                      </a>
                    </li>
                      <?php endif; ?>
                       <?php if($akses == "POLDA" || $akses == "POLRES" ): ?>
                    <li class="nav-item ">
                        <a class="nav-link <?php echo ($menu=='kabupaten') ? 'active' : ''; ?>" href="kabupaten" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Kabupaten</span>
                        </div>
                      </a>
                    </li>
                    <?php endif; ?>
                     <?php if($akses == "POLDA" || $akses == "POLRES" || $akses == "POLSEK"): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($menu=='kecamatan') ? 'active' : ''; ?>" href="kecamatan" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Kecamatan</span>
                        </div>
                      </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($menu=='desa') ? 'active' : ''; ?>" href="desa" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Desa</span>
                        </div>
                      </a>
                    </li>
                    <?php endif; ?>
                    <?php if($akses == "POLDA" ): ?>
                     <li class="nav-item">
                        <a class="nav-link <?php echo ($menu=='sumber_dokumen') ? 'active' : ''; ?>" href="sumber-dokumen" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Sumber Dokumen</span>
                        </div>
                      </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($menu=='kategori-kriminal') ? 'active' : ''; ?>" href="kategori-kriminal" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Kategori Kriminal</span>
                        </div>
                      </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($menu=='sub-kategori-kriminal') ? 'active' : ''; ?>" href="sub-kategori-kriminal" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Sub Kategori Kriminal</span>
                        </div>
                      </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($menu=='kategori-bencana') ? 'active' : ''; ?>" href="kategori-bencana" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Kategori Bencana</span>
                        </div>
                      </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($menu=='kategori-kamtibmas') ? 'active' : ''; ?>" href="kategori-kamtibmas" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Kategori Kamtibmas</span>
                        </div>
                      </a>
                    </li>
                    
                     <li class="nav-item">
                        <a class="nav-link <?php echo ($menu=='kategori-lalin') ? 'active' : ''; ?>" href="kategori-lalin" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Kategori Lalu Lintas</span>
                        </div>
                      </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($menu=='jenis-jalan') ? 'active' : ''; ?>" href="jenis-jalan" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Jenis Jalan</span>
                        </div>
                      </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($menu=='modus-operandi') ? 'active' : ''; ?>" href="modus-operandi" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Modus Operandi</span>
                        </div>
                      </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($menu=='jenis-tkp') ? 'active' : ''; ?>" href="jenis-tkp" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Jenis TKP</span>
                        </div>
                      </a>
                    </li>
                    <?php endif; ?>
                  </ul>
                   <a class="nav-link <?php echo ($menu=='pengguna') ? 'active' : ''; ?>" href="pengguna" role="button" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-users"></span></span><span class="nav-link-text ps-1">Data Pengguna</span>
                    </div>
                  </a>
                    
                </li>
              </ul>
               
            </div>
          </div>
        </nav>