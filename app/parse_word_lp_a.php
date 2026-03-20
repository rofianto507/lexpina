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

    // Skip gambar
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

function cleanPipe($text) {
    $text = preg_replace('/^\|\s*/m', '', $text);
    $text = preg_replace('/\s*\|\s*$/m', '', $text);
    $text = preg_replace('/\s*\|\s*/', ' ', $text);
    $text = preg_replace('/  +/', ' ', $text);
    return trim($text);
}

function cleanValue($text) {
    $text = trim($text);
    $text = preg_replace('/^[\s:]+/', '', $text);
    $text = preg_replace('/^\d+\.\s*/', '', $text);
    return trim($text);
}

function cleanStopKeywords($text) {
    $stopKeywords = [
        'Pelapor atau Pengadu',
        'TINDAKAN YANG TELAH DILAKUKAN',
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
 

try {
    $phpWord = IOFactory::load($uploadPath);

    $allText   = '';
    $tableData = [];

    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {

            // Skip element yang bukan teks/tabel
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
                // Teks biasa / TextRun / dll
                try {
                    $t = getElementText($element);
                    if (!empty($t) && is_string($t)) {
                        $allText .= $t . "\n";
                    }
                } catch (\Exception $e) {
                    // Skip element yang bermasalah
                    continue;
                } catch (\Error $e) {
                    // Skip fatal error (object to string conversion)
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
        'pelapor'            => '',  // LP A tidak punya "YANG MELAPORKAN", akan dikosongkan
        'waktu_kejadian'     => '',
        'tempat_kejadian'    => '',
        'apa_yang_terjadi'   => '',
        'terlapor'           => '',
        'korban'             => '',
        'bagaimana_terjadi'  => '',
        'kapan_dilaporkan'   => '',
        'latitude'           => '',
        'longitude'          => '',
        'tindak_pidana'      => '',
        'saksi'              => '',
        'barang_bukti'       => '',
        'uraian'             => '',
        'tanggal_formatted'  => '',
        'tanggal_laporan_formatted' => '',
    ];

    // ============================================
    // 1. NOMOR LP
    // ============================================
    if (preg_match('/Nomor\s*:?\s*(LP[^\n]+)/i', $allText, $match)) {
        $data['no_lp'] = cleanPipe(trim($match[1]));
    }

    // ============================================
    // 2. PERISTIWA YANG TERJADI
    //    LP A langsung mulai dari sini (tidak ada "YANG MELAPORKAN")
    // ============================================
    if (preg_match('/PERISTIWA YANG TERJADI(.+?)(?=TINDAK PIDANA APA|$)/si', $allText, $match)) {
        $bagian = $match[1];

        // Mapping field LP A
        // Urutan penting: keyword yang lebih spesifik harus di atas
        $mappings = [
            'Waktu Kejadian'    => 'waktu_kejadian',
            'Tempat Kejadian'   => 'tempat_kejadian',
            'Apa Yang Terjadi'  => 'apa_yang_terjadi',
            'Bagaimana Terjadi' => 'bagaimana_terjadi',
            'Dilaporkan Pada'   => 'kapan_dilaporkan',
        ];

        // Field "4. Siapa" khusus: punya sub-field Terlapor dan Korban
        // Kita parse terpisah

        $lines = explode("\n", $bagian);
        $currentField = '';
        $inSiapa = false;       // Flag: sedang di bagian "Siapa"
        $siapaSub = '';         // Sub-field aktif: 'terlapor' atau 'korban'

        foreach ($lines as $line) {
            $line = trim($line);
            $line = trim($line, '| ');
            $line = preg_replace('/^\d+\.\s*/', '', $line);
            $line = cleanPipe($line);

            if (empty($line)) continue;

            $matched = false;

            // ---- Cek apakah ini header "Siapa" ----
            if (preg_match('/^Siapa\s*$/i', $line) || preg_match('/^Siapa\s*:/i', $line)) {
                $inSiapa = true;
                $siapaSub = '';
                $currentField = '';
                $matched = true;
            }

            // ---- Cek sub-field dalam bagian "Siapa" ----
            if ($inSiapa && !$matched) {
                if (stripos($line, 'Terlapor') === 0) {
                    $siapaSub = 'terlapor';
                    // Cek apakah ada data setelah "Terlapor"
                    if (preg_match('/^Terlapor\s*:?\s*(.+)/i', $line, $m)) {
                        $data['terlapor'] = cleanPipe(trim($m[1]));
                    }
                    $matched = true;
                }
                elseif (stripos($line, 'Korban') === 0) {
                    $siapaSub = 'korban';
                    if (preg_match('/^Korban\s*:?\s*(.+)/i', $line, $m)) {
                        $data['korban'] = cleanPipe(trim($m[1]));
                    }
                    $matched = true;
                }
                // Data lanjutan untuk sub-field terlapor/korban
                elseif (!empty($siapaSub) && !$matched) {
                    // Cek dulu apakah baris ini sebenarnya header field lain
                    $isOtherField = false;
                    foreach ($mappings as $kw => $f) {
                        if (stripos($line, $kw) !== false) {
                            $isOtherField = true;
                            break;
                        }
                    }
                    if (!$isOtherField) {
                        $data[$siapaSub] .= ' ' . $line;
                        $matched = true;
                    } else {
                        // Keluar dari bagian "Siapa"
                        $inSiapa = false;
                        $siapaSub = '';
                    }
                }
            }

            // ---- Cek mapping field biasa ----
            if (!$matched) {
                foreach ($mappings as $keyword => $field) {
                    if (stripos($line, $keyword) !== false && strpos($line, ':') !== false) {
                        $parts = explode(':', $line, 2);
                        if (isset($parts[1])) {
                            $data[$field] = cleanPipe(trim($parts[1]));
                        }
                        $currentField = $field;
                        $inSiapa = false;
                        $siapaSub = '';
                        $matched = true;
                        break;
                    }
                }
            }

            // ---- Data lanjutan (multi-line) ----
            if (!$matched && !empty($currentField) && !$inSiapa) {
                $data[$currentField] .= ' ' . $line;
            }
        }
    }

    // ============================================
    // 3. KOORDINAT
    // ============================================
    $coordSource = $data['tempat_kejadian'];
    if (preg_match('/TITIK KOORDINAT\s*(-?[\d\.]+)\s*[,\s]\s*(-?[\d\.]+)/i', $coordSource, $match)) {
        $data['latitude']  = trim($match[1]);
        $data['longitude'] = trim($match[2]);
    }

    // Fallback: cari di allText
    if (empty($data['latitude'])) {
        if (preg_match('/TITIK KOORDINAT\s*(-?[\d\.]+)\s*[,\s]\s*(-?[\d\.]+)/i', $allText, $match)) {
            $data['latitude']  = trim($match[1]);
            $data['longitude'] = trim($match[2]);
        }
    }

    // ============================================
    // 4. TABEL BAWAH: Tindak Pidana, Saksi, Barang Bukti, Uraian
    //    (sama seperti LP B)
    // ============================================
    $tindak_pidana_parts = [];
    $saksi_parts = [];
    $barang_bukti_parts = [];
    $uraian_parts = [];

    foreach ($tableData as $table) {
        $mode = '';

        foreach ($table as $rowIndex => $row) {
            $col0Text  = isset($row[0]) ? $row[0]['text'] : '';
            $col1Text  = isset($row[1]) ? $row[1]['text'] : '';
            $col0Parts = isset($row[0]) ? $row[0]['parts'] : [];
            $col1Parts = isset($row[1]) ? $row[1]['parts'] : [];
            $col0Upper = strtoupper($col0Text);
            $rowAllUpper = strtoupper($col0Text . ' ' . $col1Text);

            // Stop keywords
            if (strpos($rowAllUpper, 'PELAPOR ATAU PENGADU') !== false ||
                strpos($rowAllUpper, 'TINDAKAN YANG TELAH DILAKUKAN') !== false ||
                strpos($rowAllUpper, 'TINDAKAN YANG DILAKUKAN') !== false ||
                strpos($rowAllUpper, 'MENGETAHUI') !== false ||
                strpos($rowAllUpper, 'KA SPKT') !== false ||
                strpos($rowAllUpper, 'YANG MENERIMA LAPORAN') !== false) {
                $mode = '';
                continue;
            }

            // HEADER: TINDAK PIDANA + SAKSI
            if (strpos($col0Upper, 'TINDAK PIDANA') !== false) {
                $mode = 'TINDAK_SAKSI';

                $foundFromParts = false;
                if (count($col0Parts) > 1) {
                    for ($p = 1; $p < count($col0Parts); $p++) {
                        $val = cleanValue($col0Parts[$p]);
                        if (!empty($val)) { $tindak_pidana_parts[] = $val; $foundFromParts = true; }
                    }
                }
                if (count($col1Parts) > 1) {
                    for ($p = 1; $p < count($col1Parts); $p++) {
                        $val = cleanValue($col1Parts[$p]);
                        if (!empty($val)) { $saksi_parts[] = $val; $foundFromParts = true; }
                    }
                }

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

            // HEADER: BARANG BUKTI + URAIAN
            if (strpos($col0Upper, 'BARANG BUKTI') !== false) {
                $mode = 'BUKTI_URAIAN';

                $foundFromParts = false;
                if (count($col0Parts) > 1) {
                    for ($p = 1; $p < count($col0Parts); $p++) {
                        $val = cleanValue($col0Parts[$p]);
                        if (!empty($val)) { $barang_bukti_parts[] = $val; $foundFromParts = true; }
                    }
                }
                if (count($col1Parts) > 1) {
                    for ($p = 1; $p < count($col1Parts); $p++) {
                        $val = cleanValue($col1Parts[$p]);
                        if (!empty($val)) { $uraian_parts[] = $val; $foundFromParts = true; }
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

            // DATA baris
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

    $data['tindak_pidana'] = cleanStopKeywords(implode(' ', $tindak_pidana_parts));
    $data['saksi']         = cleanStopKeywords(implode(' ', $saksi_parts));
    $data['barang_bukti']  = cleanStopKeywords(implode(' ', $barang_bukti_parts));
    $data['uraian']        = cleanStopKeywords(implode(' ', $uraian_parts));

    // ============================================
    // 5. Parse Tanggal
    // ============================================
    $bulanMap = [
        'Januari'=>'01','Februari'=>'02','Maret'=>'03','April'=>'04',
        'Mei'=>'05','Juni'=>'06','Juli'=>'07','Agustus'=>'08',
        'September'=>'09','Oktober'=>'10','November'=>'11','Desember'=>'12'
    ];

    // Tanggal Kejadian: "19-02-2026" atau "Tanggal 19 Februari 2026 Sekitar Jam 12.00"
     if (!empty($data['waktu_kejadian'])) {
        // Format: dd-mm-yyyy
        if (preg_match('/(\d{2})-(\d{2})-(\d{4})/', $data['waktu_kejadian'], $m)) {
            // Hasil: 2026-02-19T00:00
            $data['tanggal_formatted'] = "{$m[3]}-{$m[2]}-{$m[1]}T00:00";
        }
        // Format: Tanggal dd Bulan yyyy ... Jam HH.MM
        elseif (preg_match('/(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})\s+.*?(?:Jam|Pukul)\s+(\d{2})[.\:](\d{2})/i', $data['waktu_kejadian'], $m)) {
            $tgl = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $bln = $bulanMap[$m[2]] ?? '01';
            $data['tanggal_formatted'] = "{$m[3]}-{$bln}-{$tgl}T{$m[4]}:{$m[5]}";
        }
    }

    // Tanggal Laporan (Dilaporkan Pada)
    if (!empty($data['kapan_dilaporkan'])) {
        if (preg_match('/(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})\s+.*?(?:Pukul|Jam)\s+(\d{2})[.\:](\d{2})/i', $data['kapan_dilaporkan'], $m)) {
            $tgl = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $bln = $bulanMap[$m[2]] ?? '01';
            $data['tanggal_laporan_formatted'] = "{$m[3]}-{$bln}-{$tgl}T{$m[4]}:{$m[5]}";
        }
    }
        // ============================================
    
    // ============================================
    // 6. Gabungkan "Bagaimana Terjadi" ke "keterangan" (Apa yang terjadi)
    //    Karena LP A punya narasi lengkap di "Bagaimana Terjadi"
    // ============================================
    // "Apa yang terjadi" biasanya singkat (misal: "Kecelakaan Lalu Lintas Tabrak Depan - Depan")
    // "Bagaimana Terjadi" adalah uraian panjang
    // Kita gabungkan keduanya

    // ============================================
    // 7. Bersihkan semua data
    // ============================================
    foreach ($data as $key => $value) {
        $value = cleanPipe($value);
        if ($key === 'pelapor' || $key === 'korban' || $key === 'saksi' || $key === 'terlapor' || $key === 'bagaimana_terjadi') {
            $data[$key] = trim(preg_replace('/[^\S\n]+/', ' ', $value));
        } else {
            $data[$key] = trim(preg_replace('/\s+/', ' ', $value));
        }
    }

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>