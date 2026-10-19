<?php
require_once __DIR__.'/includes/bootstrap.php';
$token=$_GET['token']??$_POST['token']??''; $valid=false; $reset=null;
if(preg_match('/^[a-f0-9]{64}$/',$token)) { $hash=hash('sha256',$token); $q=$conn->prepare('SELECT reset_id,user_id FROM password_resets WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW() LIMIT 1'); $q->bind_param('s',$hash); $q->execute(); $reset=$q->get_result()->fetch_assoc(); $valid=(bool)$reset; }
if($_SERVER['REQUEST_METHOD']==='POST') {
    $new=$_POST['password']??'';
    if(!$valid||!password_is_valid($new)||$new!==($_POST['confirm_password']??'')) flash('The reset link is invalid or expired, the passwords do not match, or the new password is weak.','error');
    else { $conn->begin_transaction(); $pass=password_hash($new,PASSWORD_DEFAULT); $uid=(int)$reset['user_id']; $rid=(int)$reset['reset_id']; $q=$conn->prepare('UPDATE users SET password=?,must_change_password=0 WHERE user_id=?'); $q->bind_param('si',$pass,$uid); $q->execute(); $q=$conn->prepare('UPDATE password_resets SET used_at=NOW() WHERE reset_id=?'); $q->bind_param('i',$rid); $q->execute(); audit($conn,$uid,'password_reset_completed','user',$uid); $conn->commit(); flash('Password changed. Sign in with your new password.'); go('/spi/'); }
}
page_start('Set new password');
if(!$valid) echo '<section class="card"><h1>Reset link unavailable</h1><p>This link is invalid, expired, or has already been used.</p></section>';
else echo '<section class="card auth"><h1>Choose a new password</h1><form method="post"><input type="hidden" name="csrf" value="'.e(csrf_token()).'"><input type="hidden" name="token" value="'.e($token).'">'.'<label>New password<div class="password-field"><input id="reset-new-password" type="password" name="password" minlength="8" maxlength="128" autocomplete="new-password" required>'.password_toggle('reset-new-password').'</div>'.password_meter('reset-new-password').'</label><label>Confirm password<div class="password-field"><input id="reset-confirm-password" type="password" name="confirm_password" minlength="8" maxlength="128" autocomplete="new-password" required>'.password_toggle('reset-confirm-password').'</div></label><button class="submit-button">Save password</button></form></section>';
page_end();
