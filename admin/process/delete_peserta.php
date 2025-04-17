<?php
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../includes/admin_auth.php';

if(empty($_SESSION['admin'])) {
    $_SESSION['error'] = "Silakan login sebagai admin terlebih dahulu!";
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['peserta_id'])) {
    $peserta_id = $_POST['peserta_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Hapus data verifikasi peserta
        $stmt = $pdo->prepare("DELETE FROM verifikasi_peserta WHERE peserta_id = ?");
        $stmt->execute([$peserta_id]);
        
        // Hapus data peserta
        $stmt = $pdo->prepare("DELETE FROM peserta WHERE id = ?");
        $stmt->execute([$peserta_id]);
        
        $pdo->commit();
        $_SESSION['success'] = "Data peserta berhasil dihapus!";
    } catch(PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Gagal menghapus data peserta: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid request!";
}

header("Location: ../peserta.php");
exit;
