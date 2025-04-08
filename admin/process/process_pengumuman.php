<?php
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../includes/admin_auth.php';

if(empty($_SESSION['admin'])) {
    $_SESSION['error'] = "Silakan login sebagai admin terlebih dahulu!";
    header("Location: ../login.php");
    exit;
}

if(!isset($_GET['id']) || !isset($_GET['status'])) {
    $_SESSION['error'] = "Parameter tidak lengkap!";
    header("Location: ../pengumuman.php");
    exit;
}

$peserta_id = $_GET['id'];
$status = $_GET['status'];

if(!in_array($status, ['diterima', 'ditolak'])) {
    $_SESSION['error'] = "Status tidak valid!";
    header("Location: ../pengumuman.php");
    exit;
}

try {
    // Check if record exists
    $check = $pdo->prepare("SELECT * FROM pengumuman WHERE peserta_id = ?");
    $check->execute([$peserta_id]);
    
    if($check->rowCount() > 0) {
        // Update existing record
        $stmt = $pdo->prepare("UPDATE pengumuman SET status_penerimaan = ?, updated_at = NOW() WHERE peserta_id = ?");
        $stmt->execute([$status, $peserta_id]);
    } else {
        // Insert new record
        $stmt = $pdo->prepare("INSERT INTO pengumuman (peserta_id, status_penerimaan, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
        $stmt->execute([$peserta_id, $status]);
    }
    
    $_SESSION['success'] = "Status penerimaan peserta berhasil diperbarui!";
} catch(PDOException $e) {
    $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
}

header("Location: ../pengumuman.php");
exit;