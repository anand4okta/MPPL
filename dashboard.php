<?php
// Start session at the very beginning
session_start();

// Add this after session_start
$now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$current_year = (int)$now->format('Y');
$current_month = (int)$now->format('m');
$academic_year_start = ($current_month > 6) ? $current_year + 1 : $current_year;
$academic_year_end = $academic_year_start + 1;

// Debug session
error_log("Session data: " . print_r($_SESSION, true));

require_once __DIR__.'/config/db.php';
require_once __DIR__.'/includes/functions.php';

// Check if user is logged in
if(empty($_SESSION['user'])) {
    $_SESSION['error'] = "Silakan login terlebih dahulu!";
    header("Location: index.php");
    exit;
}

try {
    // Tambahkan v.catatan ke query
    $stmt = $pdo->prepare("SELECT p.*, j.nama_jalur, v.status_verifikasi, v.catatan
                          FROM peserta p 
                          LEFT JOIN jalur_pendaftaran j ON p.jalur_id = j.id
                          LEFT JOIN verifikasi_peserta v ON p.id = v.peserta_id 
                          WHERE p.id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $peserta = $stmt->fetch();

    // Get prestasi
    $stmt = $pdo->prepare("SELECT * FROM prestasi WHERE peserta_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user']['id']]);
    $prestasi = $stmt->fetchAll();

    // Get available jalur pendaftaran
    $stmt = $pdo->prepare("SELECT id, nama_jalur FROM jalur_pendaftaran");
    $stmt->execute();
    $jalur_list = $stmt->fetchAll();

    $csrf_token = generateCsrfToken();
} catch(PDOException $e) {
    error_log("Database Error: ".$e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan sistem";
    header("Location: index.php");
    exit;
}

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!validateCsrfToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Token keamanan tidak valid!";
        header("Location: dashboard.php");
        exit;
    }

    // Pastikan tidak ada output sebelum JSON
    ob_start();

    try {
        if(isset($_POST['update_profile'])) {
            // Update profile logic
            $fields = [
                'tempat_lahir', 'tanggal_lahir', 'agama_siswa', 'no_wa_siswa', 'asal_sekolah', 'nik',
                'alamat_siswa_jalan', 'alamat_siswa_rt', 'alamat_siswa_rw', 'tahun_lulus', 'jalur_id',
                'alamat_siswa_kelurahan', 'alamat_siswa_kecamatan', 'alamat_siswa_kota',
                'nama_ayah', 'nama_ibu', 'pekerjaan_ayah', 'pekerjaan_ibu',
                'agama_ayah', 'agama_ibu', 'no_telp_ortu', 'jenis_kelamin',
                'alamat_ortu_jalan', 'alamat_ortu_rt', 'alamat_ortu_rw',
                'alamat_ortu_kelurahan', 'alamat_ortu_kecamatan', 'alamat_ortu_kota',
                'program_keahlian', 'tempat_lahir_ayah', 'tempat_lahir_ibu', 'tanggal_lahir_ayah', 'tanggal_lahir_ibu'
            ];

            $updates = [];
            $params = [];
            foreach($fields as $field) {
                if(isset($_POST[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $_POST[$field];
                }
            }
            $params[] = $_SESSION['user']['id'];

            if(!empty($updates)) {
                $sql = "UPDATE peserta SET " . implode(', ', $updates) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }

            $_SESSION['success'] = "Data berhasil diperbarui!";
        }

        if(isset($_POST['add_prestasi'])) {
            $stmt = $pdo->prepare("INSERT INTO prestasi (peserta_id, bidang_prestasi, peringkat, tingkat) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user']['id'],
                $_POST['bidang_prestasi'],
                $_POST['peringkat'],
                $_POST['tingkat']
            ]);
            $_SESSION['success'] = "Prestasi berhasil ditambahkan!";
        }

        if(isset($_POST['delete_prestasi'])) {
            $stmt = $pdo->prepare("DELETE FROM prestasi WHERE id = ? AND peserta_id = ?");
            $stmt->execute([$_POST['prestasi_id'], $_SESSION['user']['id']]);
            $_SESSION['success'] = "Prestasi berhasil dihapus!";
        }

        ob_clean(); // Bersihkan output buffer sebelum mengirim JSON
        header("Location: dashboard.php");
        exit;

    } catch(PDOException $e) {
        ob_clean(); // Bersihkan output buffer sebelum mengirim JSON
        error_log("Database Error: ".$e->getMessage());
        $_SESSION['error'] = "Terjadi kesalahan sistem";
        header("Location: dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PPDB MAN 1 Musi Rawas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            padding-top: 100px;
        }
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1030;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-white shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center text-primary" href="index.php">
                <img src="https://freeimghost.net/images/2025/04/04/logo_kemenag.png" alt="Logo" height="40" class="me-2">
                <div class="d-flex flex-column">
                    <span class="fw-bold">PPDB MAN 1 MUSI RAWAS</span>
                    <small class="text-muted">Tahun Ajaran <?= $academic_year_start . '/' . $academic_year_end ?></small>
                </div>
            </a>
            <button class="navbar-toggler border-primary" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon text-primary"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                </ul>
                <div class="d-flex gap-2">
                    <a href="profile.php" class="btn btn-success">
                        <i class="bi bi-person"></i> Profile
                    </a>
                    <a href="logout.php" class="btn btn-danger">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content with preserved PHP logic -->
    <div class="container">
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-person-circle"></i> Data Diri</h4>
                    </div>
                    <div class="card-body">
                        <!-- Data Pendaftaran Awal -->
                        <div class="alert alert-info mb-4">
                            <h5 class="alert-heading">Data Pendaftaran</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Nama Lengkap:</strong> <?= htmlspecialchars($peserta['nama_lengkap']) ?></p>
                                    <p class="mb-1"><strong>NISN:</strong> <?= htmlspecialchars($peserta['nisn']) ?></p>
                                    <p class="mb-1"><strong>No. WhatsApp:</strong> <?= htmlspecialchars($peserta['no_wa_siswa']) ?></p>
                                    <p class="mb-1"><strong>Status:</strong> 
                                    <?php 
                                    $status = $peserta['status_verifikasi'] ?? 'Pending';
                                    if ($status === 'Verified') {
                                        echo "<span class='badge bg-success'>Terverifikasi</span>";
                                    } elseif ($status === 'rejected') {
                                        echo "<span class='badge bg-danger'>Ditolak</span>";
                                        echo "<br><small class='text-danger mt-1'>Silahkan periksa kembali kelengkapan data anda</small>";
                                    } else {
                                        echo "<span class='badge bg-warning text-dark'>Belum Diverifikasi</span>";
                                    }
                                    ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Asal Sekolah:</strong> <?= htmlspecialchars($peserta['asal_sekolah']) ?></p>
                                    <p class="mb-1"><strong>Tahun Lulus:</strong> <?= htmlspecialchars($peserta['tahun_lulus']) ?></p>
                                    <p class="mb-1"><strong>Jalur Pendaftaran:</strong> <?= htmlspecialchars($peserta['nama_jalur']) ?></p>
                                    <p class="mb-1"><strong>Tanggal Daftar:</strong> <?= date('d/m/Y', strtotime($peserta['created_at'])) ?></p>
                                </div>
                            </div>

                            <?php if(!empty($peserta['catatan'])): ?>
                            <div class="mt-3">
                                <div class="alert alert-warning mb-0">
                                    <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Catatan dari Admin:</h6>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($peserta['catatan'])) ?></p>
                                    <?php if($status === 'rejected'): ?>
                                    <hr>
                                    <small class="text-danger">
                                        <i class="bi bi-info-circle"></i> 
                                        Silakan perbaiki data sesuai catatan di atas dan lengkapi dokumen yang diminta
                                    </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if($status === 'Verified'): ?>
                        <div class="alert alert-success mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="alert-heading mb-1">Pengumuman Kelulusan</h5>
                                    <p class="mb-0">Lihat hasil pengumuman kelulusan Anda</p>
                                </div>
                                <a href="pengumuman.php" class="btn btn-primary">
                                    <i class="bi bi-megaphone"></i> Lihat Pengumuman
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <form method="post" enctype="multipart/form-data" id="uploadForm">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <!-- Biodata Siswa -->
                            <h5 class="mt-4 bg-light p-2 rounded"><i class="bi bi-person"></i> Biodata Siswa</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>NISN</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($peserta['nisn']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label>NIK</label>
                                    <input type="text" class="form-control" name="nik" value="<?= htmlspecialchars($peserta['nik'] ?? '') ?>" 
                                           pattern="[0-9]{16}" title="NIK harus 16 digit" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Tempat Lahir</label>
                                    <input type="text" class="form-control" name="tempat_lahir" 
                                           value="<?= htmlspecialchars($peserta['tempat_lahir'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Tanggal Lahir</label>
                                    <input type="date" class="form-control" name="tanggal_lahir" 
                                           value="<?= htmlspecialchars($peserta['tanggal_lahir'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>No. WhatsApp Siswa</label>
                                    <input type="tel" class="form-control" name="no_wa_siswa" 
                                           value="<?= htmlspecialchars($peserta['no_wa_siswa'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Asal Sekolah</label>
                                    <input type="text" class="form-control" name="asal_sekolah" 
                                           value="<?= htmlspecialchars($peserta['asal_sekolah']) ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Tahun Lulus</label>
                                    <input type="number" class="form-control" name="tahun_lulus" 
                                           value="<?= htmlspecialchars($peserta['tahun_lulus'] ?? '') ?>" 
                                           min="2020" max="2025" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Jalur Pendaftaran</label>
                                    <?php if($peserta['jalur_id']): ?>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($peserta['nama_jalur']) ?>" readonly>
                                        <input type="hidden" name="jalur_id" value="<?= $peserta['jalur_id'] ?>">
                                    <?php else: ?>
                                        <select class="form-select" name="jalur_id" required>
                                            <?php foreach($jalur_list as $jalur): ?>
                                                <option value="<?= $jalur['id'] ?>" <?= ($peserta['jalur_id'] ?? '') == $jalur['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($jalur['nama_jalur']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <h6 class="mb-3">Alamat Siswa</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Jalan/Kampung</label>
                                    <input type="text" class="form-control" name="alamat_siswa_jalan" 
                                        value="<?= htmlspecialchars($peserta['alamat_siswa_jalan'] ?? '') ?>" required>
                                </div>
                            </div>

                            <h6 class="mb-3">Alamat Siswa</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>RT</label>
                                    <input type="number" class="form-control" name="alamat_siswa_rt" 
                                           value="<?= htmlspecialchars($peserta['alamat_siswa_rt'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label>RW</label>
                                    <input type="number" class="form-control" name="alamat_siswa_rw" 
                                           value="<?= htmlspecialchars($peserta['alamat_siswa_rw'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label>Kelurahan/Desa</label>
                                    <input type="text" class="form-control" name="alamat_siswa_kelurahan" 
                                           value="<?= htmlspecialchars($peserta['alamat_siswa_kelurahan'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label>Kecamatan</label>
                                    <input type="text" class="form-control" name="alamat_siswa_kecamatan" 
                                           value="<?= htmlspecialchars($peserta['alamat_siswa_kecamatan'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label>Kabupaten/Kota</label>
                                    <input type="text" class="form-control" name="alamat_siswa_kota" 
                                           value="<?= htmlspecialchars($peserta['alamat_siswa_kota'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="col-md-6">
                                    <label>Agama</label>
                                    <select class="form-select" name="agama_siswa" required>
                                        <?php
                                        $agama_list = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
                                        foreach($agama_list as $agama_siswa): ?>
                                            <option value="<?= $agama_siswa ?>" <?= ($peserta['agama_siswa'] ?? '') === $agama_siswa ? 'selected' : '' ?>>
                                                <?= $agama_siswa ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mt-2">
                                    <label>Jenis Kelamin</label>
                                    <select class="form-select" name="jenis_kelamin" required>
                                        <?php
                                        $kelamin_list = ['Laki-Laki', 'Perempuan'];
                                        foreach($kelamin_list as $jenis_kelamin): ?>
                                            <option value="<?= $jenis_kelamin ?>" <?= ($peserta['jenis_kelamin'] ?? '') === $jenis_kelamin ? 'selected' : '' ?>>
                                                <?= $jenis_kelamin ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                            </div>

                            <!-- Biodata Orang Tua -->
                            <h5 class="mt-4 bg-light p-2 rounded"><i class="bi bi-people"></i> Biodata Orang Tua</h5>
                            <div class="mb-3">
                                <label>No. KK</label>
                                <input type="NUMBER" class="form-control" name="no_kk" 
                                       value="<?= htmlspecialchars($peserta['no_kk'] ?? '') ?>" required>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Nama Ayah</label>
                                    <input type="text" class="form-control" name="nama_ayah" 
                                           value="<?= htmlspecialchars($peserta['nama_ayah'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Nama Ibu</label>
                                    <input type="text" class="form-control" name="nama_ibu" 
                                           value="<?= htmlspecialchars($peserta['nama_ibu'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Tempat Lahir Ayah</label>
                                    <input type="text" class="form-control" name="tempat_lahir_ayah" 
                                           value="<?= htmlspecialchars($peserta['tempat_lahir_ayah'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Tempat Lahir Ibu</label>
                                    <input type="text" class="form-control" name="tempat_lahir_ibu" 
                                           value="<?= htmlspecialchars($peserta['tempat_lahir_ibu'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Tanggal Lahir Ayah</label>
                                    <input type="date" class="form-control" name="tanggal_lahir_ayah" 
                                           value="<?= htmlspecialchars($peserta['tanggal_lahir_ayah'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Tanggal Lahir Ibu</label>
                                    <input type="date" class="form-control" name="tanggal_lahir_ibu" 
                                           value="<?= htmlspecialchars($peserta['tanggal_lahir_ibu'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Pekerjaan Ayah</label>
                                    <input type="text" class="form-control" name="pekerjaan_ayah" 
                                           value="<?= htmlspecialchars($peserta['pekerjaan_ayah'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Pekerjaan Ibu</label>
                                    <input type="text" class="form-control" name="pekerjaan_ibu" 
                                           value="<?= htmlspecialchars($peserta['pekerjaan_ibu'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Agama Ayah</label>
                                    <select class="form-select" name="agama_ayah" required>
                                        <?php foreach($agama_list as $agama): ?>
                                            <option value="<?= $agama ?>" <?= ($peserta['agama_ayah'] ?? '') === $agama ? 'selected' : '' ?>>
                                                <?= $agama ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>Agama Ibu</label>
                                    <select class="form-select" name="agama_ibu" required>
                                        <?php foreach($agama_list as $agama): ?>
                                            <option value="<?= $agama ?>" <?= ($peserta['agama_ibu'] ?? '') === $agama ? 'selected' : '' ?>>
                                                <?= $agama ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <h6 class="mb-3">Alamat Orang Tua</h6>
                            <div class="mb-3">
                                <label>Jalan/Kampung</label>
                                <input type="text" class="form-control" name="alamat_ortu_jalan" 
                                       value="<?= htmlspecialchars($peserta['alamat_ortu_jalan'] ?? '') ?>" required>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>RT</label>
                                    <input type="number" class="form-control" name="alamat_ortu_rt" 
                                           value="<?= htmlspecialchars($peserta['alamat_ortu_rt'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label>RW</label>
                                    <input type="number" class="form-control" name="alamat_ortu_rw" 
                                           value="<?= htmlspecialchars($peserta['alamat_ortu_rw'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label>Kelurahan/Desa</label>
                                    <input type="text" class="form-control" name="alamat_ortu_kelurahan" 
                                           value="<?= htmlspecialchars($peserta['alamat_ortu_kelurahan'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label>Kecamatan</label>
                                    <input type="text" class="form-control" name="alamat_ortu_kecamatan" 
                                           value="<?= htmlspecialchars($peserta['alamat_ortu_kecamatan'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label>Kabupaten/Kota</label>
                                    <input type="text" class="form-control" name="alamat_ortu_kota" 
                                           value="<?= htmlspecialchars($peserta['alamat_ortu_kota'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label>No. Telp Orang Tua</label>
                                <input type="tel" class="form-control" name="no_telp_ortu" 
                                       value="<?= htmlspecialchars($peserta['no_telp_ortu'] ?? '') ?>" required>
                            </div>

                            <!-- Data Lainnya -->
                            <h5 class="mt-4 bg-light p-2 rounded"><i class="bi bi-card-list"></i> Data Lainnya</h5>

                            <div class="mb-3">
                                <label>Program Keahlian</label>
                                <select class="form-select" name="program_keahlian" required>
                                    <option value="">- Pilih -</option>
                                    <?php
                                    $program_list = ['IPA', 'IPS', 'Bahasa', 'Keagamaan'];
                                    foreach($program_list as $program): ?>
                                        <option value="<?= $program ?>" <?= ($peserta['program_keahlian'] ?? '') === $program ? 'selected' : '' ?>>
                                            <?= $program ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?php if($peserta['jarak_ke_sekolah']): ?>
                            <div class="mb-3">
                                <label>Jarak ke Sekolah</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($peserta['jarak_ke_sekolah']) ?> km" readonly>
                                <small class="text-muted">Diisi oleh admin/panitia</small>
                            </div>
                            <?php endif; ?>

                            <!-- Upload Dokumen Section -->
                            <h5 class="mt-4">Upload Dokumen</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6>Kartu Keluarga</h6>
                                            <?php 
                                            $folder_name = str_replace(' ', '_', strtolower($peserta['nama_lengkap']));
                                            if(!empty($peserta['file_kk']) && file_exists("File/{$folder_name}/{$peserta['file_kk']}")): ?>
                                                <p class="mb-2"><small class="text-muted"><?= htmlspecialchars($peserta['file_kk']) ?></small></p>
                                            <?php endif; ?>
                                            <div class="mt-2">
                                                <input type="file" class="form-control file-upload" name="file_kk" 
                                                       accept="application/pdf" data-type="kk">
                                                <small class="text-muted">Upload KK (PDF, max 5MB)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6>Akte Kelahiran</h6>
                                            <?php if(!empty($peserta['file_akte']) && file_exists("File/{$folder_name}/{$peserta['file_akte']}")): ?>
                                                <p class="mb-2"><small class="text-muted"><?= htmlspecialchars($peserta['file_akte']) ?></small></p>
                                            <?php endif; ?>
                                            <div class="mt-2">
                                                <input type="file" class="form-control file-upload" name="file_akte" 
                                                       accept="application/pdf" data-type="akte">
                                                <small class="text-muted">Upload Akte (PDF, max 5MB)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6>Pas Foto</h6>
                                            <?php if(!empty($peserta['file_photo']) && file_exists("File/{$folder_name}/{$peserta['file_photo']}")): ?>
                                                <p class="mb-2"><small class="text-muted"><?= htmlspecialchars($peserta['file_photo']) ?></small></p>
                                            <?php endif; ?>
                                            <div class="mt-2">
                                                <input type="file" class="form-control file-upload" name="file_photo" 
                                                       accept="image/jpeg,image/png" data-type="photo">
                                                <small class="text-muted">Upload Foto (JPG/PNG, max 5MB)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Raport uploads -->
                            <div class="row">
                                <h6 class="mb-3">Raport Semester (PDF)</h6>
                                <?php for($i = 1; $i <= 5; $i++): 
                                    $field_name = "file_raport_" . $i;
                                ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6>Semester <?= $i ?></h6>
                                                <?php if(!empty($peserta[$field_name]) && file_exists("File/{$folder_name}/{$peserta[$field_name]}")): ?>
                                                    <p class="mb-2"><small class="text-muted"><?= htmlspecialchars($peserta[$field_name]) ?></small></p>
                                                <?php endif; ?>
                                                <div class="mt-2">
                                                    <input type="file" class="form-control file-upload" 
                                                           name="<?= $field_name ?>" accept="application/pdf" 
                                                           data-type="raport" data-semester="<?= $i ?>">
                                                    <small class="text-muted">Upload Raport Semester <?= $i ?> (PDF, max 5MB)</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>

                            <div id="uploadStatus" class="alert alert-info" style="display: none;">
                                <div class="d-flex align-items-center">
                                    <div class="spinner-border spinner-border-sm me-2"></div>
                                    <span>Mengupload file...</span>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-save"></i> Simpan Data
                            </button>
                        </form>

                        <script>
                        async function confirmDelete(e) {
                            e.preventDefault();
                            const result = await Swal.fire({
                                title: 'Hapus Prestasi',
                                text: 'Apakah Anda yakin ingin menghapus prestasi ini?',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                cancelButtonColor: '#3085d6',
                                confirmButtonText: 'Ya, Hapus!',
                                cancelButtonText: 'Batal'
                            });

                            if (result.isConfirmed) {
                                const form = e.target;
                                form.submit();
                            }
                            return false;
                        }

                        // Simplified form submission
                        const uploadForm = document.getElementById('uploadForm');
                        if (uploadForm) {
                            uploadForm.addEventListener('submit', function(e) {
                                e.preventDefault();
                                
                                // Validasi ukuran file
                                const files = document.querySelectorAll('.file-upload');
                                let isValid = true;
                                
                                files.forEach(input => {
                                    if (input.files.length > 0) {
                                        const file = input.files[0];
                                        if (file.size > 5 * 1024 * 1024) {
                                            alert(`File ${file.name} melebihi batas maksimal 5MB`);
                                            isValid = false;
                                        }
                                    }
                                });

                                if (!isValid) return;

                                const formData = new FormData(uploadForm);
                                
                                fetch('process/upload_handler.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(result => {
                                    if (result.success) {
                                        window.location.href = 'dashboard.php?success=1';
                                    } else {
                                        window.location.href = 'dashboard.php?error=1';
                                    }
                                })
                                .catch(error => {
                                    window.location.href = 'dashboard.php?error=1';
                                });
                            });
                        }

                        // Handle URL parameters for alerts
                        window.addEventListener('load', function() {
                            const urlParams = new URLSearchParams(window.location.search);
                            
                            if (urlParams.has('success')) {
                                Swal.fire({
                                    title: 'Berhasil!',
                                    text: 'Data berhasil disimpan',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    // Hapus parameter dari URL tanpa reload
                                    window.history.replaceState({}, '', 'dashboard.php');
                                });
                            } else if (urlParams.has('error')) {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Terjadi kesalahan saat menyimpan data',
                                    icon: 'error'
                                }).then(() => {
                                    window.history.replaceState({}, '', 'dashboard.php');
                                });
                            }
                        });
                        </script>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="bi bi-trophy"></i> Prestasi</h4>
                    </div>
                    <div class="card-body">
                        <form method="post" class="mb-4" onsubmit="return handleAddPrestasi(event)">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="add_prestasi" value="1">
                            
                            <div class="mb-3">
                                <label>Bidang Prestasi</label>
                                <input type="text" class="form-control" name="bidang_prestasi" required>
                            </div>

                            <div class="mb-3">
                                <label>Peringkat/Hasil</label>
                                <input type="text" class="form-control" name="peringkat" required>
                            </div>

                            <div class="mb-3">
                                <label>Tingkat</label>
                                <select class="form-select" name="tingkat" required>
                                    <option value="Kecamatan">Kecamatan</option>
                                    <option value="Kabupaten">Kabupaten</option>
                                    <option value="Provinsi">Provinsi</option>
                                    <option value="Nasional">Nasional</option>
                                    <option value="Internasional">Internasional</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-plus-circle"></i> Tambah Prestasi
                            </button>
                        </form>

                        <h5>Daftar Prestasi</h5>
                        <?php if(empty($prestasi)): ?>
                            <p class="text-muted">Belum ada prestasi yang ditambahkan</p>
                        <?php else: ?>
                            <div class="list-group">
                            <?php foreach($prestasi as $p): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($p['bidang_prestasi']) ?></h6>
                                            <small class="text-muted">
                                                Peringkat: <?= htmlspecialchars($p['peringkat']) ?><br>
                                                Tingkat: <?= htmlspecialchars($p['tingkat']) ?>
                                            </small>
                                        </div>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <input type="hidden" name="delete_prestasi" value="1">
                                            <input type="hidden" name="prestasi_id" value="<?= $p['id'] ?>">
                                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(this)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this script before closing body tag -->
    <script>
    // Existing delete confirmation function
    function confirmDelete(button) {
        Swal.fire({
            title: 'Hapus Prestasi?',
            text: 'Prestasi yang dihapus tidak dapat dikembalikan',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = button.closest('form');
                form.submit();
            }
        });
    }

    // Add new function for handling prestasi submission
    function handleAddPrestasi(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Prestasi berhasil ditambahkan',
                timer: 1500,
                showConfirmButton: false,
                timerProgressBar: true
            }).then(() => {
                window.location.reload();
            });
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Gagal menambahkan prestasi'
            });
        });

        return false;
    }
    </script>
    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Kontak Kami</h5>
                    <p>
                        <i class="bi bi-geo-alt"></i> Jalan Provinsi Rt. 06 Kel. Muara Kelingi Kec. Muara Kelingi Kab. Musi Rawas.<br>
                        <i class="bi bi-telephone"></i> +62 813-6810-2412 <br>
                        <i class="bi bi-envelope"></i> syafii.imam317@gmail.com
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5>Media Sosial</h5>
                    <div class="social-links">
                        <a href="#" class="text-white me-2"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white me-2"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white me-2"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <small>&copy; <?= date('Y') ?> MAN 1 Musi Rawas. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/browser-image-compression/2.0.0/browser-image-compression.min.js"></script>
    <script>
        async function compressImage(file) {
            try {
                const options = {
                    maxSizeMB: 1,
                    maxWidthOrHeight: 800,
                    useWebWorker: true
                };
                return await imageCompression(file, options);
            } catch (error) {
                console.error('Error compressing image:', error);
                return file;
            }
        }

        document.getElementById('uploadForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const uploadStatus = document.getElementById('uploadStatus');
            
            try {
                submitBtn.disabled = true;
                uploadStatus.style.display = 'block';
                
                const formData = new FormData(this);
                const response = await fetch('process/upload_handler.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    // Show success alert first
                    await Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Data berhasil disimpan',
                        confirmButtonColor: '#28a745',
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Add extra delay before redirect
                    await new Promise(resolve => setTimeout(resolve, 500));
                    window.location.href = 'profile.php';
                } else {
                    throw new Error(result.message || 'Gagal menyimpan data');
                }
            } catch (error) {
                console.error('Error:', error);
                // Show error alert
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: error.message || 'Terjadi kesalahan saat menyimpan data',
                    confirmButtonColor: '#dc3545'
                });
            } finally {
                submitBtn.disabled = false;
                uploadStatus.style.display = 'none';
            }
        });

        // File validation and compression preview
        document.querySelectorAll('.file-upload').forEach(input => {
            input.addEventListener('change', async function() {
                if(this.files.length > 0) {
                    const file = this.files[0];
                    const maxSize = 5 * 1024 * 1024; // 5MB
                    const isPhoto = this.name === 'file_photo';
                    
                    if(file.size > maxSize) {
                        if(isPhoto) {
                            const compressed = await compressImage(file);
                            if(compressed.size <= maxSize) {
                                const container = new DataTransfer();
                                container.items.add(compressed);
                                this.files = container.files;
                                return;
                            }
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'File Terlalu Besar',
                            text: 'Ukuran file maksimal 5MB',
                            confirmButtonColor: '#d33'
                        });
                        this.value = '';
                        return;
                    }

                    const validPhotoTypes = ['image/jpeg', 'image/png'];
                    const validDocTypes = ['application/pdf'];
                    
                    if(isPhoto && !validPhotoTypes.includes(file.type)) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Format File Salah',
                            text: 'Foto harus berformat JPG atau PNG',
                            confirmButtonColor: '#d33'
                        });
                        this.value = '';
                    } else if(!isPhoto && !validDocTypes.includes(file.type)) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Format File Salah', 
                            text: 'Dokumen harus berformat PDF',
                            confirmButtonColor: '#d33'
                        });
                        this.value = '';
                    }
                }
            });
        });
    </script>
</body>
</html>
