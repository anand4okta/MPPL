<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg bg-white shadow-sm admin-navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center text-primary" href="index.php">
            <img src="https://freeimghost.net/images/2025/04/04/logo_kemenag.png" alt="Logo" height="40" class="me-2">
            <div class="d-flex flex-column">
                <span class="fw-bold">ADMIN PPDB MAN 1 MUSI RAWAS</span>
                <small class="text-muted">Panel Administrator</small>
            </div>
        </a>
        <button class="navbar-toggler border-primary me-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon text-primary"></span>
        </button>
        <div class="collapse navbar-collapse mt-3 mt-lg-0" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                </li>
            </ul>
            <div class="d-flex">
                <a href="../logout.php" class="btn btn-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </div>
</nav>
<style>
    .admin-navbar {
        position: fixed;
        top: 0;
        right: 0;
        left: 0;
        z-index: 1030;
    }
    
    @media (max-width: 767px) {
        .admin-navbar {
            position: relative;
        }
        /* Remove the spacer margin on mobile */
        .admin-navbar + div {
            margin-top: 0 !important;
        }
    }
</style>
<!-- Spacer for desktop only -->
<div class="d-none d-md-block" style="margin-top: 80px;"></div>
