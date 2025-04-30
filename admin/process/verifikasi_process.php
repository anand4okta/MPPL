<?php
require_once '../../config/db.php';
require_once '../../includes/admin_auth.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Method not allowed');
}

try {
    // Get peserta data first to check files
    $stmt = $pdo->prepare("SELECT * FROM peserta WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $peserta = $stmt->fetch();

    if(!$peserta) {
        throw new Exception("Data peserta tidak ditemukan");
    }

    // Check missing files
    $missing_files = [];
    $folder_name = str_replace(' ', '_', strtolower($peserta['nama_lengkap']));
    
    // Check required files
    $required_files = [
        'file_kk' => 'Kartu Keluarga',
        'file_akte' => 'Akte Kelahiran',
        'file_photo' => 'Pas Foto'
    ];

    foreach($required_files as $field => $label) {
        if(empty($peserta[$field]) || !file_exists("../../File/{$folder_name}/{$peserta[$field]}")) {
            $missing_files[] = $label;
        }
    }

    // Check Raport
    for($i = 1; $i <= 5; $i++) {
        $field_name = "file_raport_" . $i;
        if(empty($peserta[$field_name]) || !file_exists("../../File/{$folder_name}/{$peserta[$field_name]}")) {
            $missing_files[] = "Raport Semester " . $i;
        }
    }

    // Generate automatic note if files are missing
    $auto_note = !empty($missing_files) ? "Silakan lengkapi berkas berikut:\n- " . implode("\n- ", $missing_files) : "";
    
    // Use admin's manual note if provided, otherwise use auto note
    $final_note = !empty($_POST['catatan']) ? $_POST['catatan'] : $auto_note;

    // Check if verification record exists
    $stmt = $pdo->prepare("SELECT id FROM verifikasi_peserta WHERE peserta_id = ?");
    $stmt->execute([$_POST['id']]);
    $exists = $stmt->fetch();

    if($exists) {
        // Update existing record
        $stmt = $pdo->prepare("UPDATE verifikasi_peserta SET 
            status_verifikasi = ?,
            jarak_ke_sekolah = ?,
            catatan = ?,
            admin_id = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE peserta_id = ?");
        
        $stmt->execute([
            $_POST['status_verifikasi'],
            $_POST['jarak'],
            $final_note,
            $_SESSION['admin']['id'],
            $_POST['id']
        ]);
    } else {
        // Insert new record
        $stmt = $pdo->prepare("INSERT INTO verifikasi_peserta 
            (peserta_id, admin_id, status_verifikasi, jarak_ke_sekolah, catatan)
            VALUES (?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $_POST['id'],
            $_SESSION['admin']['id'],
            $_POST['status_verifikasi'],
            $_POST['jarak'],
            $final_note
        ]);
    }

    // Update jarak in peserta table
    $stmt = $pdo->prepare("UPDATE peserta SET jarak_ke_sekolah = ? WHERE id = ?");
    $stmt->execute([$_POST['jarak'], $_POST['id']]);

    // Handle pengumuman if status is Verified
    if($_POST['status_verifikasi'] === 'Verified' && !empty($_POST['status_penerimaan'])) {
        $stmt = $pdo->prepare("INSERT INTO pengumuman 
            (peserta_id, status_penerimaan, admin_id)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            status_penerimaan = ?, admin_id = ?");
        
        $stmt->execute([
            $_POST['id'],
            $_POST['status_penerimaan'],
            $_SESSION['admin']['id'],
            $_POST['status_penerimaan'],
            $_SESSION['admin']['id']
        ]);
    }

    // Change this line to use a specific session key
    $_SESSION['verifikasi_success'] = "Status verifikasi berhasil diperbarui";
    header("Location: ../verifikasi.php");
    exit;

} catch(Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    header("Location: ../verifikasi.php");
    exit;
}
