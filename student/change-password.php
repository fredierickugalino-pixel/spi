<?php
require_once __DIR__.'/../includes/bootstrap.php'; require_role('student');
if($_SERVER['REQUEST_METHOD']==='POST') {
    $old=$_POST['current_password']??''; $new=$_POST['password']??'';
    $q=$conn->prepare('SELECT password FROM users WHERE user_id=?'); $q->bind_param('i',$_SESSION['user_id']); $q->execute(); $hash=$q->get_result()->fetch_column();
    if(!password_verify($old,$hash)||!password_is_valid($new)||$new!==($_POST['confirm_password']??'')) flash('Current password may be incorrect, the new passwords may not match, or the new password is weak.','error');
    else { $next=password_hash($new,PASSWORD_DEFAULT); $q=$conn->prepare('UPDATE users SET password=? WHERE user_id=?'); $q->bind_param('si',$next,$_SESSION['user_id']); $q->execute(); audit($conn,$_SESSION['user_id'],'password_changed','user',$_SESSION['user_id']); flash('Password changed.'); go('/spi/student/change-password.php'); }
}
page_start('Change password');
echo '<section class="card auth"><h1>Change password</h1><form method="post"><input type="hidden" name="csrf" value="'.e(csrf_token()).'"><label>Current password<div class="password-field"><input id="student-current-password" type="password" name="current_password" autocomplete="current-password" required>'.password_toggle('student-current-password').'</div></label><label>New password<div class="password-field"><input id="student-new-password" type="password" name="password" minlength="8" maxlength="128" autocomplete="new-password" required>'.password_toggle('student-new-password').'</div>'.password_meter('student-new-password').'</label><label>Confirm new password<div class="password-field"><input id="student-confirm-password" type="password" name="confirm_password" minlength="8" maxlength="128" autocomplete="new-password" required>'.password_toggle('student-confirm-password').'</div></label><button class="submit-button">Change password</button></form></section>';
page_end();
