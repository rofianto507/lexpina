<?php
/**
 * Helper logging aktivitas user.
 * 
 * @param PDO $pdo
 * @param int $user_id   ID user yang melakukan aksi
 * @param string $aktivitas   Jenis aktivitas: add/update/delete
 * @param string $modul       Modul/data: kriminalitas/kamtibmas/dll
 * @param int $data_id        ID data terkait (nullable)
 * @param string $keterangan  Keterangan/deskripsi tambahan (opsional)
 */
function logUser($pdo, $user_id, $aktivitas, $modul, $data_id = null, $keterangan = '') {
    $sql = "INSERT INTO user_logs (user_id, aktivitas, modul, data_id, keterangan, created_at) 
            VALUES (:user_id, :aktivitas, :modul, :data_id, :keterangan, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id'    => $user_id,
        ':aktivitas'  => $aktivitas,
        ':modul'      => $modul,
        ':data_id'    => $data_id,
        ':keterangan' => $keterangan
    ]);
}