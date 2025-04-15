<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/admin_auth.php';

if(empty($_SESSION['admin'])) {
    $_SESSION['error'] = "Silakan login sebagai admin terlebih dahulu!";
    header("Location: login.php");
    exit;
}

// Fetch verified participants for announcement
$stmt = $pdo->query("
    SELECT p.*, jp.nama_jalur, v.status_verifikasi, pg.status_penerimaan
    FROM peserta p 
    LEFT JOIN jalur_pendaftaran jp ON jp.id = p.jalur_id
    LEFT JOIN verifikasi_peserta v ON p.id = v.peserta_id 
    LEFT JOIN pengumuman pg ON p.id = pg.peserta_id
    WHERE v.status_verifikasi = 'Verified'
    ORDER BY p.created_at DESC");
$peserta_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengumuman - Admin PPDB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'partials/admin_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row g-0">
            <div class="col-md-2 sidebar-container">
                <?php include 'partials/admin_sidebar.php'; ?>
            </div>
            <div class="col-md-10 pt-4">
                <h2 class="mb-4">Keputusan Penerimaan Siswa</h2>
                <div class="card shadow">
                    <div class="card-body">
                        <?php if(isset($_SESSION['success'])): ?>
                            <div class="alert alert-success">
                                <?= $_SESSION['success']; ?>
                                <?php unset($_SESSION['success']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?= $_SESSION['error']; ?>
                                <?php unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>NISN</th>
                                        <th>Nama Lengkap</th>
                                        <th>Jalur</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($peserta_list as $peserta): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($peserta['nisn']) ?></td>
                                        <td><?= htmlspecialchars($peserta['nama_lengkap']) ?></td>
                                        <td><?= htmlspecialchars($peserta['nama_jalur']) ?></td>
                                        <td>
                                            <?php 
                                            $status = $peserta['status_penerimaan'] ?? 'Belum diputuskan';
                                            $badge_class = [
                                                'diterima' => 'success',
                                                'ditolak' => 'danger',
                                                'Belum diputuskan' => 'warning'
                                            ][$status];
                                            ?>
                                            <span class="badge bg-<?= $badge_class ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="process/process_pengumuman.php?id=<?= $peserta['id'] ?>&status=diterima" 
                                                   class="btn btn-sm btn-success"
                                                   onclick="return confirm('Yakin ingin menerima peserta ini?')">
                                                    <i class="bi bi-check-circle"></i> Terima
                                                </a>
                                                <a href="process/process_pengumuman.php?id=<?= $peserta['id'] ?>&status=ditolak" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Yakin ingin menolak peserta ini?')">
                                                    <i class="bi bi-x-circle"></i> Tolak
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
