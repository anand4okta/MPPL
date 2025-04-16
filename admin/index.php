<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/admin_auth.php';

// Check if admin is logged in
if(empty($_SESSION['admin'])) {
    $_SESSION['error'] = "Silakan login sebagai admin terlebih dahulu!";
    header("Location: login.php");
    exit;
}

// Fetch summary data
$stmt = $pdo->query("
    SELECT 
        COALESCE(COUNT(*), 0) as total_pendaftar,
        COALESCE(SUM(CASE WHEN v.status_verifikasi = 'Verified' THEN 1 ELSE 0 END), 0) as total_verified,
        COALESCE(SUM(CASE WHEN v.status_verifikasi = 'Rejected' THEN 1 ELSE 0 END), 0) as total_verif_rejected,
        COALESCE(SUM(CASE WHEN v.status_verifikasi IS NULL OR v.status_verifikasi = 'Pending' THEN 1 ELSE 0 END), 0) as total_pending,
        COALESCE(SUM(CASE WHEN p2.status_penerimaan = 'diterima' THEN 1 ELSE 0 END), 0) as total_accepted,
        COALESCE(SUM(CASE WHEN p2.status_penerimaan = 'ditolak' THEN 1 ELSE 0 END), 0) as total_tidak_diterima
    FROM peserta p
    LEFT JOIN verifikasi_peserta v ON p.id = v.peserta_id
    LEFT JOIN pengumuman p2 ON p.id = p2.peserta_id
");
$summary = $stmt->fetch();

// Fetch jalur pendaftaran statistics
$stmt = $pdo->query("
    SELECT jp.nama_jalur, COALESCE(COUNT(p.id), 0) as jumlah
    FROM jalur_pendaftaran jp
    LEFT JOIN peserta p ON jp.id = p.jalur_id
    GROUP BY jp.nama_jalur
");
$jalur_stats = $stmt->fetchAll();

// Fetch latest registrations
$stmt = $pdo->query("
    SELECT p.*, jp.nama_jalur, v.status_verifikasi 
    FROM peserta p 
    LEFT JOIN jalur_pendaftaran jp ON jp.id = p.jalur_id
    LEFT JOIN verifikasi_peserta v ON p.id = v.peserta_id 
    ORDER BY p.created_at DESC 
    LIMIT 10
");
$latest_registrations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - PPDB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'partials/admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row g-0"> <!-- Remove default row gap -->
            <div class="col-md-2 sidebar-container"> <!-- Reduce column width from 3 to 2 -->
                <?php include 'partials/admin_sidebar.php'; ?>
            </div>
            <div class="col-md-10 pt-4"> <!-- Increase from 9 to 10 to match new sidebar width -->
                <h2 class="mb-3">Dashboard Admin</h2>
                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mt-3">
                        <div class="card border-100 shadow-lg">
                            <div class="card-body text-center">
                                <div class="circle-icon bg-primary text-white mb-3">
                                    <i class="bi bi-person-add h3"></i>
                                </div>
                                <h5 class="card-title">Total Pendaftar</h5>
                                <h3><?= $summary['total_pendaftar'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mt-3">
                        <div class="card border-100 shadow-lg">
                            <div class="card-body text-center">
                                <div class="circle-icon bg-success text-white mb-3">
                                    <i class="bi bi-person-check h3"></i>
                                </div>
                                <h5 class="card-title">Terverifikasi</h5>
                                <h3><?= $summary['total_verified'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mt-3">
                        <div class="card border-100 shadow-lg">
                            <div class="card-body text-center">
                                <div class="circle-icon bg-warning text-white mb-3">
                                    <i class="bi bi-hourglass-split h3"></i>
                                </div>
                                <h5 class="card-title">Pending</h5>
                                <h3><?= $summary['total_pending'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mt-3">
                        <div class="card border-100 shadow-lg">
                            <div class="card-body text-center">
                                <div class="circle-icon bg-info text-white mb-3">
                                    <i class="bi bi-check-circle h3"></i>
                                </div>
                                <h5 class="card-title">Diterima</h5>
                                <h3><?= $summary['total_accepted'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mt-3">
                        <div class="card border-100 shadow-lg">
                            <div class="card-body text-center">
                                <div class="circle-icon bg-secondary text-white mb-3">
                                    <i class="bi bi-x-circle h3"></i>
                                </div>
                                <h5 class="card-title">Tidak Diterima</h5>
                                <h3><?= $summary['total_tidak_diterima'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mt-3">
                        <div class="card border-100 shadow-lg">
                            <div class="card-body text-center">
                                <div class="circle-icon bg-danger text-white mb-3">
                                    <i class="bi bi-x-octagon h3"></i>
                                </div>
                                <h5 class="card-title">Ditolak (Verifikasi)</h5>
                                <h3><?= $summary['total_verif_rejected'] ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jalur Pendaftaran Stats -->
                <h2 class="mb-4">Berdasarkan Jalur Penerimaan</h2>
                <div class="row mb-4">
                    <?php 
                    $colors = ['primary', 'success', 'warning', 'info', 'secondary', 'danger'];
                    $icons = ['door-open', 'house', 'houses', 'trophy', 'star', 'book'];
                    $i = 0;
                    foreach($jalur_stats as $jalur): 
                    ?>
                    <div class="col-md-3 mb-3">
                        <div class="card border-100 shadow-lg">
                            <div class="card-body text-center">
                                <div class="circle-icon bg-<?= $colors[$i % count($colors)] ?> text-white mb-3">
                                    <i class="bi bi-<?= $icons[$i % count($icons)] ?> h3"></i>
                                </div>
                                <h5 class="card-title"><?= htmlspecialchars($jalur['nama_jalur']) ?></h5>
                                <h3><?= htmlspecialchars($jalur['jumlah']) ?></h3>
                            </div>
                        </div>
                    </div>
                    <?php 
                    $i++;
                    endforeach; 
                    ?>
                </div>

                <!-- Latest Registrations -->
                <div class="card border-100 shadow-lg">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><i class="bi bi-clock-history"></i> Pendaftar Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>NISN</th>
                                        <th>Nama</th>
                                        <th>Jalur</th>
                                        <th>Status</th>
                                        <th>Tanggal Daftar</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($latest_registrations as $reg): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($reg['nisn']) ?></td>
                                            <td><?= htmlspecialchars($reg['nama_lengkap']) ?></td>
                                            <td><?= htmlspecialchars($reg['nama_jalur']) ?></td>
                                            <td>
                                                <?php
                                                $status = $reg['status_verifikasi'] ?? 'Pending';
                                                $statusClass = [
                                                    'Pending' => 'warning',
                                                    'Verified' => 'success',
                                                    'Rejected' => 'danger'
                                                ][$status];
                                                ?>
                                                <span class="badge bg-<?= $statusClass ?>">
                                                    <?= htmlspecialchars($status) ?>
                                                </span>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($reg['created_at'])) ?></td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="verifikasi.php?id=<?= $reg['id'] ?>" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <?php if($status === 'Verified'): ?>
                                                        <div class="announcement-button-container">
                                                            <a href="pengumuman.php?id=<?= $reg['id'] ?>" 
                                                               class="btn btn-sm btn-success">
                                                                <i class="bi bi-megaphone"></i>
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <style>
                    .announcement-button-container {
                        display: inline-block;
                    }
                    @media (min-width: 768px) {
                        .announcement-button-container {
                            margin-left: 4px;
                        }
                        .sidebar-container {
                            position: sticky;
                            height: calc(100vh - 56px); /* Adjust for typical navbar height */
                            top: 56px; /* Match navbar height */
                            z-index: 100; /* Ensure proper stacking */
                            padding-right: 0; /* Remove right padding */
                        }
                    }
                    @media (max-width: 767px) {
                        .sidebar-container {
                            position: relative;
                            height: auto;
                            top: 0;
                            padding: 0; /* Remove padding on mobile */
                        }
                    }
                </style>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
