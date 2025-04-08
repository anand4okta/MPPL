<?php
$current_page = basename($_SERVER['PHP_SELF']);
$menu_items = [
    'index.php' => ['icon' => 'bi-speedometer2', 'text' => 'Dashboard'],
    'peserta.php' => ['icon' => 'bi-people', 'text' => 'Data Peserta'],
    'verifikasi.php' => ['icon' => 'bi-check-circle', 'text' => 'Verifikasi'],
    'pengumuman.php' => ['icon' => 'bi-megaphone', 'text' => 'Pengumuman'],
    'pengaturan.php' => ['icon' => 'bi-gear', 'text' => 'Pengaturan'],
    'export_data.php' => ['icon' => 'bi-arrow-left-right', 'text' => 'Export Data']
];
?>
<div class="sidebar bg-light p-3">
    <div class="list-group">
        <a href="index.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="peserta.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'peserta.php' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Data Peserta
        </a>
        <a href="verifikasi.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'verifikasi.php' ? 'active' : '' ?>">
            <i class="bi bi-check-circle"></i> Verifikasi
        </a>
        <a href="pengumuman.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'pengumuman.php' ? 'active' : '' ?>">
            <i class="bi bi-megaphone"></i> Pengumuman
        </a>
        <a href="pengaturan.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'pengaturan.php' ? 'active' : '' ?>">
            <i class="bi bi-gear"></i> Pengaturan
        </a>
        <a href="export_data.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'export_data.php' ? 'active' : '' ?>">
            <i class="bi bi-arrow-left-right"></i> Export Data
        </a>
        <a href="logout.php" class="list-group-item list-group-item-action text-danger">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>
