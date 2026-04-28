<?php 
$active_page = ''; 
include 'header.php'; 
include 'navbar.php'; 

// 1. Ambil data produk dari database menggunakan PDO
try {
    $stmt = $pdo->prepare("SELECT * FROM produks WHERE status = 1 ORDER BY durasi_bulan ASC");
    $stmt->execute();
    $produks = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Gagal memuat paket: " . $e->getMessage();
}
?>

    <main class="subscription-page">
        <div class="pricing-container">
            
            <div class="pricing-header">
                <h4>Go Smarter with...</h4>
                <h2>"THE MOST POWERFULL LAW WEBSITE"</h2>
                <h1 class="premium-text"><i class="fa-solid fa-crown"></i> Premium</h1>
                <p>Buka akses tanpa batas ke jutaan dokumen hukum, putusan pengadilan, dan fitur analisis tingkat lanjut.</p>
            </div>

            <div class="pricing-grid">
                
                <?php foreach ($produks as $row): 
                    // Logika penentuan class berdasarkan badge untuk styling khusus
                    $card_class = '';
                    $btn_class = '';
                    $badge_class = '';

                    if ($row['badge'] == 'RECOMMENDED') {
                        $card_class = 'recommended';
                        $btn_class = 'btn-recommended';
                        $badge_class = 'badge-recommended';
                    } elseif ($row['badge'] == 'BEST VALUE!!!') {
                        $card_class = 'best-value';
                        $btn_class = 'btn-best';
                        $badge_class = 'badge-best-value';
                    }
                ?>

                <div class="pricing-card <?php echo $card_class; ?>">
                    
                    <?php if (!empty($row['badge'])): ?>
                        <div class="<?php echo $badge_class; ?>">
                            <?php echo ($row['badge'] == 'BEST VALUE!!!') ? '<i class="fa-solid fa-fire"></i> ' : ''; ?>
                            <?php echo $row['badge']; ?>
                        </div>
                    <?php endif; ?>

                    <div class="card-header">
                        <h3><?php echo $row['nama_paket']; ?></h3>
                        <p><?php echo $row['deskripsi']; ?></p>
                    </div>

                    <div class="card-price">
                        <?php if (!empty($row['harga_coret'])): ?>
                            <span class="price-strike">Rp <?php echo number_format($row['harga_coret'], 0, ',', '.'); ?>/bln</span>
                            <br>
                        <?php endif; ?>
                        
                        <span class="currency">Rp</span>
                        <span class="amount"><?php echo number_format($row['harga_per_bulan'], 0, ',', '.'); ?></span>
                        <span class="period">/bulan</span>
                    </div>

                    <?php
                        $is_logged_in = isset($_SESSION['id']) || isset($_SESSION['user_id']);
                        
                        if ($is_logged_in): 
                    ?>
                        <a href="checkout.php?id=<?php echo $row['id']; ?>" class="btn-pricing <?php echo $btn_class; ?>">Pilih Paket</a>
                    <?php else: ?>
                        <button type="button" class="btn-pricing <?php echo $btn_class; ?> btn-login-trigger" data-checkout-url="checkout.php?id=<?php echo $row['id']; ?>">Pilih Paket</button>
                    <?php endif; ?>
                </div>

                <?php endforeach; ?>

            </div>
            
        </div>
    </main>

<?php include 'footer.php'; ?>