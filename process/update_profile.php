<?php
session_start();
require_once __DIR__.'/../includes/config.php';

header('Content-Type: application/json');
ob_start();

if(empty($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    if($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $fields = [
        'nik', 'no_kk', 'tempat_lahir', 'tanggal_lahir', 'agama_siswa', 'no_wa_siswa', 
        'asal_sekolah', 'tahun_lulus', 'jalur_id', 'jenis_kelamin',
        'alamat_siswa_jalan', 'alamat_siswa_rt', 'alamat_siswa_rw',
        'alamat_siswa_kelurahan', 'alamat_siswa_kecamatan', 'alamat_siswa_kota',
        'nama_ayah', 'nama_ibu', 'pekerjaan_ayah', 'pekerjaan_ibu',
        'agama_ayah', 'agama_ibu', 'no_telp_ortu',
        'alamat_ortu_jalan', 'alamat_ortu_rt', 'alamat_ortu_rw',
        'alamat_ortu_kelurahan', 'alamat_ortu_kecamatan', 'alamat_ortu_kota',
        'program_keahlian', 'tempat_lahir_ayah', 'tempat_lahir_ibu', 
        'tanggal_lahir_ayah', 'tanggal_lahir_ibu'
    ];

    $updates = [];
    $params = [];

    foreach($fields as $field) {
        if(isset($_POST[$field]) && $_POST[$field] !== '') {
            $updates[] = "$field = ?";
            $params[] = $_POST[$field];
        }
    }

    if(!empty($updates)) {
        $params[] = $_SESSION['user']['id'];
        $sql = "UPDATE peserta SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        if(!$stmt->execute($params)) {
            throw new Exception("Gagal memperbarui data");
        }
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Data berhasil diperbarui'
    ]);

} catch(Exception $e) {
    ob_clean();
    error_log("Update Profile Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}