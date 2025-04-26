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

    // Base query
    $query = "SELECT p.*, jp.nama_jalur, v.status_verifikasi 
        FROM peserta p 
        LEFT JOIN jalur_pendaftaran jp ON jp.id = p.jalur_id
        LEFT JOIN verifikasi_peserta v ON p.id = v.peserta_id";

    $params = array();
    if (!empty($search)) {
        // Handle both name and NISN search
        $searchLike = '%' . strtolower($search) . '%';
        $query .= " WHERE LOWER(p.nama_lengkap) LIKE :searchName OR p.nisn LIKE :searchNisn";
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

    // Cek nama yang mirip
    $similar_names = [];
    $name_count = [];
    $checked_pairs = []; // Untuk mencegah pengecekan ganda

    foreach ($peserta_list as $peserta) {
        $nama = strtolower($peserta['nama_lengkap']);
        $name_count[$nama] = isset($name_count[$nama]) ? $name_count[$nama] + 1 : 1;
        
        // Cek kemiripan dengan nama lain
        foreach ($peserta_list as $other) {
            if ($peserta['id'] !== $other['id']) {
                $other_nama = strtolower($other['nama_lengkap']);
                
                // Buat unique pair key untuk mencegah pengecekan ganda
                $pair_key = $nama < $other_nama ? 
                           $nama . '|||' . $other_nama : 
                           $other_nama . '|||' . $nama;
                
                if (!isset($checked_pairs[$pair_key])) {
                    similar_text($nama, $other_nama, $percent);
                    if ($percent > 80) {
                        $similar_names[] = [
                            'nama1' => ucwords($nama),
                            'nama2' => ucwords($other_nama)
                        ];
                    }
                    $checked_pairs[$pair_key] = true;
                }
            }
        }
    }

    // Siapkan pesan peringatan untuk nama yang mirip
    $warning_messages = [];
    foreach ($similar_names as $pair) {
        $warning_messages[] = "Nama yang mirip terdeteksi: {$pair['nama1']} dengan {$pair['nama2']}";
    }

    // Pesan untuk nama yang sama persis
    foreach ($name_count as $name => $count) {
        if ($count > 1) {
            $warning_messages[] = "Terdapat " . $count . " peserta dengan nama yang sama: " . ucwords($name);
        }
    }
    
    // Add search result message
    if (!empty($search)) {
        $count = count($peserta_list);
        $searchMessage = "Ditemukan {$count} hasil pencarian untuk \"{$search}\"";
    }

} catch (PDOException $e) {
    error_log("Database error in peserta.php: " . $e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan saat mencari data. Error: " . $e->getMessage();
    $peserta_list = [];
}

// Debug information for development
if (isset($_SESSION['error'])) {
    error_log("Search error - Query: " . $query);
    error_log("Search error - Params: " . print_r($params, true));
}
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">Data Peserta</h2>
                    <form class="d-flex" role="search" method="GET" style="width: 400px;">
                        <input class="form-control me-2" type="search" name="search" 
                               placeholder="Cari nama atau NISN..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-outline-primary" type="submit">Cari</button>
                        <?php if(!empty($search)): ?>
                            <a href="peserta.php" class="btn btn-outline-secondary ms-2">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if(!empty($search)): ?>
                    <div class="alert alert-info">
                        <?= $searchMessage ?>
                    </div>
                <?php endif; ?>

                <?php if(!empty($warning_messages)): ?>
                    <div class="alert alert-warning">
                        <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Perhatian!</h6>
                        <ul class="mb-0">
                            <?php foreach($warning_messages as $message): ?>
                                <li><?= $message ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-body">
                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= $_SESSION['error']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                <?php unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if(empty($peserta_list)): ?>
                            <div class="alert alert-warning">
                                <?= empty($search) ? 'Tidak ada data peserta.' : 'Tidak ada hasil pencarian untuk "'.htmlspecialchars($search).'"' ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No</th>
                                            <th>NISN</th>
                                            <th>Nama</th>
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
                                                <a href="profile_siswa.php?id=<?= $peserta['id'] ?>" class="btn btn-sm btn-info mb-2 mb-md-0">
                                                    <i class="bi bi-eye"></i> Lihat Detail
                                                </a>
                                                <form action="process/delete_peserta.php" method="POST" class="d-inline-block" 
                                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus peserta ini?');">
                                                    <input type="hidden" name="peserta_id" value="<?= $peserta['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i> Hapus
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
