<?php
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("UPDATE jalur_pendaftaran SET kuota = ? WHERE id = ?");
        
        foreach ($_POST['kuota'] as $id => $kuota) {
            $stmt->execute([$kuota, $id]);
        }
        
        $_SESSION['success'] = "Kuota jalur pendaftaran berhasil diperbarui!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal memperbarui kuota: " . $e->getMessage();
    }
}

header("Location: ../pengaturan.php");
exit;