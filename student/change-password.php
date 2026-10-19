<?php
require_once __DIR__.'/../includes/bootstrap.php';
require_role('student');

$uid=(int)$_SESSION['user_id'];
$q=$conn->prepare('SELECT username,email FROM users WHERE user_id=? LIMIT 1');
$q->bind_param('i',$uid); $q->execute(); $account=$q->get_result()->fetch_assoc() ?: [];
$q=$conn->prepare('SELECT COALESCE(NULLIF(st.firstname,""),r.submitted_firstname) AS firstname,COALESCE(NULLIF(st.middle_name,""),r.submitted_middle_name) AS middle_name,COALESCE(NULLIF(st.lastname,""),r.submitted_lastname) AS lastname,st.suffix,COALESCE(st.lrn,r.submitted_lrn) AS lrn,COALESCE(s.grade_level,pd.grade_level) AS grade_level,COALESCE(s.section_name,pd.section_name) AS section_name,COALESCE(str.strand_code,pd.track_name) AS strand_code FROM student_account_requests r LEFT JOIN students st ON st.student_id=r.student_id LEFT JOIN student_enrollments en ON en.student_id=st.student_id AND en.assignment_status="active" LEFT JOIN sections s ON s.section_id=en.section_id LEFT JOIN school_years y ON y.school_year_id=en.school_year_id LEFT JOIN strands str ON str.strand_id=s.strand_id LEFT JOIN student_profile_details pd ON pd.user_id=r.user_id WHERE r.user_id=? ORDER BY y.school_year_id DESC LIMIT 1');
$q->bind_param('i',$uid); $q->execute(); $student=$q->get_result()->fetch_assoc() ?: [];
$q=$conn->prepare('SELECT track_name,grade_level,section_name FROM student_profile_details WHERE user_id=? LIMIT 1');
$q->bind_param('i',$uid); $q->execute(); $savedProfile=$q->get_result()->fetch_assoc() ?: [];
$profileTrack=trim((string)($_POST['track_name']??$savedProfile['track_name']??$student['strand_code']??''));
$profileGrade=preg_replace('/^Grade\s*/i','',trim((string)($_POST['grade_level']??$savedProfile['grade_level']??$student['grade_level']??'')));
$profileSection=trim((string)($_POST['section_name']??$savedProfile['section_name']??$student['section_name']??''));
$profileComplete=$profileTrack!==''&&in_array($profileGrade,['11','12'],true)&&$profileSection!=='';
$editingDetails=!$profileComplete&&(isset($_GET['edit_details'])||($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='save-profile-details'));
$fullName=trim(implode(' ',array_filter([$student['firstname']??'', $student['middle_name']??'', $student['lastname']??''])));
$suffix=trim((string)($student['suffix']??''));
if($suffix!==''&&strtolower($suffix)!=='n/a') $fullName.=' '.$suffix;

if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='save-profile-details') {
    $track=trim((string)($_POST['track_name']??''));
    $grade=trim((string)($_POST['grade_level']??''));
    $section=trim((string)($_POST['section_name']??''));
    if($track===''||strlen($track)>100||!in_array($grade,['11','12'],true)||$section===''||strlen($section)>100) {
        flash('Enter a track, Grade 11 or 12, and a section name.','error');
    } else {
        $q=$conn->prepare('INSERT INTO student_profile_details(user_id,track_name,grade_level,section_name) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE track_name=VALUES(track_name),grade_level=VALUES(grade_level),section_name=VALUES(section_name)');
        $q->bind_param('isss',$uid,$track,$grade,$section); $q->execute();
        audit($conn,$uid,'student_profile_details_updated','student_profile',$uid);
        flash('Your profile details have been saved.'); go('/spi/student/change-password.php');
    }
} elseif($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='change-password') {
    $old=$_POST['current_password']??''; $new=$_POST['password']??'';
    $q=$conn->prepare('SELECT password FROM users WHERE user_id=?'); $q->bind_param('i',$uid); $q->execute(); $hash=$q->get_result()->fetch_column();
    if(!password_verify($old,(string)$hash)||!password_is_valid($new)||$new!==($_POST['confirm_password']??'')) flash('Current password may be incorrect, the new passwords may not match, or the new password is weak.','error');
    else { $next=password_hash($new,PASSWORD_DEFAULT); $q=$conn->prepare('UPDATE users SET password=? WHERE user_id=?'); $q->bind_param('si',$next,$uid); $q->execute(); audit($conn,$uid,'password_changed','user',$uid); flash('Password changed.'); go('/spi/student/change-password.php'); }
}

