<?php
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

try {
    // Get peserta data with prestasi
    $stmt = $pdo->prepare("
        SELECT p.*, j.nama_jalur 
        FROM peserta p 
        LEFT JOIN jalur_pendaftaran j ON p.jalur_id = j.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $peserta = $stmt->fetch();

    // Get prestasi
    $stmt = $pdo->prepare("SELECT * FROM prestasi WHERE peserta_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_GET['id']]);
    $prestasi = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Database Error: ".$e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan sistem";
    header("Location: peserta.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil - <?= htmlspecialchars($peserta['nama_lengkap']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../partials/admin_header.php'; ?>
    
    <div class="container-fluid my-4">
        <div class="row">
            <div class="col-md-3">
                <div style="position: sticky; top: 80px;">
                    <?php include '../partials/admin_sidebar.php'; ?>
                </div>
                <div class="card shadow-sm mb-4 mt-4">
                    <div class="card-body text-center">
                        <?php 
                        $folder_name = str_replace(' ', '_', strtolower($peserta['nama_lengkap']));
                        $photo_path = "../File/{$folder_name}/{$peserta['file_photo']}";
                        
                        if(!empty($peserta['file_photo']) && file_exists($photo_path)): ?>
                            <img src="<?= htmlspecialchars($photo_path) ?>" 
                                 class="img-thumbnail mb-3" 
                                 style="max-width: 200px;" 
                                 alt="Foto Peserta"
                                 onerror="this.src='../assets/img/default-profile.png';">
                        <?php else: ?>
                            <i class="bi bi-person-circle text-secondary mb-2" style="font-size: 150px;"></i>
                            <p class="text-muted small">Belum upload foto</p>
                        <?php endif; ?>
                        <h5><?= htmlspecialchars($peserta['nama_lengkap']) ?></h5>
                        <p class="text-muted mb-0">NISN: <?= htmlspecialchars($peserta['nisn']) ?></p>
                        <p class="text-muted mb-0"><?= htmlspecialchars($peserta['nama_jalur']) ?></p>
                        <?php 
                        $stmt = $pdo->prepare("SELECT status_verifikasi FROM verifikasi_peserta WHERE peserta_id = ?");
                        $stmt->execute([$_GET['id']]);
                        $verifikasi = $stmt->fetch();
                        $status = $verifikasi ? $verifikasi['status_verifikasi'] : 'Belum Diverifikasi';
                        if ($status === 'rejected') {
                            echo "<p class='text-danger mb-0'>Status: Ditolak - Silakan periksa kembali kelengkapan data anda</p>";
                        } else {
                            echo "<p class='text-muted mb-0'>Status: " . htmlspecialchars($status) . "</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <!-- Section 1: Biodata Siswa -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-person"></i> Biodata Siswa</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table">
                                    <tr>
                                        <td width="150">NIK</td>
                                        <td>: <?= htmlspecialchars($peserta['nik']) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Tempat Lahir</td>
                                        <td>: <?= htmlspecialchars($peserta['tempat_lahir']) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Tanggal Lahir</td>
                                        <td>: <?= htmlspecialchars($peserta['tanggal_lahir']) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Agama</td>
                                        <td>: <?= htmlspecialchars($peserta['agama_siswa']) ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table">
                                    <tr>
                                        <td width="150">Asal Sekolah</td>
                                        <td>: <?= htmlspecialchars($peserta['asal_sekolah']) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Tahun Lulus</td>
                                        <td>: <?= htmlspecialchars($peserta['tahun_lulus']) ?></td>
                                    </tr>
                                    <tr>
                                        <td>No. WhatsApp</td>
                                        <td>: <?= htmlspecialchars($peserta['no_wa_siswa']) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Jenis Kelamin</td>
                                        <td>: <?= htmlspecialchars($peserta['jenis_kelamin']) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <h6 class="border-bottom pb-2">Alamat Siswa</h6>
                        <p>
                            <?= htmlspecialchars($peserta['alamat_siswa_jalan']) ?><br>
                            RT <?= htmlspecialchars($peserta['alamat_siswa_rt']) ?> / 
                            RW <?= htmlspecialchars($peserta['alamat_siswa_rw']) ?><br>
                            Kel. <?= htmlspecialchars($peserta['alamat_siswa_kelurahan']) ?>,
                            Kec. <?= htmlspecialchars($peserta['alamat_siswa_kecamatan']) ?><br>
                            <?= htmlspecialchars($peserta['alamat_siswa_kota']) ?>
                        </p>
                    </div>
                </div>

                <!-- Section 2: Biodata Orangtua -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-people"></i> Biodata Orangtua</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div>
                                <table class="table">
                                    <tr>
                                        <td width="150">No. KK</td>
                                        <td>: <?= htmlspecialchars($peserta['no_kk']) ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Data Ayah</h6>
                                <table class="table">
                                    <tr>
                                        <td width="150">Nama</td>
                                        <td>: <?= htmlspecialchars($peserta['nama_ayah']) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Pekerjaan</td>
                                        <td>: <?= htmlspecialchars($peserta['pekerjaan_ayah']) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Agama</td>
                                        <td>: <?= htmlspecialchars($peserta['agama_ayah']) ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Data Ibu</h6>
                                <table class="table">
                                    <tr>
                                        <td width="150">Nama</td>
                                        <td>: <?= htmlspecialchars($peserta['nama_ibu']) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Pekerjaan</td>
                                        <td>: <?= htmlspecialchars($peserta['pekerjaan_ibu']) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Agama</td>
                                        <td>: <?= htmlspecialchars($peserta['agama_ibu']) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label>No. Telepon Orangtua:</label>
                            <p class="mb-0"><?= htmlspecialchars($peserta['no_telp_ortu']) ?></p>
                        </div>

                        <h6 class="border-bottom pb-2">Alamat Orangtua</h6>
                        <p>
                            <?= htmlspecialchars($peserta['alamat_ortu_jalan']) ?><br>
                            RT <?= htmlspecialchars($peserta['alamat_ortu_rt']) ?> / 
                            RW <?= htmlspecialchars($peserta['alamat_ortu_rw']) ?><br>
                            Kel. <?= htmlspecialchars($peserta['alamat_ortu_kelurahan']) ?>,
                            Kec. <?= htmlspecialchars($peserta['alamat_ortu_kecamatan']) ?><br>
                            <?= htmlspecialchars($peserta['alamat_ortu_kota']) ?>
                        </p>
                    </div>
                </div>

                <!-- Section 3: Lain-lain -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Lain-lain</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Program Keahlian yang Dituju</h6>
                                <p><?= htmlspecialchars($peserta['program_keahlian']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Jarak ke Sekolah</h6>
                                <p><?= htmlspecialchars($peserta['jarak_ke_sekolah'] ?? '-') ?> km</p>
                            </div>
                        </div>

                        <h6 class="border-bottom pb-2">Prestasi</h6>
                        <?php if(empty($prestasi)): ?>
                            <p class="text-muted">Belum ada prestasi yang ditambahkan</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Bidang</th>
                                            <th>Peringkat</th>
                                            <th>Tingkat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($prestasi as $p): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($p['bidang_prestasi']) ?></td>
                                                <td><?= htmlspecialchars($p['peringkat']) ?></td>
                                                <td><?= htmlspecialchars($p['tingkat']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <h6 class="border-bottom pb-2 mt-4">Dokumen</h6>
                        <div class="row mt-4">
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h6>Kartu Keluarga</h6>
                                        <?php if(!empty($peserta['file_kk']) && file_exists("../File/{$folder_name}/{$peserta['file_kk']}")): ?>
                                            <p class="mb-2"><small class="text-muted"><?= htmlspecialchars($peserta['file_kk']) ?></small></p>
                                            <a href="download_admin.php?type=kk&id=<?= $peserta['id'] ?>" 
                                               class="btn btn-sm btn-primary" target="_blank">
                                                <i class="bi bi-download"></i> Download KK
                                            </a>
                                        <?php else: ?>
                                            <p class="text-muted mb-0">Belum diunggah</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h6>Akte Kelahiran</h6>
                                        <?php if(!empty($peserta['file_akte']) && file_exists("../File/{$folder_name}/{$peserta['file_akte']}")): ?>
                                            <p class="mb-2"><small class="text-muted"><?= htmlspecialchars($peserta['file_akte']) ?></small></p>
                                            <a href="download_admin.php?type=akte&id=<?= $peserta['id'] ?>" 
                                               class="btn btn-sm btn-primary" target="_blank">
                                                <i class="bi bi-download"></i> Download Akte
                                            </a>
                                        <?php else: ?>
                                            <p class="text-muted mb-0">Belum diunggah</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h6>Pas Foto</h6>
                                        <?php if(!empty($peserta['file_photo']) && file_exists("../File/{$folder_name}/{$peserta['file_photo']}")): ?>
                                            <p class="mb-2"><small class="text-muted"><?= htmlspecialchars($peserta['file_photo']) ?></small></p>
                                            <a href="download_admin.php?type=photo&id=<?= $peserta['id'] ?>" 
                                               class="btn btn-sm btn-primary" target="_blank">
                                                <i class="bi bi-download"></i> Download Foto
                                            </a>
                                        <?php else: ?>
                                            <p class="text-muted mb-0">Belum diunggah</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add Raport Downloads Section -->
                        <h6 class="border-bottom pb-2 mt-4">Raport</h6>
                        <div class="row mt-3">
                            <?php for($i = 1; $i <= 5; $i++): 
                                $field_name = "file_raport_" . $i;
                            ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6>Semester <?= $i ?></h6>
                                            <?php if(!empty($peserta[$field_name]) && file_exists("../File/{$folder_name}/{$peserta[$field_name]}")): ?>
                                                <p class="mb-2"><small class="text-muted"><?= htmlspecialchars($peserta[$field_name]) ?></small></p>
                                                <a href="download_admin.php?type=raport&sem=<?= $i ?>&id=<?= $peserta['id'] ?>" 
                                                   class="btn btn-sm btn-primary" target="_blank">
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                            <?php else: ?>
                                                <p class="text-muted mb-0">Belum diunggah</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <a href="peserta.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
