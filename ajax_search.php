<?php
require_once 'config/configuration.php';

$katakunci = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($katakunci) > 1) {
    try {
       $stmt = $pdo->prepare("
            SELECT id, judul, kategori, sumber, deskripsi 
            FROM `databases` 
            WHERE status = 1 
            AND (judul LIKE ? OR sumber LIKE ? OR deskripsi LIKE ?) 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        
        $search_term = "%" . $katakunci . "%";
        
        // Eksekusi array dengan mengirimkan $search_term 3 kali untuk mengisi 3 tanda tanya (?) di atas
        $stmt->execute([$search_term, $search_term, $search_term]);
        
        $results = $stmt->fetchAll();

        if (count($results) > 0) {
            foreach ($results as $row) {
                // 1. Highlight pada Judul
                $judul_highlight = str_ireplace($katakunci, "<mark style='background:#fff3cd; color:#856404; padding:0 2px; border-radius:3px;'>$katakunci</mark>", htmlspecialchars($row['judul']));
                
                $kategori_format = ucwords(str_replace('-', ' ', $row['kategori']));
                $sumber_format   = !empty($row['sumber']) ? " &bull; " . htmlspecialchars($row['sumber']) : "";

                // 2. Buat "Smart Snippet" dari deskripsi
                $deskripsi_bersih = strip_tags($row['deskripsi']); // Buang tag HTML
                $posisi_kata = stripos($deskripsi_bersih, $katakunci);
                
                $snippet_highlight = '';
                if ($posisi_kata !== false) {
                    // Jika kata kunci ada di deskripsi, potong 30 karakter sebelum dan 60 karakter sesudah kata tersebut
                    $start = max(0, $posisi_kata - 30);
                    $snippet = substr($deskripsi_bersih, $start, 90);
                    if ($start > 0) $snippet = "..." . $snippet;
                    $snippet .= "...";
                    
                    // Beri highlight pada snippet
                    $snippet_highlight = str_ireplace($katakunci, "<mark style='background:#fff3cd; color:#856404; padding:0 2px; border-radius:3px;'>$katakunci</mark>", htmlspecialchars($snippet));
                }

                echo '
                <a href="database_detail.php?id='.$row['id'].'&kategori='.$row['kategori'].'" class="live-search-item">
                    <div class="ls-kategori">'.$kategori_format . $sumber_format.'</div>
                    <div class="ls-judul">'.$judul_highlight.'</div>';
                
                // Tampilkan snippet di bawah judul hanya jika kata kunci ditemukan di deskripsi
                if (!empty($snippet_highlight)) {
                    echo '<div class="ls-snippet">'.$snippet_highlight.'</div>';
                }

                echo '</a>';
            }
            
            // Tombol lihat semua hasil
            echo '<a href="database.php?search='.urlencode($katakunci).'" class="live-search-more">Lihat semua hasil pencarian &raquo;</a>';
            
        } else {
            // TAMPILAN JIKA DATA KOSONG + TOMBOL REQUEST
            echo '
            <div class="live-search-empty" style="padding: 25px 15px;">
                <i class="fa-solid fa-file-circle-question" style="font-size: 30px; margin-bottom: 12px; color: #ddd;"></i>
                <p style="margin-bottom: 15px; color: #666; font-size: 14px;">Dokumen dengan kata kunci <strong>"'.htmlspecialchars($katakunci).'"</strong> tidak ditemukan.</p>
                <a href="saran.php?req='.urlencode($katakunci).'" class="btn-request-doc">
                    <i class="fa-solid fa-paper-plane"></i> Request Dokumen Ini
                </a>
            </div>';
        }

    } catch (PDOException $e) {
        echo '<div class="live-search-empty">Terjadi kesalahan sistem: '.$e->getMessage().'</div>';
    }
}
?>