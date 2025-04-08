<?php
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE jalur_pendaftaran SET tanggal_buka = ?, tanggal_tutup = ? WHERE id = ?");
        
        foreach ($_POST['tanggal_buka'] as $jalur_id => $tanggal_buka) {
            $tanggal_tutup = $_POST['tanggal_tutup'][$jalur_id] ?? null;
            $stmt->execute([$tanggal_buka, $tanggal_tutup, $jalur_id]);
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Pengaturan tanggal jalur pendaftaran berhasil disimpan!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Gagal menyimpan pengaturan: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Metode request tidak valid!";
}

header("Location: pengaturan.php");
exit;