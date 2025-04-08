<?php
require_once __DIR__.'/config/db.php';

// Remove remember_me token if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// Clear remember token from database if user is logged in
if (isset($_SESSION['user']['id'])) {
    $stmt = $pdo->prepare("UPDATE peserta SET remember_token = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
}

// Clear all session data
$_SESSION = array();

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// Destroy the session
session_destroy();

// Redirect to index
header("Location: index.php");
exit;