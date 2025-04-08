<?php
require_once __DIR__.'/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit;
}

try {
    $username = trim(htmlspecialchars($_POST['username']));
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        throw new Exception("Username dan password harus diisi!");
    }

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || $password !== $admin['password']) {
        // Untuk menghindari timing attacks, gunakan sleep
        sleep(1);
        throw new Exception("Username atau password salah!");
    }

    // Set session dengan data admin
    $_SESSION['admin'] = [
        'id' => $admin['id'],
        'username' => $admin['username'],
        'last_login' => date('Y-m-d H:i:s')
    ];

    header("Location: ../index.php");
    exit;

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../login.php");
    exit;
}