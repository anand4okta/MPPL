<?php
// Set session configuration
ini_set('session.cookie_lifetime', 0);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_samesite', 'Lax');

session_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/functions.php';

header('Content-Type: application/json');

// Debug log untuk melihat data yang diterima
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada sesi aktif']);
    exit;
}

try {
    $user_id = $_SESSION['user']['id'];
    if (!$user_id) {
        throw new Exception('ID pengguna tidak valid');
    }

    // Create base File directory
    $base_upload_dir = __DIR__ . '/../File/';
    if (!file_exists($base_upload_dir)) {
        if (!mkdir($base_upload_dir, 0777, true)) {
            error_log("Failed to create base upload directory: " . $base_upload_dir);
            throw new Exception('Gagal membuat direktori upload');
        }
        chmod($base_upload_dir, 0777);
    }

    // Get current user data
    $stmt = $pdo->prepare("SELECT * FROM peserta WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_data) {
        throw new Exception('Data peserta tidak ditemukan');
    }

    // Prepare student directory with proper permissions
    $student_folder = sanitize_filename($current_data['nama_lengkap']);
    $upload_dir = $base_upload_dir . $student_folder . '/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        chmod($upload_dir, 0777);
    }
    error_log("Student directory ready: " . $upload_dir);

    // Prepare form data
    $form_data = [];
    $fields = [
        'nik', 'no_kk', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama_siswa',
        'asal_sekolah', 'tahun_lulus', 'no_wa_siswa', 'program_keahlian',
        'alamat_siswa_jalan', 'alamat_siswa_rt', 'alamat_siswa_rw',
        'alamat_siswa_kelurahan', 'alamat_siswa_kecamatan', 'alamat_siswa_kota',
        'nama_ayah', 'nama_ibu', 'tempat_lahir_ayah', 'tempat_lahir_ibu',
        'tanggal_lahir_ayah', 'tanggal_lahir_ibu', 'pekerjaan_ayah', 'pekerjaan_ibu',
        'agama_ayah', 'agama_ibu', 'no_telp_ortu',
        'alamat_ortu_jalan', 'alamat_ortu_rt', 'alamat_ortu_rw',
        'alamat_ortu_kelurahan', 'alamat_ortu_kecamatan', 'alamat_ortu_kota'
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $form_data[$field] = clean_input($_POST[$field]);
        }
    }

    // Handle file uploads
    $files_to_update = [];
    $file_fields = ['file_kk', 'file_akte', 'file_photo', 'file_ijazah_skl'];
    // Add raport files
    for ($i = 1; $i <= 5; $i++) {
        $file_fields[] = "file_raport_$i";
    }

    foreach ($file_fields as $file_field) {
        if (isset($_FILES[$file_field]) && $_FILES[$file_field]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$file_field];
            
            // Generate filename
            $new_filename = getFormattedFileName($file['name'], $file_field, $current_data['nama_lengkap']);
            
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                chmod($upload_dir . $new_filename, 0644);
                $files_to_update[$file_field] = $new_filename; // Store just filename
            }
        }
    }

    // Update database
    $pdo->beginTransaction();

    // Update form data
    if (!empty($form_data) || !empty($files_to_update)) {
        $update_fields = [];
        $update_values = [];
        
        foreach ($form_data as $field => $value) {
            $update_fields[] = "$field = ?";
            $update_values[] = $value;
        }

        foreach ($files_to_update as $field => $value) {
            $update_fields[] = "$field = ?";
            $update_values[] = $value;
        }

        if (!empty($update_fields)) {
            $update_values[] = $user_id;
            $sql = "UPDATE peserta SET " . implode(', ', $update_fields) . ", updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($update_values);
        }

        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Data berhasil disimpan',
            'files' => $files_to_update
        ]);
        exit;
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Tidak ada perubahan data'
        ]);
        exit;
    }

} catch (Exception $e) {
    if (isset($pdo)) $pdo->rollBack();
    error_log("Upload Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
    exit;
}

// Add this helper function at the end of the file
function sanitize_filename($filename) {
    // Remove any character that's not alphanumeric, underscore, dash or space
    $filename = preg_replace('/[^\w\-\s]/', '', $filename);
    // Replace spaces with underscores
    $filename = str_replace(' ', '_', $filename);
    // Remove multiple consecutive underscores
    $filename = preg_replace('/_+/', '_', $filename);
    // Convert to lowercase
    return strtolower($filename);
}