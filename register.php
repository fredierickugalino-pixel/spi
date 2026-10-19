<?php
require_once __DIR__.'/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $lrn=trim($_POST['lrn']??''); $first=trim($_POST['firstname']??''); $middle=trim($_POST['middle_name']??''); $last=trim($_POST['lastname']??'');
    $email=trim($_POST['email']??''); $password=$_POST['password']??''; $confirm=$_POST['confirm_password']??'';
    if(!preg_match('/^[0-9]{12}$/',$lrn)||$first===''||$last===''||strlen($first)>100||strlen($middle)>100||strlen($last)>100||($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))||!password_is_valid($password)||$password!==$confirm) {
        flash('Check your LRN, name, email, and password. '.password_requirement_text().' Passwords must match.','error');
    } else {
        try {
            $conn->begin_transaction(); $username=$email!==''?$email:$lrn; $emailDb=$email!==''?$email:null; $hash=password_hash($password,PASSWORD_DEFAULT); $role='student'; $status='active';
            $q=$conn->prepare('INSERT INTO users(username,email,password,role,account_status) VALUES(?,?,?,?,?)'); $q->bind_param('sssss',$username,$emailDb,$hash,$role,$status); $q->execute(); $uid=$conn->insert_id;
            $rs='pending'; $q=$conn->prepare('INSERT INTO student_account_requests(user_id,submitted_lrn,submitted_firstname,submitted_middle_name,submitted_lastname,request_status) VALUES(?,?,?,?,?,?)'); $q->bind_param('isssss',$uid,$lrn,$first,$middle,$last,$rs); $q->execute();
            audit($conn,$uid,'student_registration_submitted','student_account_request',(int)$conn->insert_id); $conn->commit();
            flash('Your student account is ready. You can sign in now. Access to grades will be available after your identity is matched with an official school record.'); go('/spi/');
        } catch(mysqli_sql_exception $ex) { $conn->rollback(); flash('We could not create this request. The username, email, or account may already exist.','error'); }
    }
}
page_start('Student registration');
?>
<section class="card register-card">
    <header class="register-heading">
        <img src="/spi/logo.png" alt="St. Peregrine Institute logo" class="school-logo">
        <p class="eyebrow">SENIOR HIGH SCHOOL</p>
        <h1>Create a student account</h1>
        <p class="subtitle">Register and sign in right away. Grade access requires matching your details with an official school record.</p>
    </header>
    <div class="verification-note"><span class="verification-icon" aria-hidden="true">i</span><p>Access to grades is enabled only after an administrator verifies your details against official school records.</p></div>
    <form method="post" class="register-form">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <section class="register-section" aria-labelledby="student-details-heading">
            <div class="register-section-heading"><span class="section-number">1</span><div><h2 id="student-details-heading">Student details</h2><p>Use the name shown on your official school record.</p></div></div>
            <div class="register-grid">
                <label class="field-lrn">Learner Reference Number (LRN)<input name="lrn" inputmode="numeric" maxlength="12" placeholder="12-digit LRN" required></label>
                <label>First name<input name="firstname" maxlength="100" autocomplete="given-name" required></label>
                <label>Middle name <span class="optional">Optional</span><input name="middle_name" maxlength="100" autocomplete="additional-name"></label>
                <label>Last name<input name="lastname" maxlength="100" autocomplete="family-name" required></label>
                <label class="field-email">Email address <span class="optional">Optional</span><input type="email" name="email" autocomplete="email" placeholder="you@example.com"></label>
            </div>
        </section>
        <section class="register-section account-section" aria-labelledby="account-details-heading">
            <div class="register-section-heading"><span class="section-number">2</span><div><h2 id="account-details-heading">Secure your account</h2><p>Choose a password you do not use on other sites.</p></div></div>
            <div class="register-grid password-grid">
                <label>Password<div class="password-field"><input id="register-password" type="password" name="password" minlength="8" maxlength="128" autocomplete="new-password" placeholder="Create a password" required><?=password_toggle('register-password')?></div><?=password_meter('register-password')?></label>
                <label>Confirm password<div class="password-field"><input id="register-confirm-password" type="password" name="confirm_password" minlength="8" maxlength="128" autocomplete="new-password" placeholder="Enter it again" required><?=password_toggle('register-confirm-password')?></div></label>
            </div>
        </section>
        <button class="submit-button register-submit">Submit account request</button>
    </form>
    <p class="auth-switch register-return">Already have an account? <a href="/spi/">Sign in</a></p>
</section>
<?php page_end(); ?>
