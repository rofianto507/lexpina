<?php
// ============================================
// IMPORT LAPORAN POLISI (.docx) KE MYSQL
// Menggunakan PHPWord - Install Manual
// ============================================

require_once 'PHPWord/src/PhpWord/Autoloader.php';
\PhpOffice\PhpWord\Autoloader::register();

use PhpOffice\PhpWord\IOFactory;

// ============================================
// KONFIGURASI DATABASE
// ============================================
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "petadigi_db";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// ============================================
// FUNGSI: Ambil semua teks dari elemen Word
// ============================================
function getElementText($element) {
    $text = '';
    if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
        foreach ($element->getElements() as $child) {
            if (method_exists($child, 'getText')) {
                $text .= $child->getText();
            }
        }
    } elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
        $text = $element->getText();
    } elseif (method_exists($element, 'getText')) {
        $text = $element->getText();
    }
    return $text;
}

// ============================================
// FUNGSI: Ambil teks dari Cell tabel
// ============================================
function getCellText($cell) {
    $text = '';
    foreach ($cell->getElements() as $element) {
        $t = getElementText($element);
        if (!empty($t)) {
            $text .= ($text !== '' ? ' ' : '') . $t;
        }
    }
    return trim($text);
}

// ============================================
// FUNGSI: Parse data key:value dari teks
// Contoh: "1. Nama : ABDI HARTENDI" → ['nama' => 'ABDI HARTENDI']
// ============================================
function parseKeyValue($text, $mappings) {
    $result = [];
    foreach ($mappings as $keyword => $fieldName) {
        // Cari pola: keyword diikuti ":" lalu value
        // Menangani variasi format dengan/tanpa nomor di depan
        $pattern = '/' . preg_quote($keyword, '/') . '\s*:\s*(.+?)(?=\n\s*\d+\.|$)/si';
        if (preg_match($pattern, $text, $match)) {
            $result[$fieldName] = trim($match[1]);
        }
    }
    return $result;
}

// ============================================
// FUNGSI UTAMA: Parse Laporan Polisi
// ============================================
function parseLaporanPolisi($filePath) {
    $phpWord = IOFactory::load($filePath);

    $allText    = '';
    $allCells   = [];

    // Kumpulkan semua teks dari dokumen
    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {

            // Teks biasa / paragraf
            $t = getElementText($element);
            if (!empty($t)) {
                $allText .= $t . "\n";
            }

            // Tabel
            if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                foreach ($element->getRows() as $row) {
                    $rowTexts = [];
                    foreach ($row->getCells() as $cell) {
                        $cellText = getCellText($cell);
                        if (!empty($cellText)) {
                            $rowTexts[] = $cellText;
                        }
                    }
                    if (!empty($rowTexts)) {
                        $lineText = implode(" ", $rowTexts);
                        $allText .= $lineText . "\n";
                        $allCells[] = $rowTexts;
                    }
                }
            }
        }
    }

    $data = [
        'nomor_lp'              => '',
        // Yang Melaporkan
        'pelapor_nama'          => '',
        'pelapor_no_identitas'  => '',
        'pelapor_kewarganegaraan' => '',
        'pelapor_jenis_kelamin' => '',
        'pelapor_tempat_tgl_lahir' => '',
        'pelapor_pekerjaan'     => '',
        'pelapor_agama'         => '',
        'pelapor_alamat'        => '',
        'pelapor_kontak'        => '',
        // Peristiwa
        'waktu_kejadian'        => '',
        'tempat_kejadian'       => '',
        'apa_yang_terjadi'      => '',
        'siapa_terlapor'        => '',
        'siapa_korban'          => '',
        'kapan_dilaporkan'      => '',
    ];

    // ============================================
    // 1. AMBIL NOMOR LP
    // ============================================
    if (preg_match('/Nomor\s*:?\s*(LP[\/\w\s\-\.]+)/i', $allText, $match)) {
        $data['nomor_lp'] = trim($match[1]);
    }

    // ============================================
    // 2. AMBIL BAGIAN "YANG MELAPORKAN"
    // ============================================
    // Ambil teks antara "YANG MELAPORKAN" dan "PERISTIWA YANG TERJADI"
    if (preg_match('/YANG MELAPORKAN(.+?)PERISTIWA YANG TERJADI/si', $allText, $match)) {
        $bagianPelapor = $match[1];

        $mappingPelapor = [
            'Nama'                  => 'pelapor_nama',
            'Nomor Identitas'       => 'pelapor_no_identitas',
            'Kewarganegaraan'       => 'pelapor_kewarganegaraan',
            'Jenis Kelamin'         => 'pelapor_jenis_kelamin',
            'Tempat/tanggal lahir'  => 'pelapor_tempat_tgl_lahir',
            'Pekerjaan'             => 'pelapor_pekerjaan',
            'Agama'                 => 'pelapor_agama',
            'Alamat'                => 'pelapor_alamat',
            'Kontak'                => 'pelapor_kontak',
        ];

        // Parse per baris
        $lines = explode("\n", $bagianPelapor);
        foreach ($lines as $line) {
            $line = trim($line);
            // Hapus nomor urut di depan (1. 2. dst)
            $line = preg_replace('/^\d+\.\s*/', '', $line);

            foreach ($mappingPelapor as $keyword => $field) {
                if (stripos($line, $keyword) !== false && strpos($line, ':') !== false) {
                    $parts = explode(':', $line, 2);
                    if (isset($parts[1])) {
                        $data[$field] = trim($parts[1]);
                    }
                    break;
                }
            }
        }
    }

    // ============================================
    // 3. AMBIL BAGIAN "PERISTIWA YANG TERJADI"
    // ============================================
    if (preg_match('/PERISTIWA YANG TERJADI(.+)/si', $allText, $match)) {
        $bagianPeristiwa = $match[1];

        $mappingPeristiwa = [
            'Waktu Kejadian'    => 'waktu_kejadian',
            'Tempat Kejadian'   => 'tempat_kejadian',
            'Apa Yang Terjadi'  => 'apa_yang_terjadi',
            'Siapa terlapor'    => 'siapa_terlapor',
            'Siapa korban'      => 'siapa_korban',
            'Kapan dilaporkan'  => 'kapan_dilaporkan',
        ];

        $lines = explode("\n", $bagianPeristiwa);
        $currentField = '';

        foreach ($lines as $line) {
            $line = trim($line);
            $line = preg_replace('/^\d+\.\s*/', '', $line);
            $matched = false;

            foreach ($mappingPeristiwa as $keyword => $field) {
                if (stripos($line, $keyword) !== false && strpos($line, ':') !== false) {
                    $parts = explode(':', $line, 2);
                    if (isset($parts[1])) {
                        $data[$field] = trim($parts[1]);
                    }
                    $currentField = $field;
                    $matched = true;
                    break;
                }
            }

            // Jika baris lanjutan (multi-line value)
            if (!$matched && !empty($currentField) && !empty($line)) {
                $data[$currentField] .= ' ' . $line;
            }
        }
    }

    // Bersihkan spasi berlebih
    foreach ($data as $key => $value) {
        $data[$key] = trim(preg_replace('/\s+/', ' ', $value));
    }

    return $data;
}

