<?php
require_once '../../config/db.php';
require_once '../../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Update jarak ke sekolah
        $stmt = $pdo->prepare("UPDATE peserta SET jarak_ke_sekolah = ? WHERE id = ?");
        $stmt->execute([$_POST['jarak'], $_POST['id']]);

        // Cek dan update/insert verifikasi
        $stmt = $pdo->prepare("SELECT id FROM verifikasi_peserta WHERE peserta_id = ?");
        $stmt->execute([$_POST['id']]);

        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE verifikasi_peserta SET status_verifikasi = ?, catatan = ? WHERE peserta_id = ?");
            $stmt->execute([$_POST['status_verifikasi'], $_POST['catatan'], $_POST['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO verifikasi_peserta (peserta_id, status_verifikasi, catatan) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['id'], $_POST['status_verifikasi'], $_POST['catatan']]);
        }

        // Handle pengumuman jika status Verified
        if ($_POST['status_verifikasi'] === 'Verified' && !empty($_POST['status_penerimaan'])) {
            $stmt = $pdo->prepare("REPLACE INTO pengumuman (peserta_id, status_penerimaan, catatan) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['id'], strtolower($_POST['status_penerimaan']), $_POST['catatan']]);
        }
        
        $pdo->commit();
        
        // Redirect dengan parameter success
        header('Location: ../verifikasi.php?success=1');
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Verifikasi Error: " . $e->getMessage());
        header('Location: ../verifikasi.php?error=1');
        exit;
    }
}

header('Location: ../verifikasi.php');
exit;
