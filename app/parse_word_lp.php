<?php
session_start();
include("../config/configuration.php");

if ($_SESSION["nama"] == "" || $_SESSION["id"] == "") {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../PHPWord/src/PhpWord/Autoloader.php';
\PhpOffice\PhpWord\Autoloader::register();

use PhpOffice\PhpWord\IOFactory;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file_word'])) {
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file_word'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if ($ext !== 'docx') {
    echo json_encode(['error' => 'Hanya file .docx yang diizinkan']);
    exit;
}

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Gagal upload file']);
    exit;
}

$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
$uploadPath = $uploadDir . time() . '_' . basename($file['name']);
move_uploaded_file($file['tmp_name'], $uploadPath);

// ============================================
// FUNGSI HELPER
// ============================================
function getElementText($element) {
    $text = '';

    // Skip gambar untuk menghindari error
    if ($element instanceof \PhpOffice\PhpWord\Element\Image) {
        return '';
    }

    if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
        foreach ($element->getElements() as $child) {
            if ($child instanceof \PhpOffice\PhpWord\Element\Image) continue;
            if (method_exists($child, 'getText')) {
                $val = $child->getText();
                if (is_string($val)) {
                    $text .= $val;
                }
            }
        }
    } elseif (method_exists($element, 'getText')) {
        $val = $element->getText();
        if (is_string($val)) {
            $text = $val;
        }
    }
    return $text;
}

/**
 * Ambil teks dari cell, tapi PISAHKAN per paragraf/elemen
 * Ini penting karena header dan data bisa jadi elemen berbeda dalam cell yang sama
 */
function getCellTextParts($cell) {
    $parts = [];
    foreach ($cell->getElements() as $element) {
        $t = getElementText($element);
        if (!empty(trim($t))) {
            $parts[] = trim($t);
        }
    }
    return $parts;
}

function getCellText($cell) {
    $parts = getCellTextParts($cell);
    return implode(' ', $parts);
}

/**
 * Bersihkan teks dari label-label yang tidak diinginkan
 * Potong teks mulai dari keyword tertentu sampai akhir
 */
function cleanStopKeywords($text) {
    $stopKeywords = [
        'Pelapor atau Pengadu',
        'TINDAKAN YANG DILAKUKAN',
        'MENGETAHUI',
        'KA SPKT',
        'Yang menerima laporan',
    ];
    foreach ($stopKeywords as $kw) {
        $pos = stripos($text, $kw);
        if ($pos !== false) {
            $text = substr($text, 0, $pos);
        }
    }
    return trim($text);
}
function normalizeMinusSign($text) {
    $search = [
        "\xE2\x80\x93", // – EN DASH (U+2013) ← paling sering di Word/DOCX
        "\xE2\x80\x94", // — EM DASH (U+2014)
        "\xE2\x88\x92", // − MINUS SIGN (U+2212)
        "\xE2\x80\x90", // ‐ HYPHEN (U+2010)
        "\xE2\x80\x91", // ‑ NON-BREAKING HYPHEN (U+2011)
        "\xC2\xAD",     // ­ SOFT HYPHEN (U+00AD)
        "\xC2\xA0",     // NO-BREAK SPACE (U+00A0) ← Word sering pakai ini
    ];
    $replace = [
        '-', '-', '-', '-', '-', '-', ' '
    ];
    return str_replace($search, $replace, $text);
}

