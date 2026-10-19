<?php
require_once __DIR__.'/includes/bootstrap.php';

if(isset($_GET['restart'])) {
    unset($_SESSION['password_reset_verified'],$_SESSION['password_reset_email']);
    go('/spi/reset-password.php');
}

$error='';
$emailValue=trim((string)($_POST['email']??$_SESSION['password_reset_email']??''));
$success=false;
$verified=$_SESSION['password_reset_verified']??null;
if($verified&&(!isset($verified['verified_at'])||time()-(int)$verified['verified_at']>600)) {
    unset($_SESSION['password_reset_verified'],$_SESSION['password_reset_email']);
    $verified=null;
    $error='PIN verification expired. Enter your email and PIN again.';
}
if(!$verified&&$emailValue==='') go('/spi/forgot-password.php');

if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['phase']??'')==='verify-pin') {
    unset($_SESSION['password_reset_verified']);
    $emailValue=trim((string)($_POST['email']??$_SESSION['password_reset_email']??''));
    $pin=trim($_POST['pin']??'');
    $isVerified=false;
    $transactionStarted=false;
    try {
        $conn->begin_transaction(); $transactionStarted=true;
        $q=$conn->prepare('SELECT user_id FROM users WHERE email=? AND account_status="active" LIMIT 1 FOR UPDATE');
        $q->bind_param('s',$emailValue); $q->execute(); $user=$q->get_result()->fetch_assoc();
        if($user&&preg_match('/^[0-9]{4}$/',$pin)) {
            $uid=(int)$user['user_id'];
            $q=$conn->prepare('SELECT reset_id,token_hash,token_salt,attempt_count FROM password_resets WHERE user_id=? AND used_at IS NULL AND expires_at>NOW() ORDER BY created_at DESC LIMIT 1 FOR UPDATE');
            $q->bind_param('i',$uid); $q->execute(); $reset=$q->get_result()->fetch_assoc();
            if($reset) {
                $pinHash=hash('sha256',$reset['token_salt'].'|'.strtolower($emailValue).'|'.$pin);
                if(hash_equals($reset['token_hash'],$pinHash)) {
                    $_SESSION['password_reset_verified']=['user_id'=>$uid,'reset_id'=>(int)$reset['reset_id'],'verified_at'=>time()];
                    $_SESSION['password_reset_email']=$emailValue;
                    $isVerified=true;
                } else {
                    $attempts=(int)$reset['attempt_count']+1; $rid=(int)$reset['reset_id'];
                    if($attempts>=5) $q=$conn->prepare('UPDATE password_resets SET attempt_count=?,used_at=NOW() WHERE reset_id=?');
                    else $q=$conn->prepare('UPDATE password_resets SET attempt_count=? WHERE reset_id=?');
                    $q->bind_param('ii',$attempts,$rid); $q->execute();
                }
            }
        }
        $conn->commit(); $transactionStarted=false;
    } catch(Throwable $exception) {
        if($transactionStarted) { try { $conn->rollback(); } catch(Throwable $ignored) {} }
        error_log('SPI password PIN verification failed: '.$exception->getMessage());
        $error='We could not verify the PIN right now. Please try again later.';
    }
    if($isVerified) go('/spi/reset-password.php');
    if($error==='') $error='That PIN is invalid, expired, or already used. Request a new PIN if needed.';
    unset($_SESSION['password_reset_verified']);
}

