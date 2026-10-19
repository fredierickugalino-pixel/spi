<?php
require_once __DIR__.'/includes/bootstrap.php';
if (!empty($_SESSION['user_id'])) go('/spi/dashboard.php');
page_start('Sign in');
if (!empty($_SESSION['login_error'])) {
    $loginError=$_SESSION['login_error']; unset($_SESSION['login_error']);
    echo '<dialog class="login-alert" id="login-alert" aria-labelledby="login-alert-title" aria-describedby="login-alert-message"><span class="alert-mark" aria-hidden="true">!</span><h2 id="login-alert-title">Sign-in failed</h2><p id="login-alert-message">'.e($loginError).'</p><button class="submit-button" type="button" autofocus onclick="document.getElementById(\'login-alert\').close()">Try again</button></dialog><script>document.getElementById("login-alert").showModal();</script>';
}
?>
<section class="auth card login-card">
    <header class="login-heading">
        <img src="/spi/logo.png" alt="St. Peregrine Institute logo" class="school-logo">
        <p class="eyebrow">SENIOR HIGH SCHOOL</p>
        <h1>Welcome back</h1>
        <p class="subtitle">Sign in to your school account.</p>
    </header>
    <form method="post" action="/spi/login.php" class="login-form">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <label>Username or email<input name="identity" autocomplete="username" placeholder="Enter your username or email" required></label>
        <label>Password<div class="password-field"><input id="login-password" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required><?=password_toggle('login-password')?></div></label>
        <div class="login-forgot"><a href="/spi/forgot-password.php">Forgot password?</a></div>
        <button class="submit-button">Sign in</button>
    </form>
    <div class="login-register"><span>New to SPI?</span><a href="/spi/register.php">Create a student account</a></div>
</section>
<?php page_end(); ?>
