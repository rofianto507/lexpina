<?php
require_once 'config/configuration.php';

header('Content-Type: application/xml; charset=UTF-8');

$base_url = rtrim($path, '/');

function sitemap_url($base_url, $loc, $changefreq, $priority, $lastmod = null) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($base_url . $loc) . "</loc>\n";
    if ($lastmod) {
        echo "    <lastmod>" . htmlspecialchars($lastmod) . "</lastmod>\n";
    }
    echo "    <changefreq>" . htmlspecialchars($changefreq) . "</changefreq>\n";
    echo "    <priority>" . htmlspecialchars($priority) . "</priority>\n";
    echo "  </url>\n";
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php
// Halaman statis utama
sitemap_url($base_url, '/index.php', 'daily', '1.0');
sitemap_url($base_url, '/database.php', 'daily', '0.9');
sitemap_url($base_url, '/berita.php', 'daily', '0.8');
sitemap_url($base_url, '/langganan.php', 'monthly', '0.7');
sitemap_url($base_url, '/tentang.php', 'monthly', '0.5');
sitemap_url($base_url, '/saran.php', 'monthly', '0.3');

// Dokumen hukum
try {
    $stmt = $pdo->query("SELECT id, slug, kategori, created_at, updated_at FROM `databases` WHERE status = 1 ORDER BY id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $lastmod = date('Y-m-d', strtotime($row['updated_at'] ?: $row['created_at']));
        sitemap_url(
            $base_url,
            '/database_detail.php?id=' . $row['id'] . '&slug=' . $row['slug'] . '&kategori=' . $row['kategori'],
            'monthly',
            '0.8',
            $lastmod
        );
    }
} catch (PDOException $e) {
    // Diamkan agar sitemap tetap valid meski satu bagian gagal
}

// Berita
try {
    $stmt2 = $pdo->query("SELECT slug, created_at, updated_at FROM beritas WHERE status = 1 ORDER BY id DESC");
    while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        $lastmod = date('Y-m-d', strtotime($row['updated_at'] ?: $row['created_at']));
        sitemap_url(
            $base_url,
            '/berita_detail.php?slug=' . $row['slug'],
            'weekly',
            '0.6',
            $lastmod
        );
    }
} catch (PDOException $e) {
    // Diamkan agar sitemap tetap valid meski satu bagian gagal
}
?>
</urlset>
