<?php
session_start();
require_once '../config/db.php';

// Clear admin session
if(isset($_SESSION['admin'])) {
    unset($_SESSION['admin']);
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;