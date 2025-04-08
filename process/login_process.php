<?php
// Set session configuration
ini_set('session.cookie_lifetime', 0);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_samesite', 'Lax');

session_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/functions.php';

// Debug: Log all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Pastikan tidak ada output sebelum header
ob_start();

// Log device info
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
error_log("Login attempt from device: " . $userAgent);

// Cek metode request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Hanya metode POST yang diizinkan";
    header("Location: ../index.php?show=login");
    exit;
}

// Validasi CSRF Token
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    $_SESSION['error'] = "Token keamanan tidak valid!";
    header("Location: ../index.php?show=login");
    exit;
}

// Validasi CAPTCHA
if (!isset($_POST['captcha']) || !validateCaptcha($_POST['captcha'], 'login')) {
    $_SESSION['error'] = "CAPTCHA tidak valid!";
    header("Location: ../index.php?show=login");
    exit;
}

try {
    // Bersihkan input
    $nisn = trim($_POST['nisn']);
    $password = $_POST['password'];

    // Detailed debug logging
    error_log("Login attempt started");
    error_log("NISN: " . $nisn);
    error_log("Device: " . $userAgent);
    
    // Validasi input
    if (empty($nisn) || empty($password)) {
        throw new Exception("NISN dan password harus diisi!");
    }

    // Query database dengan prepared statement - tanpa status check
    $stmt = $pdo->prepare("SELECT * FROM peserta WHERE nisn = ?");
    $stmt->execute([$nisn]);
    $user = $stmt->fetch();

    // Debug logging
    error_log("Query executed");
    error_log("User data found: " . ($user ? 'Yes' : 'No'));
    if ($user) {
        error_log("User status: " . ($user['status'] ?? 'undefined'));
        error_log("Password in DB: " . substr($user['password'], 0, 10) . '...');
        error_log("Input password verification result: " . (password_verify($password, $user['password']) ? 'Success' : 'Failed'));
    }

    // Verifikasi user
    if (!$user) {
        incrementLoginAttempt();
        throw new Exception("NISN atau password salah!");
    }

    // Verifikasi password
    if (!password_verify($password, $user['password'])) {
        error_log("Password verification failed for NISN: " . $nisn);
        incrementLoginAttempt();
        throw new Exception("NISN atau password salah!");
    }

    error_log("Login successful for NISN: " . $nisn);
    
    // Reset attempt jika berhasil
    unset($_SESSION['login_attempts']);
    unset($_SESSION['login_blocked']);

    // Set session dengan informasi lengkap
    $_SESSION['user'] = [
        'id' => $user['id'],
        'nisn' => $user['nisn'],
        'nama' => $user['nama_lengkap'],
        'login_time' => time(),
        'device' => $userAgent
    ];
    
    error_log("Session set successfully: " . print_r($_SESSION['user'], true));

    // Bersihkan output buffer
    ob_end_clean();

    // Redirect ke dashboard setelah login berhasil
    $_SESSION['success'] = "Login berhasil!";
    header("Location: ../dashboard.php");
    exit();

} catch (Exception $e) {
    ob_end_clean();
    error_log("Login Error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../index.php?show=login");
    exit();
}

function incrementLoginAttempt() {
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    
    if ($_SESSION['login_attempts'] >= 3) {
        $_SESSION['login_blocked'] = time() + 300; // Blokir 5 menit
    }
}
?>