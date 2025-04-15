<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/admin_auth.php';

if(empty($_SESSION['admin'])) {
    $_SESSION['error'] = "Silakan login sebagai admin terlebih dahulu!";
    header("Location: login.php");
    exit;
}

$stmt = $pdo->query("SELECT p.*, jp.nama_jalur, v.status_verifikasi 
    FROM peserta p 
    LEFT JOIN jalur_pendaftaran jp ON jp.id = p.jalur_id
    LEFT JOIN verifikasi_peserta v ON p.id = v.peserta_id 
    ORDER BY p.created_at DESC");
$peserta_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Peserta - PPDB</title>
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
                <h2 class="mb-4">Data Peserta</h2>
                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>NISN</th>
                                        <th>Nama</th>
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
                                            $status = $peserta['status_verifikasi'] ?? 'Pending';
                                            $statusClass = [
                                                'Pending' => 'warning',
                                                'Verified' => 'success',
                                                'Rejected' => 'danger'
                                            ][$status];
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
                                        </td>
                                        <td>
                                            <a href="profile_siswa.php?id=<?= $peserta['id'] ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i> Lihat Detail
                                            </a>
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
