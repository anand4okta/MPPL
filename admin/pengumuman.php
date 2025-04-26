<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/admin_auth.php';

if(empty($_SESSION['admin'])) {
    $_SESSION['error'] = "Silakan login sebagai admin terlebih dahulu!";
    header("Location: login.php");
    exit;
}

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    $query = "SELECT 
                p.*, 
                jp.nama_jalur, 
                v.status_verifikasi, 
                pg.status_penerimaan,
                DATE_FORMAT(p.created_at, '%d/%m/%Y') as tanggal_daftar
            FROM peserta p 
            LEFT JOIN jalur_pendaftaran jp ON jp.id = p.jalur_id
            LEFT JOIN verifikasi_peserta v ON p.id = v.peserta_id 
            LEFT JOIN pengumuman pg ON p.id = pg.peserta_id
            WHERE v.status_verifikasi = 'Verified'";

    $params = array();
    if (!empty($search)) {
        $searchLike = '%' . strtolower($search) . '%';
        $query .= " AND (
            LOWER(p.nama_lengkap) LIKE :searchName 
            OR LOWER(p.nisn) LIKE :searchNisn
        )";
        $params[':searchName'] = $searchLike;
        $params[':searchNisn'] = $searchLike;
    }

    $query .= " ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    
    if (!$stmt->execute()) {
        throw new PDOException("Failed to execute query");
    }
    
    $peserta_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add search result message
    if (!empty($search)) {
        $count = count($peserta_list);
        $searchMessage = "Ditemukan {$count} hasil pencarian untuk \"{$search}\"";
    }

} catch (PDOException $e) {
    error_log("Database error in pengumuman.php: " . $e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan saat mencari data";
    $peserta_list = [];
}
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'partials/admin_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row g-0">
            <div class="col-md-2 sidebar-container">
                <?php include 'partials/admin_sidebar.php'; ?>
            </div>
            <div class="col-md-10 pt-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">Keputusan Penerimaan Siswa</h2>
                    <form class="d-flex" role="search" method="GET" style="width: 400px;">
                        <input class="form-control me-2" type="search" name="search" 
                               placeholder="Cari nama atau NISN..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-outline-primary" type="submit">Cari</button>
                        <?php if(!empty($search)): ?>
                            <a href="pengumuman.php" class="btn btn-outline-secondary ms-2">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if(!empty($search)): ?>
                    <div class="alert alert-info">
                        <?= $searchMessage ?>
                    </div>
                <?php endif; ?>

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
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>NISN</th>
                                        <th>Nama Lengkap</th>
                                        <th>Jalur</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($peserta_list as $index => $peserta): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
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
                                                   onclick="return confirmStatus(event, 'terima')">
                                                    <i class="bi bi-check-circle"></i> Terima
                                                </a>
                                                <a href="process/process_pengumuman.php?id=<?= $peserta['id'] ?>&status=ditolak" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirmStatus(event, 'tolak')">
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
    <script>
async function confirmStatus(e, action) {
    e.preventDefault();
    
    const result = await Swal.fire({
        title: `${action === 'terima' ? 'Terima' : 'Tolak'} Peserta?`,
        text: `Yakin ingin ${action === 'terima' ? 'menerima' : 'menolak'} peserta ini?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: action === 'terima' ? '#28a745' : '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Ya, ${action === 'terima' ? 'Terima' : 'Tolak'}!`,
        cancelButtonText: 'Batal'
    });

    if (result.isConfirmed) {
        window.location.href = e.target.href;
    }
    return false;
}

// Replace existing alerts with SweetAlert
window.addEventListener('load', function() {
    <?php if(isset($_SESSION['success'])): ?>
        Swal.fire({
            title: 'Berhasil!',
            text: '<?= $_SESSION['success'] ?>',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
        });
    <?php unset($_SESSION['success']); endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
        Swal.fire({
            title: 'Error!',
            text: '<?= $_SESSION['error'] ?>',
            icon: 'error'
        });
    <?php unset($_SESSION['error']); endif; ?>
});
</script>
</body>
</html>
