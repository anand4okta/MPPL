<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__.'/config/db.php';
require_once __DIR__.'/includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$csrf_token = generateCsrfToken();

// Set timezone untuk semua operasi
date_default_timezone_set('Asia/Jakarta');

// Dapatkan waktu sekarang dengan timezone yang benar
$now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$current_date = $now->format('Y-m-d H:i:s');

// Calculate academic year based on current date
$current_year = (int)$now->format('Y');
$current_month = (int)$now->format('m');

// If we're past June (month 6), use next year as the start of academic year
$academic_year_start = ($current_month > 6) ? $current_year + 1 : $current_year;
$academic_year_end = $academic_year_start + 1;

// Update query untuk menampilkan jalur satu per satu sesuai urutan yang diinginkan
$query = "SELECT j.*, 
          DATE_FORMAT(j.tanggal_buka, '%d %b %Y') AS formatted_tanggal_buka, 
          DATE_FORMAT(j.tanggal_tutup, '%d %b %Y') AS formatted_tanggal_tutup,
          DATE_FORMAT(j.tanggal_pengumuman, '%d %b %Y') AS formatted_tanggal_pengumuman,
          j.tanggal_buka as real_tanggal_buka,
          j.tanggal_tutup as real_tanggal_tutup,
          (SELECT COUNT(*) FROM peserta WHERE jalur_id = j.id) as total_pendaftar
          FROM jalur_pendaftaran j 
          WHERE j.nama_jalur = ? 
          LIMIT 1";

// Array untuk menyimpan semua jalur
$jalur_pendaftaran = [];
$jalur_order = ['Reguler', 'Prestasi', 'Tahfidz', 'Pondok Pesantren', 'Afirmasi', 'Domisili'];

// Ambil data untuk setiap jalur
foreach ($jalur_order as $nama_jalur) {
    $stmt = $pdo->prepare($query);
    $stmt->execute([$nama_jalur]);
    $jalur = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($jalur) {
        // Hitung sisa kuota
        $jalur['sisa_kuota'] = $jalur['kuota'] - $jalur['total_pendaftar'];
        
        // Tentukan status jalur
        if ($current_date < $jalur['real_tanggal_buka']) {
            $jalur['status_jalur'] = 'Belum Dibuka';
        } elseif ($current_date >= $jalur['real_tanggal_tutup']) {
            $jalur['status_jalur'] = 'Ditutup';
        } elseif ($jalur['sisa_kuota'] <= 0) {
            $jalur['status_jalur'] = 'Kuota Penuh';
        } else {
            $jalur['status_jalur'] = 'Dibuka';
        }
        
        $jalur_pendaftaran[] = $jalur;
    }
}

// Update active jalur dengan logika yang lebih ketat
$active_jalur = array_filter($jalur_pendaftaran, function($jalur) use ($current_date) {
    return $jalur['status_jalur'] === 'Dibuka' && $jalur['sisa_kuota'] > 0;
});

// Get registration message from session and clear it
$registration_message = isset($_SESSION['registration_success']) ? $_SESSION['registration_success'] : '';
unset($_SESSION['registration_success']);

