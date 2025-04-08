<?php
require_once __DIR__.'/config/db.php';
require_once 'includes/functions.php';

if(empty($_SESSION['user'])) {
    $_SESSION['error'] = "Silakan login terlebih dahulu!";
    header("Location: index.php");
    exit;
}

try {
    // Get peserta and announcement date data
    $stmt = $pdo->prepare("
        SELECT p.*, v.status_verifikasi, pg.status_penerimaan, j.tanggal_pengumuman 
        FROM peserta p 
        LEFT JOIN verifikasi_peserta v ON p.id = v.peserta_id
        LEFT JOIN pengumuman pg ON p.id = pg.peserta_id 
        LEFT JOIN jalur_pendaftaran j ON p.jalur_id = j.id
        WHERE p.id = ?
    ");
    $stmt->execute([$_SESSION['user']['id']]);
    $peserta = $stmt->fetch();

    if (!$peserta) {
        throw new Exception("Data peserta tidak ditemukan");
    }

    $tanggal_pengumuman = $peserta['tanggal_pengumuman'];
    
    if (!$tanggal_pengumuman) {
        throw new Exception("Tanggal pengumuman belum diatur untuk jalur pendaftaran Anda");
    }

    $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    $current_year = (int)$now->format('Y');
    $current_month = (int)$now->format('m');
    $academic_year_start = ($current_month > 6) ? $current_year + 1 : $current_year;
    $academic_year_end = $academic_year_start + 1;
    $announcement_time = new DateTime($tanggal_pengumuman, new DateTimeZone('Asia/Jakarta'));
    
    // Debug information
    error_log("Current time: " . $now->format('Y-m-d H:i:s'));
    error_log("Announcement time: " . $announcement_time->format('Y-m-d H:i:s'));
    
    $showAnnouncement = $now->format('Y-m-d') >= $announcement_time->format('Y-m-d');
    error_log("Show announcement: " . ($showAnnouncement ? 'true' : 'false'));

} catch(PDOException $e) {
    error_log("Database error in pengumuman.php: " . $e->getMessage());
    $error_message = "Terjadi kesalahan pada sistem database";
} catch(Exception $e) {
    error_log("Error in pengumuman.php: " . $e->getMessage());
    $error_message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengumuman PPDB</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            padding-top: 75px;
        }
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1030;
        }
    </style>
