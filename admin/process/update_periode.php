<?php
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("UPDATE jalur_pendaftaran SET tanggal_buka = ?, tanggal_tutup = ? WHERE id = ?");
        
        foreach ($_POST['tanggal_buka'] as $id => $tanggal_buka) {
            $tanggal_tutup = $_POST['tanggal_tutup'][$id];
            $stmt->execute([$tanggal_buka, $tanggal_tutup, $id]);
        }
        
        $_SESSION['success'] = "Periode pendaftaran berhasil diperbarui!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal memperbarui periode pendaftaran: " . $e->getMessage();
    }
}

header("Location: ../pengaturan.php");
exit;