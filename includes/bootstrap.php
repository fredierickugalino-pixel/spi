<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params(['httponly'=>true,'secure'=>$secure,'samesite'=>'Lax']); session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
csrf_check();
if (!empty($_SESSION['must_change_password']) && basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'change-password.php' && basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'logout.php') { header('Location: /spi/change-password.php'); exit; }
