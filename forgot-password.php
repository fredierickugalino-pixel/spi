<?php
require_once __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/includes/mailer.php';
$mailConfigured=password_reset_mail_is_configured();
if($_SERVER['REQUEST_METHOD']==='POST') {
    $email=trim($_POST['email']??'');
    if(filter_var($email,FILTER_VALIDATE_EMAIL)) {
        $q=$conn->prepare('SELECT user_id FROM users WHERE email=? AND account_status="active" LIMIT 1'); $q->bind_param('s',$email); $q->execute();
        if($u=$q->get_result()->fetch_assoc()) {
            $pin=str_pad((string)random_int(0,9999),4,'0',STR_PAD_LEFT); $salt=bin2hex(random_bytes(16)); $hash=hash('sha256',$salt.'|'.strtolower($email).'|'.$pin); $id=(int)$u['user_id'];
            $q=$conn->prepare('SELECT reset_id FROM password_resets WHERE user_id=? AND created_at>DATE_SUB(NOW(),INTERVAL 30 MINUTE) AND used_at IS NULL AND expires_at>NOW() AND attempt_count<5 ORDER BY created_at DESC LIMIT 1'); $q->bind_param('i',$id); $q->execute(); $recent=$q->get_result()->fetch_assoc();
            if(!$recent) {
                $q=$conn->prepare('UPDATE password_resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL'); $q->bind_param('i',$id); $q->execute();
                $q=$conn->prepare('INSERT INTO password_resets(user_id,token_hash,token_salt,expires_at) VALUES(?,?,?,DATE_ADD(NOW(),INTERVAL 1 HOUR))'); $q->bind_param('iss',$id,$hash,$salt); $q->execute();
                try { send_password_reset_pin_email($email,$pin); }
            catch(Throwable $mailError) { error_log('SPI password reset email failed: '.$mailError->getMessage()); $q=$conn->prepare('DELETE FROM password_resets WHERE token_hash=?'); $q->bind_param('s',$hash); $q->execute(); }
                audit($conn,$id,'password_reset_requested','user',$id);
            }
        }
    }
    $_SESSION['password_reset_email']=filter_var($email,FILTER_VALIDATE_EMAIL)?$email:'';
    $_SESSION['forgot_password_notice']=$mailConfigured?'If an active account uses that email address, a reset PIN has been sent.':'Email delivery is not configured. Ask the administrator to set up SMTP before requesting a reset PIN.'; go('/spi/forgot-password.php');
}
page_start('Forgot password');
if(!empty($_SESSION['forgot_password_notice'])) { $notice=$_SESSION['forgot_password_notice']; unset($_SESSION['forgot_password_notice']); echo '<dialog class="login-alert" id="forgot-password-alert" aria-labelledby="forgot-password-alert-title" aria-describedby="forgot-password-alert-message"><span class="alert-mark" aria-hidden="true">✓</span><h2 id="forgot-password-alert-title">Check your email</h2><p id="forgot-password-alert-message">'.e($notice).'</p><button class="submit-button" type="button" autofocus onclick="window.location.href=\'/spi/reset-password.php\'">Continue</button></dialog><script>document.getElementById("forgot-password-alert").showModal();</script>'; }
echo '<section class="auth card login-card"><header class="login-heading"><img src="/spi/logo.png" alt="St. Peregrine Institute logo" class="school-logo"><p class="eyebrow">ACCOUNT RECOVERY</p><h1>Reset your password</h1><p class="subtitle">Enter the email attached to your account. We’ll send a four-digit PIN that expires after one hour.</p></header><form method="post" class="login-form"><input type="hidden" name="csrf" value="'.e(csrf_token()).'"><label>Email address<input type="email" name="email" autocomplete="email" placeholder="you@example.com" required></label><button class="submit-button">Send reset PIN</button></form><p class="auth-switch">Remembered your password? <a href="/spi/">Return to sign in</a></p></section>';
page_end();
