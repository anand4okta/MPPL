<?php
session_start();

header('Content-Type: text/plain');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$type = isset($_GET['type']) && in_array($_GET['type'], ['register', 'login']) ? $_GET['type'] : 'register';

$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = substr(str_shuffle($chars), 0, 6);

$_SESSION['captcha'][$type] = [
    'code' => $code,
    'time' => time(),
    'attempts' => 0,
    'expires' => time() + 90
];

echo $code;
?>