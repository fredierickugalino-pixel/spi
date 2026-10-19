<?php
declare(strict_types=1);

function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function password_is_valid(string $password): bool {
    return strlen($password) >= 8 && strlen($password) <= 128
        && preg_match('/[a-z]/', $password) === 1
        && preg_match('/[A-Z]/', $password) === 1
        && preg_match('/[^A-Za-z0-9\s]/', $password) === 1;
}
function password_requirement_text(): string { return 'At least 8 characters, including one lowercase letter, one uppercase letter, and one special character.'; }
function password_meter(string $inputId): string {
    $id = e($inputId);
    return '<div class="password-meter" data-password-meter="'.$id.'" role="meter" aria-label="Password strength" aria-valuemin="0" aria-valuemax="4" aria-valuenow="0" aria-valuetext="No password entered"><div class="meter-track"><span class="meter-fill"></span></div><span class="meter-label">Password strength</span></div>';
}
function password_toggle(string $inputId): string {
    $id = e($inputId);
    return '<button type="button" class="password-toggle" data-password-target="'.$id.'" aria-controls="'.$id.'" aria-label="Show password" aria-pressed="false" title="Show password"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle><path class="eye-slash" d="m3 3 18 18"></path></svg></button>';
}
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_check(): void { if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Your form expired. Please go back and try again.'); } }
function flash(string $message, string $type = 'success'): void { $_SESSION['flash'] = [$message, $type]; }
function go(string $path): never { header('Location: ' . $path); exit; }
function audit(mysqli $db, ?int $userId, string $action, string $entity, ?int $id = null, array $details = []): void {
    $json = json_encode($details, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    $st = $db->prepare('INSERT INTO audit_logs(user_id,action,entity_type,entity_id,details) VALUES(?,?,?,?,?)');
    $st->bind_param('issis', $userId, $action, $entity, $id, $json); $st->execute();
}
function require_role(string ...$roles): void { if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', $roles, true)) { http_response_code(403); exit('You are not authorized to view this page.'); } }
function page_start(string $title): void {
    $user = $_SESSION['user_id'] ?? null; $name = $_SESSION['name'] ?? '';
    $studentPage = $user && ($_SESSION['role'] ?? '') === 'student';
    $teacherPage = $user && ($_SESSION['role'] ?? '') === 'teacher';
    $authPage = in_array($title, ['Sign in', 'Student registration', 'Forgot password', 'Set new password', 'Change password'], true) && !$studentPage && !$teacherPage;
    $adminPage = $user && ($_SESSION['role'] ?? '') === 'admin';
    $bodyClass = $authPage ? 'auth-page' : ($adminPage ? 'admin-page' : ($studentPage ? 'student-page' : ($teacherPage ? 'teacher-page' : '')));
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.e($title).' | SPI</title><link rel="stylesheet" href="/spi/assets/css/app.css?v=2"><link rel="stylesheet" href="/spi/assets/css/password-toggle.css?v=2">'.($title==='Student registration'?'<link rel="stylesheet" href="/spi/assets/css/register.css?v=1">':'').(in_array($title,['Sign in','Forgot password','Set new password'],true)?'<link rel="stylesheet" href="/spi/assets/css/login.css?v=1">':'').($adminPage?'<link rel="stylesheet" href="/spi/assets/css/admin.css?v=2"><link rel="stylesheet" href="/spi/assets/css/admin-sections.css?v=1">':'').($studentPage?'<link rel="stylesheet" href="/spi/assets/css/student.css?v=6">':'').($teacherPage?'<link rel="stylesheet" href="/spi/assets/css/teacher.css?v=1">':'').'</head><body'.($bodyClass!==''?' class="'.e($bodyClass).'"':'').'><header class="top"><a href="/spi/" class="brand"><img src="/spi/logo.png" alt="" class="brand-mark"><span>ST. PEREGRINE INSTITUTE <small>Senior High School</small></span></a>';
    if ($user) echo '<div class="user">'.e($name).' <a class="button ghost" href="/spi/logout.php">Log out</a></div>';
    echo '</header><main class="wrap'.($authPage?' auth-layout':'').'">';
    if (!empty($_SESSION['flash'])) { [$m,$t] = $_SESSION['flash']; unset($_SESSION['flash']); echo '<div class="notice '.e($t).'">'.e($m).'</div>'; }
    if ($adminPage) {
        $items = [
            'Dashboard' => '/spi/dashboard.php', 'Students' => '/spi/admin/students.php',
            'Subjects' => '/spi/admin/subjects.php',
            'Teachers' => '/spi/admin/teachers.php', 'Assignments' => '/spi/admin/assignments.php',
            'Grade review' => '/spi/admin/grades.php', 'Audit log' => '/spi/admin/audit-logs.php'
        ];
        $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
        echo '<div class="admin-layout"><aside class="admin-sidebar"><div class="sidebar-caption">ADMINISTRATION</div><nav class="sidebar-nav" aria-label="Administrator navigation">';
        foreach ($items as $label => $url) { $active = basename($url) === $current; echo '<a href="'.e($url).'"'.($active?' class="active" aria-current="page"':'').'><span>'.e($label).'</span></a>'; }
        echo '</nav><div class="sidebar-foot">SPI · Senior High School</div></aside><section class="admin-content">';
    } elseif ($studentPage || $teacherPage) {
        $items = $studentPage ? ['Dashboard' => '/spi/dashboard.php', 'Grades' => '/spi/student/grades.php', 'My Profile' => '/spi/student/change-password.php'] : ['Dashboard' => '/spi/dashboard.php', 'My classes' => '/spi/teacher/classes.php', 'Change password' => '/spi/change-password.php'];
        $sidebarClass = $studentPage ? 'student' : 'teacher';
        $roleLabel = $studentPage ? 'STUDENT MENU' : 'TEACHER MENU';
        $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
        echo '<div class="'.$sidebarClass.'-layout"><aside class="'.$sidebarClass.'-sidebar"><div class="sidebar-caption">'.e($roleLabel).'</div><nav class="sidebar-nav" aria-label="'.($studentPage?'Student':'Teacher').' navigation">';
        foreach ($items as $label => $url) { $active = basename($url) === $current; echo '<a href="'.e($url).'"'.($active?' class="active" aria-current="page"':'').'><span>'.e($label).'</span></a>'; }
        echo '</nav><div class="sidebar-foot">SPI · Senior High School</div></aside><section class="'.$sidebarClass.'-content">';
    } elseif ($user) { $role = $_SESSION['role']; echo '<nav class="nav"><a href="/spi/dashboard.php">Dashboard</a> ';
        if ($role === 'teacher') echo '<a href="/spi/teacher/classes.php">My classes</a><a href="/spi/change-password.php">Change password</a>';
        if ($role === 'student') echo '<a href="/spi/student/grades.php">My grades</a><a href="/spi/student/change-password.php">Change password</a>';
        echo '</nav>'; }
}
function page_end(): void { if (in_array($_SESSION['role'] ?? '', ['admin','student','teacher'], true)) echo '</section></div>'; echo '</main><footer>St. Peregrine Institute · Senior High School</footer><script>document.addEventListener("click",function(event){const button=event.target.closest("[data-password-target]");if(!button)return;const input=document.getElementById(button.dataset.passwordTarget);if(!input)return;const show=input.type==="password";input.type=show?"text":"password";button.setAttribute("aria-label",show?"Hide password":"Show password");button.setAttribute("title",show?"Hide password":"Show password");button.setAttribute("aria-pressed",show?"true":"false");});document.querySelectorAll("[data-password-meter]").forEach(function(meter){const input=document.getElementById(meter.dataset.passwordMeter);if(!input)return;const fill=meter.querySelector(".meter-fill");const label=meter.querySelector(".meter-label");function update(){const value=input.value;const checks=[value.length>=8,/[a-z]/.test(value),/[A-Z]/.test(value),/[^A-Za-z0-9\\s]/.test(value)];const score=checks.filter(Boolean).length;const empty=value.length===0;meter.setAttribute("aria-valuenow",String(score));meter.setAttribute("aria-valuetext",empty?"No password entered":score===4?"Strong":"Weak");fill.style.width=(score*25)+"%";meter.classList.toggle("is-weak",!empty&&score<4);meter.classList.toggle("is-strong",!empty&&score===4);label.textContent=empty?"Password strength":score===4?"Strong":"Weak";}input.addEventListener("input",update);update();});</script></body></html>'; }
