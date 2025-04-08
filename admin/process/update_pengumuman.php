<?php
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("UPDATE jalur_pendaftaran SET tanggal_pengumuman = ? WHERE id = ?");
        
        foreach ($_POST['tanggal_pengumuman'] as $id => $tanggal) {
            $stmt->execute([$tanggal, $id]);
        }
        
        $_SESSION['success'] = "Tanggal pengumuman berhasil diperbarui!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal memperbarui tanggal pengumuman: " . $e->getMessage();
    }
}

header("Location: ../pengaturan.php");
exit;