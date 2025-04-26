<?php
require_once '../../config/db.php';
require_once '../../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Update jarak ke sekolah
        $stmt = $pdo->prepare("UPDATE peserta SET jarak_ke_sekolah = ? WHERE id = ?");
        $stmt->execute([$_POST['jarak'], $_POST['id']]);

        // Cek verifikasi yang sudah ada
        $stmt = $pdo->prepare("SELECT id FROM verifikasi_peserta WHERE peserta_id = ?");
        $stmt->execute([$_POST['id']]);

        // Proses verifikasi
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE verifikasi_peserta SET status_verifikasi = ?, catatan = ?, updated_at = NOW() WHERE peserta_id = ?");
            $stmt->execute([$_POST['status_verifikasi'], $_POST['catatan'], $_POST['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO verifikasi_peserta (peserta_id, status_verifikasi, catatan, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
            $stmt->execute([$_POST['id'], $_POST['status_verifikasi'], $_POST['catatan']]);
        }

        // Jika status Verified dan ada status_penerimaan, update tabel pengumuman
        if ($_POST['status_verifikasi'] === 'Verified' && !empty($_POST['status_penerimaan'])) {
            // Cek pengumuman yang sudah ada
            $stmt = $pdo->prepare("SELECT id FROM pengumuman WHERE peserta_id = ?");
            $stmt->execute([$_POST['id']]);
            
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE pengumuman SET status_penerimaan = ?, catatan = ?, updated_at = NOW() WHERE peserta_id = ?");
                $stmt->execute([strtolower($_POST['status_penerimaan']), $_POST['catatan'], $_POST['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO pengumuman (peserta_id, status_penerimaan, catatan, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                $stmt->execute([$_POST['id'], strtolower($_POST['status_penerimaan']), $_POST['catatan']]);
            }
        }

        $pdo->commit();
        $_SESSION['success'] = "Status verifikasi berhasil diperbarui";
        
        // Tambahkan delay kecil sebelum redirect
        usleep(100000); // 0.1 detik delay
        
        header("Location: ../verifikasi.php");
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Verifikasi Error: " . $e->getMessage());
        $_SESSION['error'] = "Terjadi kesalahan saat memproses verifikasi";
        
        // Tambahkan delay kecil sebelum redirect
        usleep(100000); // 0.1 detik delay
        
        header("Location: ../verifikasi.php");
        exit;
    }
}

header("Location: ../verifikasi.php");
exit;
