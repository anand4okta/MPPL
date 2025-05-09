<?php
session_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../api/whatsapp_api.php';

// Blok akses langsung ke file
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode([
        'status' => 'error',
        'message' => 'Akses tidak sah!'
    ]));
}

try {
    // ============================================
    // VALIDASI KEAMANAN
    // ============================================
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        throw new Exception("Token keamanan tidak valid! Refresh halaman dan coba lagi.");
    }

    if (!isset($_POST['captcha']) || !validateCaptcha($_POST['captcha'], 'register')) {
        throw new Exception("CAPTCHA tidak valid atau kadaluarsa! Refresh CAPTCHA dan coba lagi.");
    }

    // ============================================
    // PROSES INPUT DATA
    // ============================================
    $nisn = preg_replace('/[^0-9]/', '', $_POST['nisn']);
    $nama = clean_input($_POST['nama_lengkap']);
    $phone = preg_replace('/^0/', '62', clean_input($_POST['no_wa_siswa'])); // Format internasional
    $jalur_id = (int)clean_input($_POST['jalur_id']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // ============================================
    // VALIDASI DATA
    // ============================================
    $errors = [];
    
    // Validasi panjang NISN
    if (strlen($nisn) !== 10) {
        $errors[] = "NISN harus 10 digit angka!";
    }

    // Validasi format WhatsApp
    if (!preg_match('/^62\d{9,13}$/', $phone)) {
        $errors[] = "Format WhatsApp tidak valid! Gunakan format 08xx-xxxx-xxxx";
    }

    // Validasi password
    if ($password !== $confirm_password) {
        $errors[] = "Konfirmasi password tidak cocok!";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password minimal 8 karakter!";
    }

    // Cek jalur valid dengan logic yang benar
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("
        SELECT j.*,
            (SELECT COUNT(*) FROM peserta WHERE jalur_id = j.id) as total_pendaftar
        FROM jalur_pendaftaran j 
        WHERE j.id = ?
        AND ? BETWEEN j.tanggal_buka AND j.tanggal_tutup
    ");
    $stmt->execute([$jalur_id, $now]);
    $jalur = $stmt->fetch();

    if (!$jalur) {
        throw new Exception("Jalur pendaftaran tidak tersedia atau sudah ditutup! Silakan pilih jalur lain.");
    }

    // Cek kuota
    if ($jalur['total_pendaftar'] >= $jalur['kuota']) {
        throw new Exception("Mohon maaf, kuota {$jalur['nama_jalur']} sudah penuh ({$jalur['total_pendaftar']}/{$jalur['kuota']})");
    }

    if (!empty($errors)) {
        throw new Exception(implode("<br>", $errors));
    }

    // ============================================
    // PROSES DATABASE
    // ============================================
    $pdo->beginTransaction();

    try {
        // Cek duplikasi NISN dan nama (case insensitive)
        $stmt = $pdo->prepare("
            SELECT p.id, p.nama_lengkap, p.nisn, j.nama_jalur 
            FROM peserta p 
            LEFT JOIN jalur_pendaftaran j ON p.jalur_id = j.id 
            WHERE p.nisn = ? OR LOWER(p.nama_lengkap) = LOWER(?)
        ");
        $stmt->execute([$nisn, $nama]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $error_msg = "Maaf, ";
            if (strtolower($existing['nama_lengkap']) === strtolower($nama)) {
                $error_msg .= "nama {$existing['nama_lengkap']} ";
            }
            if ($existing['nisn'] === $nisn) {
                $error_msg .= ($error_msg !== "Maaf, " ? "dengan " : "") . "NISN {$existing['nisn']} ";
            }
            $error_msg .= "sudah terdaftar di jalur {$existing['nama_jalur']}";
            throw new Exception($error_msg);
        }

        // Hash password dengan cost yang sesuai
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Simpan data
        $stmt = $pdo->prepare("INSERT INTO peserta (nama_lengkap, nisn, jalur_id, password, no_wa_siswa) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $nisn, $jalur_id, $hashed_password, $phone]);
        $user_id = $pdo->lastInsertId();
        
        $pdo->commit();

        // ============================================
        // KIRIM NOTIFIKASI WHATSAPP
        // ============================================
        try {
            $wa_result = sendRegistrationSuccess(
                $nisn,
                $password,
                $phone
            );
            
            if (!$wa_result) {
                error_log("Gagal mengirim WhatsApp ke $phone");
            }
        } catch (Exception $e) {
            error_log("WhatsApp Error: " . $e->getMessage());
        }

        $_SESSION['registration_success'] = "Pendaftaran berhasil! Silakan cek WhatsApp Anda untuk informasi selanjutnya.";
        header("Location: ../index.php");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Database Error: " . $e->getMessage());
        throw new Exception("Gagal menyimpan data. Silakan coba beberapa saat lagi.");
    }

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../index.php?show=daftar");
    exit;
}
?>
