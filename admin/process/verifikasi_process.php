<?php
session_start();
require_once __DIR__.'/../../config/db.php';
require_once '../../includes/admin_auth.php';

if(empty($_SESSION['admin']) || empty($_POST['id'])) {
    header("Location: ../verifikasi.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Get student data first to determine folder name
        $stmt = $pdo->prepare("SELECT nama_lengkap FROM peserta WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $student = $stmt->fetch();
        
        // Create student folder name 
        $folder_name = str_replace(' ', '_', strtolower($student['nama_lengkap']));
        
        // Update file paths in database to include student folder
        $stmt = $pdo->prepare("UPDATE peserta SET 
            file_kk = CONCAT(?, '/KK_', ?), 
            file_akte = CONCAT(?, '/AKTE_', ?),
            file_photo = CONCAT(?, '/Photo_', ?)
            WHERE id = ?");
        
        $stmt->execute([
            $folder_name, $student['nama_lengkap'],
            $folder_name, $student['nama_lengkap'], 
            $folder_name, $student['nama_lengkap'],
            $_POST['id']
        ]);

        // Rest of verification process
        $peserta_id = $_POST['id'];
        $status_verifikasi = $_POST['status_verifikasi'];
        $jarak = $_POST['jarak'];
        $catatan = $_POST['catatan'];
        $status_penerimaan = isset($_POST['status_penerimaan']) ? $_POST['status_penerimaan'] : null;

        // Using REPLACE INTO to avoid duplicates
        $stmt = $pdo->prepare("
            REPLACE INTO verifikasi_peserta 
            (peserta_id, status_verifikasi, jarak_ke_sekolah, catatan, created_at, updated_at)
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$peserta_id, $status_verifikasi, $jarak, $catatan]);

        // Handle pengumuman table based on verification status
        if ($status_verifikasi === 'Verified' && $status_penerimaan) {
            $stmt = $pdo->prepare("
                REPLACE INTO pengumuman 
                (peserta_id, status_penerimaan, created_at, updated_at)
                VALUES (?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$peserta_id, $status_penerimaan]);
        } elseif ($status_verifikasi === 'Rejected') {
            // If rejected, update status to rejected but keep the data
            $stmt = $pdo->prepare("UPDATE verifikasi_peserta SET status_verifikasi = 'rejected' WHERE peserta_id = ?");
            $stmt->execute([$peserta_id]);
            
            // Remove from pengumuman if exists
            $stmtDelete = $pdo->prepare("DELETE FROM pengumuman WHERE peserta_id = ?");
            $stmtDelete->execute([$peserta_id]);
        }

        $pdo->commit();
        $_SESSION['success'] = "Verifikasi berhasil disimpan";

    } catch(PDOException $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        $_SESSION['error'] = "Terjadi kesalahan sistem";
    }
    
    header("Location: ../verifikasi.php");
    exit();
}
?>