</head>
<body class="pt-5">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-white navbar-light border-bottom fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center text-primary" href="index.php">
                <img src="https://freeimghost.net/images/2025/04/04/logo_kemenag.png" alt="Logo" height="40" class="me-2">
                <div class="d-flex flex-column">
                    <span class="fw-bold">PPDB MAN 1 MUSI RAWAS</span>
                    <small class="text-muted">Tahun Ajaran <?= $academic_year_start . '/' . $academic_year_end ?></small>
                </div>
            </a>
            <button class="navbar-toggler border-primary" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="d-flex gap-2 ms-auto">
                    <a href="dashboard.php" class="btn btn-success">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="logout.php" class="btn btn-danger">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white py-3">
                        <h4 class="mb-0 text-center">Pengumuman Hasil PPDB</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if(isset($error_message)): ?>
                            <div class="alert alert-danger text-center">
                                <h4><i class="bi bi-exclamation-triangle"></i> <?= $error_message ?></h4>
                            </div>
                        <?php elseif(!$showAnnouncement): ?>
                            <div class="alert alert-warning text-center py-4">
                                <h4><i class="bi bi-exclamation-triangle"></i> Maaf, belum saatnya pengumuman!</h4>
                                <p class="mb-1">Pengumuman akan dibuka pada tanggal:</p>
                                <h5><?= date('d F Y', strtotime($tanggal_pengumuman)) ?></h5>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive mb-4">
                                <table class="table table-bordered mb-0">
                                    <tr>
                                        <th class="bg-light" width="25%">NISN</th>
                                        <td><?= htmlspecialchars($peserta['nisn']) ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Nama Lengkap</th>
                                        <td><?= htmlspecialchars($peserta['nama_lengkap']) ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Tanggal Lahir</th>
                                        <td><?= !empty($peserta['tanggal_lahir']) ? date('d F Y', strtotime($peserta['tanggal_lahir'])) : '' ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Asal Sekolah</th>
                                        <td><?= htmlspecialchars($peserta['asal_sekolah']) ?></td>
                                    </tr>
                                </table>
                            </div>

                            <?php if($peserta['status_penerimaan'] === 'diterima'): ?>
                                <div class="alert alert-success text-center py-4">
                                    <div class="mb-4">
                                        <i class="bi bi-stars" style="font-size: 3rem;"></i>
                                    </div>
                                    <h4 class="alert-heading mb-3">
                                        <i class="bi bi-check-circle"></i> SELAMAT!
                                    </h4>
                                    <div class="mb-4">
                                        <h5 class="mb-2">Anda dinyatakan <strong>DITERIMA</strong></h5>
                                        <h5 class="mb-2">sebagai Calon Peserta Didik Baru</h5>
                                        <h5 class="mb-2">MAN 1 MUSI RAWAS</h5>
                                        <h6>Tahun Pelajaran 2024/2025</h6>
                                    </div>
                                    
                                    <hr class="my-3">
                                    
                                    <div class="mt-4">
                                        <h5 class="fw-bold mb-3">Langkah Selanjutnya:</h5>
                                        <ol class="text-start px-4 mb-4">
                                            <li class="mb-2">Lakukan daftar ulang pada tanggal 1-7 Juli 2024</li>
                                            <li class="mb-3">Siapkan dokumen berikut untuk daftar ulang:
                                                <ul class="mt-2">
                                                    <li>Surat Keterangan Lulus Asli</li>
                                                    <li>Fotokopi Kartu Keluarga (2 lembar)</li>
                                                    <li>Fotokopi Akte Kelahiran (2 lembar)</li>
                                                    <li>Pas Foto 3x4 (4 lembar)</li>
                                                </ul>
                                            </li>
                                            <li class="mb-2">Hadir dalam pertemuan orang tua pada tanggal 8 Juli 2024</li>
                                            <li>Ikuti kegiatan MATSAMA (Masa Ta'aruf Siswa Madrasah) pada tanggal 15-17 Juli 2024</li>
                                        </ol>
                                        
                                        <div class="alert alert-warning py-3 mb-0">
                                            <i class="bi bi-exclamation-circle"></i>
                                            <strong>Penting:</strong> Jika tidak melakukan daftar ulang sesuai jadwal yang ditentukan, maka kelulusan dianggap gugur.
                                        </div>
                                    </div>
                                </div>
                            <?php elseif($peserta['status_penerimaan'] === 'ditolak'): ?>
                                <div class="alert alert-danger text-center py-4">
                                    <div class="mb-4">
                                        <i class="bi bi-envelope-paper-heart" style="font-size: 3rem;"></i>
                                    </div>
                                    <h4 class="mb-3"><i class="bi bi-x-circle"></i> Mohon Maaf</h4>
                                    <p class="mb-3">Anda dinyatakan <strong>TIDAK DITERIMA</strong> di MAN 1 Musi Rawas.</p>
                                    <hr class="my-3">
                                    <div class="mt-3">
                                        <p class="mb-2">Jangan berkecil hati! Masih banyak kesempatan untuk meraih mimpi Anda di sekolah lain.</p>
                                        <p class="mb-0">Tetap semangat dan terus berjuang!</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info text-center mb-0">
                                    <h4 class="mb-2"><i class="bi bi-info-circle"></i> Status Belum Tersedia</h4>
                                    <p class="mb-0">Status penerimaan Anda belum ditentukan. Silakan cek kembali nanti.</p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center py-3">
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>