if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['phase']??'')==='change-password') {
    $verified=$_SESSION['password_reset_verified']??null;
    $password=$_POST['password']??'';
    $confirm=$_POST['confirm_password']??'';
    if(!$verified||time()-(int)($verified['verified_at']??0)>600) {
        unset($_SESSION['password_reset_verified'],$_SESSION['password_reset_email']);
        $verified=null;
        $error='PIN verification expired. Enter your email and PIN again.';
    } elseif(!password_is_valid($password)||$password!==$confirm) {
        $error='Choose a strong password and make sure both password fields match.';
    } else {
        $transactionStarted=false;
        try {
            $conn->begin_transaction(); $transactionStarted=true;
            $uid=(int)$verified['user_id']; $rid=(int)$verified['reset_id'];
            $q=$conn->prepare('SELECT reset_id FROM password_resets WHERE reset_id=? AND user_id=? AND used_at IS NULL AND expires_at>NOW() AND attempt_count<5 FOR UPDATE');
            $q->bind_param('ii',$rid,$uid); $q->execute();
            if(!$q->get_result()->fetch_assoc()) throw new RuntimeException('PIN expired or unavailable.');
            $newHash=password_hash($password,PASSWORD_DEFAULT);
            $q=$conn->prepare('UPDATE users SET password=?,must_change_password=0 WHERE user_id=? AND account_status="active"'); $q->bind_param('si',$newHash,$uid); $q->execute();
            if($q->affected_rows!==1) throw new RuntimeException('Account unavailable.');
            $q=$conn->prepare('UPDATE password_resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL'); $q->bind_param('i',$uid); $q->execute();
            audit($conn,$uid,'password_reset_completed','user',$uid);
            $conn->commit(); $transactionStarted=false; $success=true;
        } catch(Throwable $exception) {
            if($transactionStarted) { try { $conn->rollback(); } catch(Throwable $ignored) {} }
            error_log('SPI password reset failed: '.$exception->getMessage());
            $error=$exception instanceof RuntimeException?'That PIN is invalid, expired, or already used. Request a new PIN if needed.':'We could not reset your password right now. Please try again later.';
        }
        if($success) {
            unset($_SESSION['password_reset_verified'],$_SESSION['password_reset_email']);
            $_SESSION['password_changed_notice']='Password changed. Sign in with your new password.';
            go('/spi/');
        }
    }
}

$verified=$_SESSION['password_reset_verified']??null;
if($verified&&time()-(int)($verified['verified_at']??0)>600) {
    unset($_SESSION['password_reset_verified'],$_SESSION['password_reset_email']);
    $verified=null;
}
page_start('Set new password');
if($error!=='') echo '<dialog class="login-alert" id="reset-alert" aria-labelledby="reset-alert-title" aria-describedby="reset-alert-message"><span class="alert-mark" aria-hidden="true">!</span><h2 id="reset-alert-title">Password reset</h2><p id="reset-alert-message">'.e($error).'</p><button class="submit-button" type="button" autofocus onclick="document.getElementById(\'reset-alert\').close()">Try again</button></dialog><script>document.getElementById("reset-alert").showModal();</script>';
if($verified) {
    echo '<section class="card auth login-card"><header class="login-heading"><img src="/spi/logo.png" alt="St. Peregrine Institute logo" class="school-logo"><p class="eyebrow">ACCOUNT RECOVERY</p><h1>Choose a new password</h1><p class="subtitle">PIN verified for '.e($_SESSION['password_reset_email']??'your account').'. Choose a new password within ten minutes.</p></header><form method="post" class="login-form"><input type="hidden" name="csrf" value="'.e(csrf_token()).'"><input type="hidden" name="phase" value="change-password"><label>New password<div class="password-field"><input id="reset-new-password" type="password" name="password" minlength="8" maxlength="128" autocomplete="new-password" required>'.password_toggle('reset-new-password').'</div>'.password_meter('reset-new-password').'</label><label>Confirm password<div class="password-field"><input id="reset-confirm-password" type="password" name="confirm_password" minlength="8" maxlength="128" autocomplete="new-password" required>'.password_toggle('reset-confirm-password').'</div></label><button class="submit-button">Save password</button></form><p class="auth-switch"><a href="/spi/reset-password.php?restart=1">Enter a different PIN</a></p></section>';
} else {
    echo '<section class="card auth login-card"><header class="login-heading"><img src="/spi/logo.png" alt="St. Peregrine Institute logo" class="school-logo"><p class="eyebrow">ACCOUNT RECOVERY</p><h1>Verify your PIN</h1><p class="subtitle">Enter the four-digit PIN sent to '.e($emailValue).'. After verification, you can choose a new password.</p></header><form method="post" class="login-form"><input type="hidden" name="csrf" value="'.e(csrf_token()).'"><input type="hidden" name="phase" value="verify-pin"><label>Four-digit PIN<input type="text" name="pin" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{4}" minlength="4" maxlength="4" placeholder="0000" required></label><button class="submit-button">Verify PIN</button></form><p class="auth-switch"><a href="/spi/forgot-password.php">Request a new PIN</a> · <a href="/spi/">Return to sign in</a></p></section>';
}
page_end();
