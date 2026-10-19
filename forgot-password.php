<?php
require_once __DIR__.'/includes/bootstrap.php';
if($_SERVER['REQUEST_METHOD']==='POST') {
    $email=trim($_POST['email']??'');
    if(filter_var($email,FILTER_VALIDATE_EMAIL)) {
        $q=$conn->prepare('SELECT user_id FROM users WHERE email=? AND account_status="active" LIMIT 1'); $q->bind_param('s',$email); $q->execute();
        if($u=$q->get_result()->fetch_assoc()) {
            $token=bin2hex(random_bytes(32)); $hash=hash('sha256',$token); $expires=date('Y-m-d H:i:s',time()+3600); $id=(int)$u['user_id'];
            $q=$conn->prepare('INSERT INTO password_resets(user_id,token_hash,expires_at) VALUES(?,?,?)'); $q->bind_param('iss',$id,$hash,$expires); $q->execute();
            $url='http'.(!empty($_SERVER['HTTPS'])?'s':'').'://'.$_SERVER['HTTP_HOST'].'/spi/reset-password.php?token='.$token;
            @mail($email,'SPI password reset','Use this link within one hour to reset your SPI password: '.$url); audit($conn,$id,'password_reset_requested','user',$id);
        }
    }
    flash('If an active account uses that email address, a reset link has been sent.'); go('/spi/forgot-password.php');
}
page_start('Forgot password');
echo '<section class="auth card login-card"><header class="login-heading"><img src="/spi/logo.png" alt="St. Peregrine Institute logo" class="school-logo"><p class="eyebrow">ACCOUNT RECOVERY</p><h1>Reset your password</h1><p class="subtitle">Enter the email attached to your account. A one-time reset link expires after one hour.</p></header><form method="post" class="login-form"><input type="hidden" name="csrf" value="'.e(csrf_token()).'"><label>Email address<input type="email" name="email" autocomplete="email" placeholder="you@example.com" required></label><button class="submit-button">Send reset link</button></form><p class="auth-switch">Remembered your password? <a href="/spi/">Return to sign in</a></p></section>';
page_end();
