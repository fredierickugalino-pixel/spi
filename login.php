<?php
require_once __DIR__.'/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') go('/spi/');
$identity = trim($_POST['identity'] ?? ''); $password = $_POST['password'] ?? '';
$_SESSION['login_tries'] = $_SESSION['login_tries'] ?? 0;
if ($_SESSION['login_tries'] >= 8) { $_SESSION['login_error']='Too many sign-in attempts. Please wait a moment, then try again.'; go('/spi/'); }
$st=$conn->prepare('SELECT user_id,username,email,password,role,account_status,must_change_password FROM users WHERE username=? OR email=? LIMIT 1');
$st->bind_param('ss',$identity,$identity); $st->execute(); $u=$st->get_result()->fetch_assoc();
if (!$u || $u['account_status'] !== 'active' || !password_verify($password,$u['password'])) { $_SESSION['login_tries']++; usleep(250000); $_SESSION['login_error']='We couldn’t sign you in. Check your username and password, then try again.'; go('/spi/'); }
session_regenerate_id(true); $_SESSION['login_tries']=0; $_SESSION['user_id']=(int)$u['user_id']; $_SESSION['role']=$u['role']; $_SESSION['name']=$u['username'];
$_SESSION['must_change_password']=(int)$u['must_change_password']===1;
if ($u['role']==='student') { $q=$conn->prepare('SELECT firstname,middle_name,lastname FROM students s JOIN student_account_requests r ON r.student_id=s.student_id WHERE r.user_id=? AND r.request_status="approved" LIMIT 1'); $q->bind_param('i',$_SESSION['user_id']); $q->execute(); if($n=$q->get_result()->fetch_assoc()) $_SESSION['name']=trim($n['firstname'].' '.$n['middle_name'].' '.$n['lastname']); }
audit($conn,$_SESSION['user_id'],'login','user',$_SESSION['user_id']);
if ($_SESSION['must_change_password']) go('/spi/change-password.php');
if ($u['role']==='student') { $q=$conn->prepare('SELECT request_status FROM student_account_requests WHERE user_id=? ORDER BY request_id DESC LIMIT 1'); $q->bind_param('i',$_SESSION['user_id']);$q->execute();$status=$q->get_result()->fetch_column(); if($status!=='approved') go('/spi/dashboard.php'); }
go('/spi/dashboard.php');