// Get error message from session and clear it
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPDB MAN 1 Musi Rawas</title>
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
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-white">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center text-primary" href="#">
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
                    <li class="nav-item">
                        <a class="nav-link text-dark active" href="index.php">
                            <i class="bi bi-house text-primary"></i> Beranda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="#alur">
                            <i class="bi bi-diagram-3 text-primary"></i> Alur
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="#jadwal">
                            <i class="bi bi-calendar-event text-primary"></i> Jadwal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="#faq">
                            <i class="bi bi-question-circle text-primary"></i> FAQ
                        </a>
                    </li>
                </ul>
                <div class="d-flex gap-2">
                    <?php if(isset($_SESSION['user'])): ?>
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a href="profile.php" class="btn btn-success">
                            <i class="bi bi-person"></i> Profile
                        </a>
                        <a href="logout.php" class="btn btn-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    <?php else: ?>
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#daftarModal">
                            <i class="bi bi-person-plus"></i> Daftar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Success/Error Alerts -->
    <?php if($registration_message): ?>
    <div class="container">
        <div class="alert alert-success alert-dismissible fade show mt-4 mb-0 shadow-lg border-100" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong><?= htmlspecialchars($registration_message) ?></strong>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>

    <?php if($error_message): ?>
    <div class="container">
        <div class="alert alert-danger alert-dismissible fade show mt-4 mb-0 shadow-lg border-100" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong><?= htmlspecialchars($error_message) ?></strong>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="container">
        <!-- Alur Pendaftaran Section -->
        <section class="mb-4 mt-0 pt-2" id="alur">
            <h2 class="text-center mb-4">Alur Pendaftaran</h2>
            <div class="row g-4 justify-content-center">
                <div class="col-md-3">
                    <div class="card h-100 border-100 shadow-lg">
                        <div class="card-body text-center">
                            <div class="circle-icon bg-primary text-white mb-3">
                                <i class="bi bi-person-plus h3"></i>
                            </div>
                            <h5 class="card-title">1. Pendaftaran Akun</h5>
                            <p class="card-text">Daftar akun dengan mengisi NISN dan data pribadi</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 border-100 shadow-lg">
                        <div class="card-body text-center">
                            <div class="circle-icon bg-success text-white mb-3">
                                <i class="bi bi-file-text h3"></i>
                            </div>
                            <h5 class="card-title">2. Lengkapi Biodata</h5>
                            <p class="card-text">Isi formulir biodata dengan lengkap dan benar</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 border-100 shadow-lg">
                        <div class="card-body text-center">
                            <div class="circle-icon bg-info text-white mb-3">
                                <i class="bi bi-upload h3"></i>
                            </div>
                            <h5 class="card-title">3. Upload Berkas</h5>
                            <p class="card-text">Upload dokumen yang diperlukan sesuai persyaratan</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 border-100 shadow-lg">
                        <div class="card-body text-center">
                            <div class="circle-icon bg-warning text-white mb-3">
                                <i class="bi bi-clock-history h3"></i>
                            </div>
                            <h5 class="card-title">4. Tunggu Hasil</h5>
                            <p class="card-text">Pantau status pendaftaran di dashboard</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Jadwal Section -->
        <section class="mb-4 mt-0 pt-4" id="jadwal">
            <h2 class="text-center mb-4">Jadwal Pendaftaran</h2>
            <div class="row">
                <div class="col-lg-8">
                    <div class="timeline">
                        <?php foreach ($jalur_pendaftaran as $jalur): ?>
                        <div class="card mb-3 border-100 shadow-lg">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-primary me-2">
                                        <i class="bi bi-calendar3"></i>
                                    </span>
                                    <h5 class="card-title mb-0"><?= htmlspecialchars($jalur['nama_jalur']) ?></h5>
                                </div>
                                <div class="timeline-info">
                                    <p class="mb-2">
                                        <i class="bi bi-calendar-check text-success"></i>
                                        <strong>Pendaftaran:</strong> 
                                        <?= htmlspecialchars($jalur['formatted_tanggal_buka']) ?> - <?= htmlspecialchars($jalur['formatted_tanggal_tutup']) ?>
                                    </p>
                                    <p class="mb-2">
                                        <i class="bi bi-calendar-event text-info"></i>
                                        <strong>Pengumuman:</strong> 
                                        <?= htmlspecialchars($jalur['formatted_tanggal_pengumuman']) ?>
                                    </p>
                                    <div class="d-flex flex-wrap gap-2 mb-2">
                                        <span class="badge bg-<?= $jalur['status_jalur'] === 'Dibuka' ? 'success' : 
                                                              ($jalur['status_jalur'] === 'Kuota Penuh' ? 'danger' : 
                                                              ($jalur['status_jalur'] === 'Ditutup' ? 'secondary' : 'warning')) ?>">
                                            Status: <?= htmlspecialchars($jalur['status_jalur']) ?>
                                        </span>
                                        <?php if ($jalur['status_jalur'] !== 'Ditutup'): ?>
                                            <span class="badge bg-<?= $jalur['sisa_kuota'] <= 0 ? 'danger' : 'info' ?>">
                                                Kuota Tersedia: <?= htmlspecialchars($jalur['sisa_kuota']) ?> dari <?= htmlspecialchars($jalur['kuota']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($jalur['sisa_kuota'] <= 0): ?>
                                        <div class="alert alert-danger p-2 mb-0">
                                            <i class="bi bi-exclamation-triangle-fill"></i> 
                                            Kuota pendaftaran telah penuh (<?= htmlspecialchars($jalur['total_pendaftar']) ?>/<?= htmlspecialchars($jalur['kuota']) ?> pendaftar)
                                        </div>
                                    <?php elseif ($jalur['status_jalur'] === 'Belum Dibuka'): ?>
                                        <div class="alert alert-info p-2 mb-0">
                                            <i class="bi bi-info-circle-fill"></i> Pendaftaran akan dibuka pada <?= htmlspecialchars($jalur['formatted_tanggal_buka']) ?>
                                        </div>
                                    <?php elseif ($jalur['status_jalur'] === 'Ditutup'): ?>
                                        <div class="alert alert-secondary p-2 mb-0">
                                            <i class="bi bi-lock-fill"></i> Pendaftaran telah ditutup pada <?= htmlspecialchars($jalur['formatted_tanggal_tutup']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card border-100 shadow-lg">
                            <div class="card-body">
                                <h5 class="card-title mb-4">
                                    <i class="bi bi-file-text"></i> Persyaratan Umum
                                </h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">
                                        <i class="bi bi-check-circle text-success"></i> Fotokopi/Scan Ijazah/SKL
                                    </li>
                                    <li class="list-group-item">
                                        <i class="bi bi-check-circle text-success"></i> Fotokopi/Scan Akta Kelahiran
                                    </li>
                                    <li class="list-group-item">
                                        <i class="bi bi-check-circle text-success"></i> Fotokopi/Scan Kartu Keluarga
                                    </li>
                                    <li class="list-group-item">
                                        <i class="bi bi-check-circle text-success"></i> Pas Foto 3x4 (2 lembar)
                                    </li>
                                    <li class="list-group-item">
                                        <i class="bi bi-check-circle text-success"></i> Fotokopi/Scan Raport Semester 1-5
                                    </li>
                                </ul>
                            </div>
                        </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="mb-4 mt-0 pt-4" id="faq">
            <h2 class="text-center mb-4">Frequently Asked Questions</h2>
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item border-100 shadow-lg">
                    <h3 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            Bagaimana cara mendaftar PPDB Online?
                        </button>
                    </h3>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Untuk mendaftar PPDB Online, ikuti langkah berikut:
                            <ol>
                                <li>Klik Tombol "Daftar" Di Bagian Kanan Atas Halaman</li>
                                <li>Isi Formulir Pendaftaran Dengan Data Yang Valid</li>
                                <li>Setelah Mendaftar, Login Dan Lengkapi Biodata</li>
                                <li>Upload Dokumen Yang Diperlukan</li>
                                <li>Tunggu Verifikasi Dari Petugas</li>
                                <li>Tunggu Pengumuman Di Halaman Dashboard</li>
                            </ol>
                        </div>
                    </div>
                </div>
                <div class="accordion-item border-100 shadow-lg">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            Apa saja jalur pendaftaran yang tersedia?
                        </button>
                    </h3>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Jalur pendaftaran yang tersedia meliputi:
                            <ul>
                                <?php foreach ($jalur_pendaftaran as $jalur): ?>
                                <li>Jalur <?= htmlspecialchars($jalur['nama_jalur']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            Setiap jalur memiliki persyaratan dan kuota yang berbeda.
                        </div>
                    </div>
                </div>
                <div class="accordion-item border-100 shadow-lg">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            Bagaimana cara mengetahui hasil seleksi?
                        </button>
                    </h3>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Hasil seleksi dapat dilihat melalui:
                            <ul>
                                <li>Login ke akun PPDB</li>
                                <li>Lihat pengumuman di dashboard</li>
                                <li>Hubungi Admin/Petugas Untuk Info Lebih Lanjut</li>
                            </ul>
                            Pengumuman akan dilakukan sesuai jadwal yang telah ditentukan.
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

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
                        <a href="#" class="text-white me-2"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <small>&copy; <?= date('Y') ?> MAN 1 Musi Rawas. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <!-- Modal Daftar -->
    <div class="modal fade" id="daftarModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus"></i> Form Pendaftaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="process/register_process.php" method="POST" id="formDaftar">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama_lengkap" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">NISN</label>
                        <input type="number" class="form-control" name="nisn" required pattern="[0-9]{10}" 
                               title="NISN harus 10 digit angka">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">No. WhatsApp</label>
                        <input type="number" class="form-control" name="no_wa_siswa" 
                            required pattern="^08\d{9,12}$" 
                            title="Contoh: 081234567890 (minimal 10 digit)"
                            placeholder="08xxx">
                        <small class="text-muted">Contoh: 081234567890</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jalur Pendaftaran</label>
                        <select class="form-select" name="jalur_id" required>
                            <option value="">Pilih Jalur</option>
                            <?php foreach($active_jalur as $jalur): ?>
                                <option value="<?= $jalur['id'] ?>"><?= htmlspecialchars($jalur['nama_jalur']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="password" id="registerPassword" required>
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="togglePassword('registerPassword')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="confirm_password" 
                                   id="registerConfirmPassword" required>
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="togglePassword('registerConfirmPassword')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">CAPTCHA</label>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <span id="captcha-timer" class="badge bg-danger ms-2">05:00</span>
                                <small class="text-muted ms-2">refresh manual jika captcha tidak muncul</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" 
                                onclick="refreshCaptcha('captcha-code', 'captcha-timer', 'captcha-input', 'register')">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <strong id="captcha-code"><?= $_SESSION['captcha']['code'] ?? '' ?></strong>
                            </span>
                            <input type="text" class="form-control" name="captcha" 
                                   id="captcha-input" required 
                                   pattern="[A-Za-z0-9]{6}" 
                                   title="Masukkan 6 karakter CAPTCHA">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Daftar</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Modal Login -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalLabel">
                        <i class="bi bi-box-arrow-in-right"></i> Form Login
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="process/login_process.php" method="POST" id="formLogin">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">NISN</label>
                            <input type="number" class="form-control" name="nisn" required 
                                pattern="[0-9]{10}" title="NISN harus 10 digit angka">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="loginPassword" required>
                                <button class="btn btn-outline-secondary" type="button" 
                                        onclick="togglePassword('loginPassword')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">CAPTCHA</label>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <span id="login-captcha-timer" class="badge bg-danger ms-2">05:00</span>
                                    <small class="text-muted ms-2">refresh manual jika captcha tidak muncul</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary" 
                                    onclick="refreshCaptcha('login-captcha-code', 'login-captcha-timer', 'login-captcha-input', 'login')">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                </button>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <strong id="login-captcha-code"><?= $_SESSION['captcha']['code'] ?? '' ?></strong>
                                </span>
                                <input type="text" class="form-control" name="captcha" 
                                    id="login-captcha-input" required 
                                    pattern="[A-Za-z0-9]{6}" 
                                    title="Masukkan 6 karakter CAPTCHA">
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
                            <label class="form-check-label" for="rememberMe">Ingat Saya</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Tutup
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll for navbar links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // All CAPTCHA functionality is now handled by functions.php getJavaScript()
        <?= getJavaScript() ?>
    </script>
</body>
</html>
