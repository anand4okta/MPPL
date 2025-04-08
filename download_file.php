<?php
session_start();
require_once 'config/db.php';

// Disable output buffering
if (ob_get_level()) ob_end_clean();

if (!isset($_SESSION['user']) || !isset($_GET['type']) || !isset($_GET['id'])) {
    http_response_code(403);
    exit("Access denied");
}

try {
    // Get peserta data
    $stmt = $pdo->prepare("SELECT * FROM peserta WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $peserta = $stmt->fetch();

    if (!$peserta || $peserta['id'] != $_SESSION['user']['id']) {
        http_response_code(403);
        exit("Access denied");
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
    
    // Check file in different possible locations
    $possible_paths = [
        __DIR__ . "/File/{$folder}/{$filename}",
        __DIR__ . "/uploads/{$filename}",
        "File/{$folder}/{$filename}",
        "uploads/{$filename}"
    ];

    $file_path = null;
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            $file_path = $path;
            break;
        }
    }

    if (!$file_path || !is_readable($file_path)) {
        error_log("File not found in paths: " . implode(", ", $possible_paths));
        http_response_code(404);
        exit("File not found");
    }

    // Get file mime type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $file_mime = finfo_file($finfo, $file_path);
    finfo_close($finfo);

    // Verify mime type
    if (!in_array($file_mime, $mime)) {
        http_response_code(400);
        exit("Invalid file type");
    }

    // Get file size
    $size = filesize($file_path);

    // Set headers for download
    header('Content-Type: ' . $file_mime);
    header('Content-Length: ' . $size);
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    header('Pragma: public');

    // Read file and output in chunks
    $handle = fopen($file_path, 'rb');
    while (!feof($handle) && (connection_status() === CONNECTION_NORMAL)) {
        echo fread($handle, 8192);
        flush();
    }
    fclose($handle);
    exit;

} catch (Exception $e) {
    error_log("Download error: " . $e->getMessage());
    http_response_code(500);
    exit("Error processing request");
}
