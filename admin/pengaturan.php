<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/admin_auth.php';

if(empty($_SESSION['admin'])) {
    $_SESSION['error'] = "Silakan login sebagai admin terlebih dahulu!";
    header("Location: login.php");
    exit;
}

// Fetch jalur pendaftaran
$stmt = $pdo->query("SELECT * FROM jalur_pendaftaran ORDER BY nama_jalur");
$jalur_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - PPDB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'partials/admin_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3">
                <div style="position: sticky; top: 80px;">
                    <?php include 'partials/admin_sidebar.php'; ?>
                </div>
            </div>
            <div class="col-md-9">
                <h2 class="mb-4" style="margin-top:40px">Pengaturan Sistem</h2>
                
                <!-- Pengaturan Tanggal Jalur Pendaftaran -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Pengaturan Tanggal Jalur Pendaftaran</h5>
                    </div>
                    <div class="card-body">
                        <form action="process/update_periode.php" method="POST">
                            <?php foreach($jalur_list as $jalur): ?>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label"><?= htmlspecialchars($jalur['nama_jalur']) ?></label>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tanggal Buka</label>
                                    <input type="date" class="form-control" 
                                           name="tanggal_buka[<?= $jalur['id'] ?>]" 
                                           value="<?= htmlspecialchars($jalur['tanggal_buka'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tanggal Tutup</label>
                                    <input type="date" class="form-control" 
                                           name="tanggal_tutup[<?= $jalur['id'] ?>]" 
                                           value="<?= htmlspecialchars($jalur['tanggal_tutup'] ?? '') ?>">
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <button type="submit" class="btn btn-primary">Simpan Pengaturan Jalur</button>
                        </form>
                    </div>
                </div>

                <!-- Pengaturan Kuota per Jalur -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Pengaturan Kuota per Jalur</h5>
                    </div>
                    <div class="card-body">
                        <form action="process/update_kuota.php" method="POST">
                            <?php foreach($jalur_list as $jalur): ?>
                            <div class="mb-3">
                                <label class="form-label"><?= htmlspecialchars($jalur['nama_jalur']) ?></label>
                                <input type="number" class="form-control" 
                                       name="kuota[<?= $jalur['id'] ?>]" 
                                       value="<?= htmlspecialchars($jalur['kuota'] ?? '') ?>"
                                       min="0">
                            </div>
                            <?php endforeach; ?>
                            <button type="submit" class="btn btn-primary">Simpan Pengaturan Kuota</button>
                        </form>
                    </div>
                </div>

                <!-- Pengaturan Tanggal Pengumuman -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Pengaturan Tanggal Pengumuman per Jalur</h5>
                    </div>
                    <div class="card-body">
                        <form action="process/update_pengumuman.php" method="POST">
                            <?php foreach($jalur_list as $jalur): ?>
                            <div class="mb-3">
                                <label class="form-label"><?= htmlspecialchars($jalur['nama_jalur']) ?></label>
                                <input type="date" class="form-control" 
                                       name="tanggal_pengumuman[<?= $jalur['id'] ?>]" 
                                       value="<?= htmlspecialchars($jalur['tanggal_pengumuman'] ?? '') ?>">
                            </div>
                            <?php endforeach; ?>
                            <button type="submit" class="btn btn-primary">Simpan Tanggal Pengumuman</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>