<?php
session_start();
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/functions.php';

header('Content-Type: application/json');

if (empty($_SESSION['user'])) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

try {
    $response = ['success' => true, 'files' => []];
    $peserta_id = $_SESSION['user']['id'];
    $nama_lengkap = str_replace(' ', '_', $_POST['nama_lengkap']);
    $upload_dir = dirname(__DIR__) . '/uploads/';
    
    error_log("Upload directory: " . $upload_dir);
    error_log("User ID: " . $peserta_id);

    // Create upload directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        error_log("Created upload directory: " . $upload_dir);
    }

    // Get current files to delete them when replaced
    $stmt = $pdo->prepare("SELECT file_kk, file_akte, file_photo FROM peserta WHERE id = ?");
    $stmt->execute([$peserta_id]);
    $current_files = $stmt->fetch(PDO::FETCH_ASSOC);

    // Function to handle file upload with correct naming convention
    function handleFileUpload($file, $type, $nama_lengkap, $upload_dir) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Sanitize and format the name
        $safe_nama = preg_replace('/[^A-Za-z0-9]/', '_', $nama_lengkap);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Generate filename based on type
        switch ($type) {
            case 'file_kk':
                $new_filename = 'KK_' . $safe_nama . '.' . $ext;
                break;
            case 'file_akte':
                $new_filename = 'AKTE_' . $safe_nama . '.' . $ext;
                break;
            case 'file_photo':
                $new_filename = 'Photo_' . $safe_nama . '.' . $ext;
                error_log("Processing photo upload: " . $new_filename);
                break;
            default:
                return null;
        }

        // Move uploaded file to destination
        if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
            error_log("File uploaded successfully: " . $upload_dir . $new_filename);
            return $new_filename;
        }
        error_log("Failed to upload file: " . $upload_dir . $new_filename);
        return null;
    }

    // Process each file type
    $file_types = ['file_kk', 'file_akte', 'file_photo'];
    foreach ($file_types as $type) {
        if (isset($_FILES[$type]) && $_FILES[$type]['size'] > 0) {
            $new_filename = handleFileUpload(
                $_FILES[$type],
                $type,
                $nama_lengkap,
                $upload_dir
            );

            if ($new_filename) {
                // Update database with new filename
                $stmt = $pdo->prepare("UPDATE peserta SET $type = ? WHERE id = ?");
                $stmt->execute([$new_filename, $peserta_id]);
                $response['files'][$type] = $new_filename;
            }
        }
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
