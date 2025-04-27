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
    
    // Base query dengan error handling yang lebih baik
    $query = "SELECT 
                p.id,
                p.nisn,
                p.nama_lengkap,
                p.created_at,
                p.jarak_ke_sekolah,
                jp.nama_jalur, 
                v.status_verifikasi,
                v.catatan,
                DATE_FORMAT(p.created_at, '%d/%m/%Y') as tanggal_daftar
            FROM peserta p 
            LEFT JOIN jalur_pendaftaran jp ON jp.id = p.jalur_id
            LEFT JOIN verifikasi_peserta v ON p.id = v.peserta_id 
            WHERE 1=1";

    $params = array();

    // Remove the filter that excludes verified students
    // Instead, just keep search filter if it exists
    if (!empty($search)) {
        $searchLike = '%' . strtolower($search) . '%';
        $query .= " AND (LOWER(p.nama_lengkap) LIKE :searchName 
                        OR p.nisn LIKE :searchNisn)";
        $params[':searchName'] = $searchLike;
        $params[':searchNisn'] = $searchLike;
    }

    // Perbaikan query ORDER BY yang sama
    $query .= " ORDER BY 
                CASE v.status_verifikasi
                    WHEN 'Verified' THEN 1 
                    WHEN 'Pending' THEN 2
                    WHEN 'rejected' THEN 3
                    ELSE 4 
                END,
                p.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    
    // Bind parameters dengan pengecekan
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    
    if (!$stmt->execute()) {
        throw new PDOException("Failed to execute query: " . implode(" ", $stmt->errorInfo()));
    }
    
    $pending_verifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($pending_verifications === false) {
        throw new PDOException("Failed to fetch results");
    }
    
    // Add search result message
    if (!empty($search)) {
        $count = count($pending_verifications);
        $searchMessage = "Ditemukan {$count} hasil pencarian untuk \"{$search}\"";
    }

} catch (PDOException $e) {
    error_log("Database error in verifikasi.php: " . $e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan saat mengambil data. Silakan coba lagi.";
    $pending_verifications = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Peserta - PPDB</title>
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
                    <h2 class="mb-0">Verifikasi Peserta</h2>
                    <form class="d-flex" role="search" method="GET" style="width: 400px;">
                        <input class="form-control me-2" type="search" name="search" 
                               placeholder="Cari nama atau NISN..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-outline-primary" type="submit">Cari</button>
                        <?php if(!empty($search)): ?>
                            <a href="verifikasi.php" class="btn btn-outline-secondary ms-2">Reset</a>
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
                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?= $_SESSION['error'] ?>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>NISN</th>
                                        <th>Nama</th>
                                        <th>Jalur</th>
                                        <th>Status</th>
                                        <th>Catatan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($pending_verifications as $index => $peserta): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($peserta['nisn']) ?></td>
                                        <td><?= htmlspecialchars($peserta['nama_lengkap']) ?></td>
                                        <td><?= htmlspecialchars($peserta['nama_jalur']) ?></td>
                                        <td>
                                            <?php 
                                            $statusClass = 'bg-warning';
                                            $statusText = 'Pending';
                                            
                                            if ($peserta['status_verifikasi'] === 'Verified') {
                                                $statusClass = 'bg-success';
                                                $statusText = 'Terverifikasi';
                                            } elseif ($peserta['status_verifikasi'] === null) {
                                                $statusClass = 'bg-secondary';
                                                $statusText = 'Belum Diverifikasi';
                                            } elseif ($peserta['status_verifikasi'] === 'rejected') {
                                                $statusClass = 'bg-danger';
                                                $statusText = 'Ditolak';
                                            }
                                            ?>
                                            <span class="badge <?= $statusClass ?>">
                                                <?= $statusText ?>
                                            </span>
                                        </td>
                                        <td><?= !empty($peserta['catatan']) ? 
                                            '<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> ' . 
                                            htmlspecialchars($peserta['catatan']) . '</span>' : 
                                            '-' ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm <?= $peserta['status_verifikasi'] === 'Verified' ? 'btn-warning' : 'btn-primary' ?>" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#verifikasiModal<?= $peserta['id'] ?>">
                                                <i class="bi <?= $peserta['status_verifikasi'] === 'Verified' ? 'bi-pencil-square' : 'bi-check-circle' ?>"></i>
                                                <?= $peserta['status_verifikasi'] === 'Verified' ? 'Edit' : 'Verifikasi' ?>
                                            </button>

                                            <!-- Modal Verifikasi -->
                                            <div class="modal fade" id="verifikasiModal<?= $peserta['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">
                                                                <?= $peserta['status_verifikasi'] === 'Verified' ? 'Edit Verifikasi' : 'Verifikasi Peserta' ?>
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form action="process/verifikasi_process.php" method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="id" value="<?= $peserta['id'] ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">NISN</label>
                                                                    <input type="text" class="form-control" value="<?= htmlspecialchars($peserta['nisn']) ?>" readonly>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Jarak ke Sekolah (KM)</label>
                                                                    <input type="number" step="0.1" class="form-control" name="jarak" 
                                                                           value="<?= htmlspecialchars($peserta['jarak_ke_sekolah'] ?? '') ?>" required>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label">Status Verifikasi</label>
                                                                    <select class="form-select" name="status_verifikasi" id="statusVerifikasi<?= $peserta['id'] ?>" 
                                                                            onchange="togglePengumumanStatus(<?= $peserta['id'] ?>)" required>
                                                                        <option value="Pending" <?= $peserta['status_verifikasi'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                                        <option value="Verified" <?= $peserta['status_verifikasi'] === 'Verified' ? 'selected' : '' ?>>Verified</option>
                                                                        <option value="Rejected" <?= $peserta['status_verifikasi'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                                                    </select>
                                                                </div>

                                                                <div class="mb-3" id="pengumumanStatus<?= $peserta['id'] ?>" style="display: none;">
                                                                    <label class="form-label">Status Penerimaan (Opsional)</label>
                                                                    <select class="form-select" name="status_penerimaan">
                                                                        <option value="">Pilih Status</option>
                                                                        <option value="Diterima">Diterima</option>
                                                                        <option value="Ditolak">Ditolak</option>
                                                                    </select>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label">Catatan</label>
                                                                    <textarea class="form-control" name="catatan" rows="3"><?= htmlspecialchars($peserta['catatan'] ?? '') ?></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                                <button type="submit" class="btn btn-primary">Simpan</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
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
        function togglePengumumanStatus(id) {
            const statusVerifikasi = document.getElementById('statusVerifikasi' + id);
            const pengumumanStatus = document.getElementById('pengumumanStatus' + id);
            
            if (statusVerifikasi.value === 'Verified') {
                pengumumanStatus.style.display = 'block';
            } else {
                pengumumanStatus.style.display = 'none';
            }
        }

        document.querySelectorAll('form[action="process/verifikasi_process.php"]').forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const modal = bootstrap.Modal.getInstance(form.closest('.modal'));

                try {
                    const response = await fetch(this.action, {
                        method: 'POST',
                        body: formData
                    });

                    if (modal) modal.hide();

                    await Swal.fire({
                        title: 'Berhasil!',
                        text: 'Status verifikasi berhasil diperbarui',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });

                    window.location.href = 'verifikasi.php';

                } catch (error) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Terjadi kesalahan saat memproses verifikasi',
                        icon: 'error'
                    });
                }
            });
        });

        // Replace URL parameter check with SweetAlert
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('success')) {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Status verifikasi berhasil diperbarui',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'verifikasi.php';
                });
            } else if (urlParams.has('error')) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Terjadi kesalahan saat memproses verifikasi',
                    icon: 'error'
                }).then(() => {
                    window.location.href = 'verifikasi.php';
                });
            }
        });
    </script>
</body>
</html>