try {
        // Hapus relasi gambar dari docx agar tidak error saat load
    $zip = new ZipArchive();
    if ($zip->open($uploadPath) === true) {
        $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
        if ($relsXml) {
            $relsXml = preg_replace(
                '/<Relationship[^>]*Type="[^"]*\/image"[^>]*\/>/i',
                '',
                $relsXml
            );
            $zip->addFromString('word/_rels/document.xml.rels', $relsXml);
        }
        $zip->close();
    }

    $phpWord = IOFactory::load($uploadPath);

    $allText    = '';
    $tableData  = [];

    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {

            // Skip gambar
            if ($element instanceof \PhpOffice\PhpWord\Element\Image) continue;

            if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                $currentTable = [];
                foreach ($element->getRows() as $row) {
                    $cells = $row->getCells();
                    $rowData = [];
                    foreach ($cells as $cell) {
                        $rowData[] = [
                            'text'  => getCellText($cell),
                            'parts' => getCellTextParts($cell),
                        ];
                    }
                    $currentTable[] = $rowData;

                    $lineTexts = [];
                    foreach ($rowData as $rd) {
                        if (!empty($rd['text'])) $lineTexts[] = $rd['text'];
                    }
                    if (!empty($lineTexts)) {
                        $allText .= implode(" | ", $lineTexts) . "\n";
                    }
                }
                $tableData[] = $currentTable;
            } else {
                try {
                    $t = getElementText($element);
                    if (!empty($t) && is_string($t)) {
                        $allText .= $t . "\n";
                    }
                } catch (\Exception $e) {
                    continue;
                } catch (\Error $e) {
                    continue;
                }
            }
        }
    }

    unlink($uploadPath);

    // ============================================
    // INISIALISASI DATA
    // ============================================
    $data = [
        'no_lp'              => '',
        'pelapor'            => '',
        'waktu_kejadian'     => '',
        'tempat_kejadian'    => '',
        'apa_yang_terjadi'   => '',
        'terlapor'           => '',
        'korban'             => '',
        'kapan_dilaporkan'   => '',
        'latitude'           => '',
        'longitude'          => '',
        'tindak_pidana'      => '',
        'saksi'              => '',
        'barang_bukti'       => '',
        'uraian'             => '',
        'tanggal_laporan_formatted' => '',
        'waktu_kejadian_formatted' => '',
    ];

    // ============================================
    // 1. NOMOR LP
    // ============================================
    if (preg_match('/Nomor\s*:?\s*(LP[^\n]+)/i', $allText, $match)) {
        $data['no_lp'] = trim($match[1]);
    }
        // ============================================
    // FUNGSI TAMBAHAN: Bersihkan pipe/garis lurus dari tabel
    // ============================================
    
    /**
     * Bersihkan tanda | (pipe) dari hasil parsing tabel Word
     * "| Nama | : | ABDI" → "Nama : ABDI"
     * "| --CURAT" → "--CURAT"  
     * "alamat Kab Oku|, nomor" → "alamat Kab Oku, nomor"
     */
    function cleanPipe($text) {
        // Hapus pipe di awal baris
        $text = preg_replace('/^\|\s*/m', '', $text);
        // Hapus pipe di akhir baris
        $text = preg_replace('/\s*\|\s*$/m', '', $text);
        // Hapus pipe yang berdiri sendiri (diapit spasi) sebagai separator kolom
        // " | : | " → " : "
        $text = preg_replace('/\s*\|\s*/', ' ', $text);
        // Bersihkan spasi berlebih
        $text = preg_replace('/  +/', ' ', $text);
        return trim($text);
    }
    // ============================================
    // 2. YANG MELAPORKAN
    // ============================================
    if (preg_match('/YANG MELAPORKAN(.+?)PERISTIWA YANG TERJADI/si', $allText, $match)) {
        $bagian = $match[1];
        $infoPelapor = [];
        $lines = explode("\n", $bagian);
        foreach ($lines as $line) {
            $line = trim($line);
            $line = preg_replace('/^\d+\.\s*/', '', $line);
            $line = cleanPipe($line);
            if (!empty($line) && strpos($line, ':') !== false) {
                $infoPelapor[] = $line;
            }
        }
        $data['pelapor'] = implode("\n", $infoPelapor);
    }

    // ============================================
    // 3. PERISTIWA YANG TERJADI
    // ============================================
    if (preg_match('/PERISTIWA YANG TERJADI(.+?)(?=TINDAK PIDANA APA|$)/si', $allText, $match)) {
        $bagian = $match[1];

        $mappings = [
            'Waktu Kejadian'   => 'waktu_kejadian',
            'Tempat Kejadian'  => 'tempat_kejadian',
            'Apa Yang Terjadi' => 'apa_yang_terjadi',
            'Siapa terlapor'   => 'terlapor',
            'Siapa korban'     => 'korban',
            'Kapan dilaporkan' => 'kapan_dilaporkan',
        ];

        $lines = explode("\n", $bagian);
        $currentField = '';

        foreach ($lines as $line) {
            $line = trim($line);
            $line = trim($line, '| ');
            $line = preg_replace('/^\d+\.\s*/', '', $line);
            $line = cleanPipe($line);
            $matched = false;

            foreach ($mappings as $keyword => $field) {
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

            if (!$matched && !empty($currentField) && !empty($line)) {
                $data[$currentField] .= ' ' . $line;
            }
        }
    }

    // ============================================
    // 4. KOORDINAT
    // ============================================
    if (preg_match('/TITIK KOORDINAT(.{0,40})/i', $allText, $debugMatch)) {
        error_log("RAW dari DOCX: [" . $debugMatch[1] . "]");
        error_log("HEX: " . bin2hex($debugMatch[1]));
    }
  
   if (preg_match('/TITIK KOORDINAT\s*(-?[\d\.]+)\s*[,\s]\s*(-?[\d\.]+)/i', $allText, $match)) {
        $data['latitude']  = trim($match[1]);
        $data['longitude'] = trim($match[2]);
    }

     

      // ============================================
    // 5. TABEL BAWAH: Tindak Pidana, Saksi, Barang Bukti, Uraian
    // ============================================

    $tindak_pidana_parts = [];
    $saksi_parts = [];
    $barang_bukti_parts = [];
    $uraian_parts = [];

    /**
     * Bersihkan value: hapus titik dua, spasi, dan nomor urut di awal
     */
    function cleanValue($text) {
        $text = trim($text);
        // Hapus titik dua di awal (bisa lebih dari satu, dengan spasi)
        $text = preg_replace('/^[\s:]+/', '', $text);
        // Hapus nomor urut di awal, contoh: "1. " atau "1." 
        $text = preg_replace('/^\d+\.\s*/', '', $text);
        return trim($text);
    }

    foreach ($tableData as $table) {
        $mode = '';

        foreach ($table as $rowIndex => $row) {
            $col0Text  = isset($row[0]) ? $row[0]['text'] : '';
            $col1Text  = isset($row[1]) ? $row[1]['text'] : '';
            $col0Parts = isset($row[0]) ? $row[0]['parts'] : [];
            $col1Parts = isset($row[1]) ? $row[1]['parts'] : [];
            $col0Upper = strtoupper($col0Text);
            $col1Upper = strtoupper($col1Text);
            $rowAllUpper = strtoupper($col0Text . ' ' . $col1Text);

            // ---- Stop keywords: berhenti parsing ----
            if (strpos($rowAllUpper, 'PELAPOR ATAU PENGADU') !== false ||
                strpos($rowAllUpper, 'TINDAKAN YANG DILAKUKAN') !== false ||
                strpos($rowAllUpper, 'MENGETAHUI') !== false ||
                strpos($rowAllUpper, 'KA SPKT') !== false ||
                strpos($rowAllUpper, 'YANG MENERIMA LAPORAN') !== false) {
                $mode = '';
                continue;
            }

            // ---- Deteksi HEADER: TINDAK PIDANA + SAKSI ----
            if (strpos($col0Upper, 'TINDAK PIDANA') !== false) {
                $mode = 'TINDAK_SAKSI';

                // Coba ambil dari parts (paragraf terpisah di cell)
                $foundFromParts = false;
                if (count($col0Parts) > 1) {
                    for ($p = 1; $p < count($col0Parts); $p++) {
                        $val = cleanValue($col0Parts[$p]);
                        if (!empty($val)) {
                            $tindak_pidana_parts[] = $val;
                            $foundFromParts = true;
                        }
                    }
                }
                if (count($col1Parts) > 1) {
                    for ($p = 1; $p < count($col1Parts); $p++) {
                        $val = cleanValue($col1Parts[$p]);
                        if (!empty($val)) {
                            $saksi_parts[] = $val;
                            $foundFromParts = true;
                        }
                    }
                }

                // Jika tidak ada parts terpisah, coba split dari teks setelah header label
                if (!$foundFromParts) {
                    if (preg_match('/TINDAK PIDANA APA\s*:?\s*(.+)/si', $col0Text, $m)) {
                        $val = cleanValue($m[1]);
                        if (!empty($val)) $tindak_pidana_parts[] = $val;
                    }
                    if (preg_match('/NAMA DAN ALAMAT SAKSI[^:]*:?\s*(.+)/si', $col1Text, $m)) {
                        $val = cleanValue($m[1]);
                        if (!empty($val)) $saksi_parts[] = $val;
                    }
                }

                continue;
            }

            // ---- Deteksi HEADER: BARANG BUKTI + URAIAN ----
            if (strpos($col0Upper, 'BARANG BUKTI') !== false) {
                $mode = 'BUKTI_URAIAN';

                $foundFromParts = false;
                if (count($col0Parts) > 1) {
                    for ($p = 1; $p < count($col0Parts); $p++) {
                        $val = cleanValue($col0Parts[$p]);
                        if (!empty($val)) {
                            $barang_bukti_parts[] = $val;
                            $foundFromParts = true;
                        }
                    }
                }
                if (count($col1Parts) > 1) {
                    for ($p = 1; $p < count($col1Parts); $p++) {
                        $val = cleanValue($col1Parts[$p]);
                        if (!empty($val)) {
                            $uraian_parts[] = $val;
                            $foundFromParts = true;
                        }
                    }
                }

                if (!$foundFromParts) {
                    if (preg_match('/BARANG BUKTI\s*:?\s*(.+)/si', $col0Text, $m)) {
                        $val = cleanValue($m[1]);
                        if (!empty($val)) $barang_bukti_parts[] = $val;
                    }
                    if (preg_match('/URAIAN SINGKAT[^:]*:?\s*(.+)/si', $col1Text, $m)) {
                        $val = cleanValue($m[1]);
                        if (!empty($val)) $uraian_parts[] = $val;
                    }
                }

                continue;
            }

            // ---- DATA baris (baris setelah header) ----
            if ($mode === 'TINDAK_SAKSI') {
                $val0 = cleanValue($col0Text);
                $val1 = cleanValue($col1Text);
                if (!empty($val0)) $tindak_pidana_parts[] = $val0;
                if (!empty($val1)) $saksi_parts[] = $val1;
            }
            else if ($mode === 'BUKTI_URAIAN') {
                $val0 = cleanValue($col0Text);
                $val1 = cleanValue($col1Text);
                if (!empty($val0)) $barang_bukti_parts[] = $val0;
                if (!empty($val1)) $uraian_parts[] = $val1;
            }
        }
    }

    // Gabungkan dan bersihkan stop keywords
    $data['tindak_pidana'] = cleanStopKeywords(implode(' ', $tindak_pidana_parts));
    $data['saksi']         = cleanStopKeywords(implode(' ', $saksi_parts));
    $data['barang_bukti']  = cleanStopKeywords(implode(' ', $barang_bukti_parts));
    $data['uraian']        = cleanStopKeywords(implode(' ', $uraian_parts));

    // ============================================
    // 6. Parse Tanggal Laporan
    // ============================================
    if (!empty($data['kapan_dilaporkan'])) {
        $bulanMap = [
            'Januari'=>'01','Februari'=>'02','Maret'=>'03','April'=>'04',
            'Mei'=>'05','Juni'=>'06','Juli'=>'07','Agustus'=>'08',
            'September'=>'09','Oktober'=>'10','November'=>'11','Desember'=>'12'
        ];
        if (preg_match('/(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})\s+Pukul\s+(\d{2})[.\:](\d{2})/i', $data['kapan_dilaporkan'], $m)) {
            $tgl = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $bln = $bulanMap[$m[2]] ?? '01';
            $thn = $m[3];
            $jam = $m[4];
            $mnt = $m[5];
            $data['tanggal_laporan_formatted'] = "{$thn}-{$bln}-{$tgl}T{$jam}:{$mnt}";
        }
    }
    // ============================================
    // 6b. Parse Tanggal kejadian (jika format sama dengan kapan dilaporkan)
    // ============================================
    if (!empty($data['waktu_kejadian'])) {
        $bulanMap = [
            'Januari'=>'01','Februari'=>'02','Maret'=>'03','April'=>'04',
            'Mei'=>'05','Juni'=>'06','Juli'=>'07','Agustus'=>'08',
            'September'=>'09','Oktober'=>'10','November'=>'11','Desember'=>'12'
        ];
        if (preg_match('/(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})\s+Pukul\s+(\d{2})[.\:](\d{2})/i', $data['waktu_kejadian'], $m)) {
            $tgl = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $bln = $bulanMap[$m[2]] ?? '01';
            $thn = $m[3];
            $jam = $m[4];
            $mnt = $m[5];
            $data['waktu_kejadian_formatted'] = "{$thn}-{$bln}-{$tgl}T{$jam}:{$mnt}";
        }
    }

    // ============================================
    // 7. Bersihkan data
    // ============================================
    foreach ($data as $key => $value) {
        $value = cleanPipe($value);
        if ($key === 'pelapor' || $key === 'korban' || $key === 'saksi') {
            $data[$key] = trim(preg_replace('/[^\S\n]+/', ' ', $value));
        } else {
            $data[$key] = trim(preg_replace('/\s+/', ' ', $value));
        }
    }

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
   // echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
    echo json_encode([
        'error' => 'Error: ' . $e->getMessage(),
        'file'  => $e->getFile(),
        'line'  => $e->getLine()
    ]);
}
?>