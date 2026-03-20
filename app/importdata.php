<?php
session_start();
include("../config/configuration.php");

 

// Aktifkan error reporting untuk debugging (hapus di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cek koneksi database
try {
    $pdo->query("SELECT 1");
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file     = $_FILES['csv_file']['tmp_name'];
    $fileType = $_FILES['csv_file']['type'];
    $fileSize = $_FILES['csv_file']['size'];

    // Validasi tipe & ukuran file
    $allowedTypes = ['text/csv', 'application/csv', 'text/plain', 'application/octet-stream'];
    $maxFileSize  = 10 * 1024 * 1024; // 10MB

    if (!in_array($fileType, $allowedTypes)) {
        $error = "File harus berupa CSV (.csv)";
    } elseif ($fileSize > $maxFileSize) {
        $error = "Ukuran file melebihi batas 10MB";
    } elseif (!is_uploaded_file($file)) {
        $error = "File tidak valid atau gagal diupload";
    } else {
        try {
            $handle = fopen($file, 'r');
            if ($handle === false) {
                throw new Exception("Gagal membuka file CSV.");
            }

            // Lewati header (baris pertama)
            $isHeader     = true;
            $successCount = 0;
            $errorMessages = [];
            $rowNumber    = 0;

            while (($row = fgetcsv($handle, 1000, ';')) !== false) {
                $rowNumber++;

                if ($isHeader) {
                    $isHeader = false;
                    continue;
                }

                // Ambil data kolom
                $id      = isset($row[0]) ? trim($row[0]) : '';
                $user_id = isset($row[1]) ? trim($row[1]) : '';

                // Lewati baris kosong atau tidak valid
                if (empty($id) || !is_numeric($id) || empty($user_id) || !is_numeric($user_id)) {
                    if (!empty($id) || !empty($user_id)) { // jangan catat baris benar-benar kosong
                        $errorMessages[] = "Baris $rowNumber: Data tidak valid (id: '$id', user_id: '$user_id')";
                    }
                    continue;
                }

                $id      = (int)$id;
                $user_id = (int)$user_id;

                try {
                    // Cek apakah id polsek ada
                    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM polseks WHERE id = ?");
                    $checkStmt->execute([$id]);
                    $exists = $checkStmt->fetchColumn();

                    if ($exists > 0) {
                        // Update user_id dan updated_at
                        $stmt = $pdo->prepare("
                            UPDATE polseks 
                            SET user_id    = ?,
                                updated_at = NOW()
                            WHERE id = ?
                        ");
                        $result = $stmt->execute([$user_id, $id]);

                        if ($result && $stmt->rowCount() > 0) {
                            $successCount++;
                        } else {
                            $errorMessages[] = "Baris $rowNumber: Tidak ada perubahan untuk id = $id (mungkin user_id sama)";
                        }
                    } else {
                        $errorMessages[] = "Baris $rowNumber: ID polsek $id tidak ditemukan di database";
                    }
                } catch (PDOException $e) {
                    $errorMessages[] = "Baris $rowNumber: Gagal update - " . $e->getMessage();
                }
            }

            fclose($handle);

            if ($successCount > 0) {
                $message = "Berhasil memperbarui <strong>$successCount</strong> data user_id polsek.";
            }
            if (!empty($errorMessages)) {
                $error = "Beberapa masalah terjadi:<br>" . implode("<br>", $errorMessages);
            }
            if (empty($message) && empty($error)) {
                $error = "Tidak ada data yang diproses (mungkin file kosong atau format salah).";
            }

        } catch (Exception $e) {
            $error = "Gagal memproses file CSV: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Update user_id Polsek dari CSV</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        h2 { color: #2c3e50; }
        .message { 
            background: #d4edda; 
            color: #155724; 
            padding: 12px; 
            border: 1px solid #c3e6cb; 
            border-radius: 4px; 
            margin: 15px 0;
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 12px; 
            border: 1px solid #f5c6cb; 
            border-radius: 4px; 
            margin: 15px 0;
        }
        form { margin-top: 20px; }
        button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>

    <h2>Update user_id Polsek dari File CSV</h2>
    <p>Upload file CSV untuk memperbarui kolom <code>user_id</code> pada table polseks berdasarkan id.</p>

    <?php if (!empty($message)): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label for="csv_file">Pilih file CSV (.csv):</label><br>
        <input type="file" name="csv_file" id="csv_file" accept=".csv" required><br><br>
        <button type="submit">Upload & Update Data</button>
    </form>

    <br>
    <p><strong>Format CSV yang diharapkan:</strong></p>
    <ul>
        <li>Pemisah kolom: titik koma <code>;</code></li>
        <li>Kolom 1: <code>id</code> (id polsek)</li>
        <li>Kolom 2: <code>user_id</code> (nilai yang akan diset)</li>
        <li>Baris pertama adalah header (akan dilewati otomatis)</li>
    </ul>

    <p><strong>Contoh isi file CSV:</strong></p>
    <pre>
id;user_id
101;5
102;7
118;12
123;15
    </pre>

</body>
</html>