page_start('My Profile');
echo '<h1>My Profile</h1><section class="card profile-card"><div class="profile-heading"><div class="profile-avatar" aria-hidden="true">'.e(strtoupper(substr($fullName!==''?$fullName:($account['username']??'S'),0,1))).'</div><div><p class="profile-eyebrow">STUDENT ACCOUNT</p><h2>'.e($fullName!==''?$fullName:($account['username']??'Student')).'</h2><p class="profile-subtitle">Your personal and school information</p></div></div><div class="profile-details">';
$details=[
    'Email'=>$account['email']??'',
    'LRN'=>$student['lrn']??'',
    'Grade level'=>isset($student['grade_level'])?'Grade '.$student['grade_level']:'',
    'Section'=>$student['section_name']??'',
    'Track / strand'=>$student['strand_code']??'',
];
foreach($details as $label=>$value) echo '<div class="profile-detail"><span>'.e($label).'</span><strong>'.e(trim((string)$value)!==''?(string)$value:'Not available').'</strong></div>';
echo '</div></section>';
if(!$profileComplete) {
    echo '<div class="profile-complete-action"><button class="button" type="button" id="open-profile-editor">Complete my details</button></div>';
    echo '<dialog class="profile-dialog" id="profile-dialog" aria-labelledby="profile-dialog-title"><div class="profile-dialog-heading"><div><p class="profile-eyebrow">SCHOOL INFORMATION</p><h2 id="profile-dialog-title">Complete my details</h2></div><button type="button" class="profile-dialog-close" aria-label="Close dialog" onclick="document.getElementById(\'profile-dialog\').close()">×</button></div><p class="profile-form-note">Enter your current track, grade level, and section. These are saved to your profile and do not change official class assignments.</p><form method="post" class="profile-details-form"><input type="hidden" name="csrf" value="'.e(csrf_token()).'"><input type="hidden" name="action" value="save-profile-details"><label>Track / strand<input type="text" name="track_name" maxlength="100" value="'.e($profileTrack).'" placeholder="For example, STEM" required></label><label>Grade level<input type="number" name="grade_level" min="11" max="12" step="1" value="'.e($profileGrade).'" placeholder="11 or 12" required></label><label>Section<input type="text" name="section_name" maxlength="100" value="'.e($profileSection).'" placeholder="Enter your section" required></label><div class="profile-dialog-actions"><button class="button ghost" type="button" onclick="document.getElementById(\'profile-dialog\').close()">Cancel</button><button class="submit-button">Save details</button></div></form></dialog><script>const profileDialog=document.getElementById("profile-dialog");document.getElementById("open-profile-editor").addEventListener("click",()=>profileDialog.showModal());'.($editingDetails?'profileDialog.showModal();':'').'</script>';
}
echo '<section class="card profile-password"><div class="profile-section-heading"><div><p class="profile-eyebrow">ACCOUNT SECURITY</p><h2>Change password</h2></div><p>Use a strong password you do not use elsewhere.</p></div><form method="post"><input type="hidden" name="csrf" value="'.e(csrf_token()).'"><input type="hidden" name="action" value="change-password"><label>Current password<div class="password-field"><input id="student-current-password" type="password" name="current_password" autocomplete="current-password" required>'.password_toggle('student-current-password').'</div></label><label>New password<div class="password-field"><input id="student-new-password" type="password" name="password" minlength="8" maxlength="128" autocomplete="new-password" required>'.password_toggle('student-new-password').'</div>'.password_meter('student-new-password').'</label><label>Confirm new password<div class="password-field"><input id="student-confirm-password" type="password" name="confirm_password" minlength="8" maxlength="128" autocomplete="new-password" required>'.password_toggle('student-confirm-password').'</div></label><button class="submit-button">Change password</button></form></section>';
page_end();
