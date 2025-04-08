<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if(empty($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if(!isset($_POST['document_type']) || !in_array($_POST['document_type'], ['file_kk', 'file_akte'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid document type']);
    exit;
}

try {
    // Get current file name
    $stmt = $pdo->prepare("SELECT " . $_POST['document_type'] . ", nama_lengkap FROM peserta WHERE id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $result = $stmt->fetch();
    $current_file = $result[$_POST['document_type']];

    if($current_file) {
        // Delete file from server using full path from database
        $file_path = "../File/" . $current_file;
        if(file_exists($file_path)) {
            unlink($file_path);
        }

        // Update database
        $stmt = $pdo->prepare("UPDATE peserta SET " . $_POST['document_type'] . " = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id']]);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'File not found']);
    }
} catch(Exception $e) {
    error_log("Error deleting document: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>