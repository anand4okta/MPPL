<?php
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

if (ob_get_level()) ob_end_clean();

if (!isset($_SESSION['admin']) || !isset($_GET['type']) || !isset($_GET['id'])) {
    http_response_code(403);
    exit("Access denied");
}

try {
    // Get peserta data
    $stmt = $pdo->prepare("SELECT * FROM peserta WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $peserta = $stmt->fetch();

    if (!$peserta) {
        http_response_code(404);
        exit("Data not found");
    }

    // Set file info based on type
    switch ($_GET['type']) {
        case 'photo':
            $filename = $peserta['file_photo'];
            $mime = ['image/jpeg', 'image/png'];
            break;
        case 'akte':
            $filename = $peserta['file_akte'];
            $mime = ['application/pdf'];
            break;
        case 'kk':
            $filename = $peserta['file_kk'];
            $mime = ['application/pdf'];
            break;
        case 'raport':
            $sem = isset($_GET['sem']) ? $_GET['sem'] : '1';
            $field = 'file_raport_' . $sem;
            $filename = $peserta[$field];
            $mime = ['application/pdf'];
            break;
        default:
            http_response_code(400);
            exit("Invalid file type");
    }

    if (!$filename) {
        http_response_code(404);
        exit("File not found in database");
    }

    // Get student folder name
    $folder = str_replace(' ', '_', strtolower($peserta['nama_lengkap']));
    
    // Set file path (admin can access files from all locations)
    $possible_paths = [
        __DIR__ . "/../File/{$folder}/{$filename}",
        __DIR__ . "/../uploads/{$filename}",
        "../File/{$folder}/{$filename}",
        "../uploads/{$filename}"
    ];

    $file_path = null;
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            $file_path = $path;
            break;
        }
    }

    if (!$file_path || !is_readable($file_path)) {
        http_response_code(404);
        exit("File not found");
    }

    // Send file headers
    header('Content-Type: ' . mime_content_type($file_path));
    header('Content-Length: ' . filesize($file_path));
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');

    // Output file
    readfile($file_path);
    exit;

} catch (Exception $e) {
    error_log("Admin download error: " . $e->getMessage());
    http_response_code(500);
    exit("Error processing request");
}