// ============================================
// PROSES IMPORT
// ============================================
$pesan = "";
$hasilParse = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_word'])) {

    $file = $_FILES['file_word'];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($ext !== 'docx') {
        $pesan = "<div class='alert error'>❌ Hanya file .docx yang diizinkan!</div>";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $pesan = "<div class='alert error'>❌ Gagal upload file!</div>";
    } else {

        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $uploadPath = $uploadDir . time() . '_' . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $uploadPath);

        try {
            // Parse dokumen
            $hasilParse = parseLaporanPolisi($uploadPath);

            // Insert ke database
            $stmt = $conn->prepare("
                INSERT INTO laporan_polisi (
                    nomor_lp,
                    pelapor_nama, pelapor_no_identitas, pelapor_kewarganegaraan,
                    pelapor_jenis_kelamin, pelapor_tempat_tgl_lahir, pelapor_pekerjaan,
                    pelapor_agama, pelapor_alamat, pelapor_kontak,
                    waktu_kejadian, tempat_kejadian, apa_yang_terjadi,
                    siapa_terlapor, siapa_korban, kapan_dilaporkan
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param("ssssssssssssssss",
                $hasilParse['nomor_lp'],
                $hasilParse['pelapor_nama'],
                $hasilParse['pelapor_no_identitas'],
                $hasilParse['pelapor_kewarganegaraan'],
                $hasilParse['pelapor_jenis_kelamin'],
                $hasilParse['pelapor_tempat_tgl_lahir'],
                $hasilParse['pelapor_pekerjaan'],
                $hasilParse['pelapor_agama'],
                $hasilParse['pelapor_alamat'],
                $hasilParse['pelapor_kontak'],
                $hasilParse['waktu_kejadian'],
                $hasilParse['tempat_kejadian'],
                $hasilParse['apa_yang_terjadi'],
                $hasilParse['siapa_terlapor'],
                $hasilParse['siapa_korban'],
                $hasilParse['kapan_dilaporkan']
            );

            if ($stmt->execute()) {
                $pesan = "<div class='alert success'>✅ Data Laporan Polisi berhasil diimport ke database! (ID: " . $stmt->insert_id . ")</div>";
            } else {
                $pesan = "<div class='alert error'>❌ Gagal menyimpan ke database: " . $stmt->error . "</div>";
            }
            $stmt->close();

            // Hapus file setelah diproses
            unlink($uploadPath);

        } catch (Exception $e) {
            $pesan = "<div class='alert error'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Laporan Polisi</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .card {
            background: #fff;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card h3 {
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 8px;
        }
        .form-group { margin-bottom: 15px; }
        .form-group label {
            display: block; margin-bottom: 5px;
            font-weight: 600; color: #555;
        }
        input[type="file"] {
            width: 100%; padding: 10px;
            border: 2px dashed #ccc; border-radius: 5px;
            background: #fafafa;
        }
        button {
            background: #4CAF50; color: white;
            padding: 12px 30px; border: none;
            border-radius: 5px; cursor: pointer;
            font-size: 16px; width: 100%;
        }
        button:hover { background: #45a049; }
        .alert {
            padding: 12px 20px; border-radius: 5px;
            margin-bottom: 15px; font-size: 14px;
        }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table {
            width: 100%; border-collapse: collapse; margin-top: 10px;
        }
        table th, table td {
            padding: 10px 12px; border: 1px solid #dee2e6;
            text-align: left; vertical-align: top;
        }
        table th {
            background: #4CAF50; color: white; width: 200px;
            white-space: nowrap;
        }
        table td { word-break: break-word; }
        .section-title {
            background: #333; color: white;
            padding: 8px 12px; font-weight: bold;
        }
        .info {
            background: #e7f3fe; border-left: 4px solid #2196F3;
            padding: 12px; margin-bottom: 15px;
            font-size: 13px; color: #333;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>📄 Import Laporan Polisi ke MySQL</h2>

    <div class="info">
        <strong>ℹ️ Petunjuk:</strong><br>
        - Upload file <strong>Laporan Polisi</strong> berformat <strong>.docx</strong><br>
        - Data yang diambil: <strong>Nomor LP</strong>, <strong>Yang Melaporkan</strong>, <strong>Peristiwa Yang Terjadi</strong>
    </div>

    <!-- FORM UPLOAD -->
    <div class="card">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Pilih File Laporan Polisi (.docx):</label>
                <input type="file" name="file_word" accept=".docx" required>
            </div>
            <button type="submit">📥 Import Laporan Polisi</button>
        </form>
    </div>

    <!-- HASIL -->
    <?php if (!empty($pesan)) : ?>
        <?= $pesan ?>
    <?php endif; ?>

    <?php if (!empty($hasilParse)) : ?>
        <div class="card">
            <h3>📋 Data yang Berhasil Di-Parse:</h3>
            <table>
                <!-- NOMOR LP -->
                <tr>
                    <td colspan="2" class="section-title">📌 NOMOR LP</td>
                </tr>
                <tr>
                    <th>Nomor LP</th>
                    <td><?= htmlspecialchars($hasilParse['nomor_lp']) ?></td>
                </tr>

                <!-- YANG MELAPORKAN -->
                <tr>
                    <td colspan="2" class="section-title">👤 YANG MELAPORKAN</td>
                </tr>
                <tr><th>Nama</th><td><?= htmlspecialchars($hasilParse['pelapor_nama']) ?></td></tr>
                <tr><th>Nomor Identitas</th><td><?= htmlspecialchars($hasilParse['pelapor_no_identitas']) ?></td></tr>
                <tr><th>Kewarganegaraan</th><td><?= htmlspecialchars($hasilParse['pelapor_kewarganegaraan']) ?></td></tr>
                <tr><th>Jenis Kelamin</th><td><?= htmlspecialchars($hasilParse['pelapor_jenis_kelamin']) ?></td></tr>
                <tr><th>Tempat/Tgl Lahir</th><td><?= htmlspecialchars($hasilParse['pelapor_tempat_tgl_lahir']) ?></td></tr>
                <tr><th>Pekerjaan</th><td><?= htmlspecialchars($hasilParse['pelapor_pekerjaan']) ?></td></tr>
                <tr><th>Agama</th><td><?= htmlspecialchars($hasilParse['pelapor_agama']) ?></td></tr>
                <tr><th>Alamat</th><td><?= htmlspecialchars($hasilParse['pelapor_alamat']) ?></td></tr>
                <tr><th>Kontak</th><td><?= htmlspecialchars($hasilParse['pelapor_kontak']) ?></td></tr>

                <!-- PERISTIWA YANG TERJADI -->
                <tr>
                    <td colspan="2" class="section-title">🔍 PERISTIWA YANG TERJADI</td>
                </tr>
                <tr><th>Waktu Kejadian</th><td><?= htmlspecialchars($hasilParse['waktu_kejadian']) ?></td></tr>
                <tr><th>Tempat Kejadian</th><td><?= htmlspecialchars($hasilParse['tempat_kejadian']) ?></td></tr>
                <tr><th>Apa Yang Terjadi</th><td><?= htmlspecialchars($hasilParse['apa_yang_terjadi']) ?></td></tr>
                <tr><th>Siapa Terlapor</th><td><?= htmlspecialchars($hasilParse['siapa_terlapor']) ?></td></tr>
                <tr><th>Siapa Korban</th><td><?= htmlspecialchars($hasilParse['siapa_korban']) ?></td></tr>
                <tr><th>Kapan Dilaporkan</th><td><?= htmlspecialchars($hasilParse['kapan_dilaporkan']) ?></td></tr>
            </table>
        </div>
    <?php endif; ?>

</div>
</body>
</html>