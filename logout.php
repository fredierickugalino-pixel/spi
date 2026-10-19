<?php
require_once __DIR__.'/includes/bootstrap.php';
if (!empty($_SESSION['user_id'])) audit($conn,(int)$_SESSION['user_id'],'logout','user',(int)$_SESSION['user_id']);
$_SESSION=[]; if(ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']); } session_destroy(); header('Location: /spi/'); exit;
