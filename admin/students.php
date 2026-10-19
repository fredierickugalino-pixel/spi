<?php
require_once __DIR__.'/../includes/bootstrap.php';
require_role('admin');

$search=trim($_GET['q']??'');
$like='%'.$search.'%';
$q=$conn->prepare('SELECT st.lrn,st.firstname,st.middle_name,st.lastname,st.suffix,(SELECT sec.section_name FROM student_enrollments en JOIN sections sec ON sec.section_id=en.section_id JOIN school_years sy ON sy.school_year_id=en.school_year_id WHERE en.student_id=st.student_id AND en.assignment_status="active" ORDER BY sy.is_active DESC,en.school_year_id DESC LIMIT 1) AS section_name,(SELECT tr.strand_code FROM student_enrollments en JOIN sections sec ON sec.section_id=en.section_id JOIN strands tr ON tr.strand_id=sec.strand_id JOIN school_years sy ON sy.school_year_id=en.school_year_id WHERE en.student_id=st.student_id AND en.assignment_status="active" ORDER BY sy.is_active DESC,en.school_year_id DESC LIMIT 1) AS strand_code FROM students st WHERE st.lrn LIKE ? OR st.firstname LIKE ? OR st.middle_name LIKE ? OR st.lastname LIKE ? ORDER BY st.lastname,st.firstname LIMIT 250');
$q->bind_param('ssss',$like,$like,$like,$like);
$q->execute();
$rows=$q->get_result();

page_start('Students');
echo '<h1>Student records</h1><form class="search"><label>Search LRN or name<input name="q" value="'.e($search).'" placeholder="Enter LRN or student name"></label><button>Search</button></form><div class="tablewrap"><table><thead><tr><th>LRN</th><th>Student</th><th>Section</th><th>Track</th></tr></thead><tbody>';
while($s=$rows->fetch_assoc()) { $suffix=trim((string)$s['suffix']); $name=trim($s['firstname'].' '.$s['middle_name'].' '.$s['lastname'].($suffix!==''&&strcasecmp($suffix,'n/a')!==0?' '.$suffix:'')); echo '<tr><td>'.e($s['lrn']).'</td><td>'.e($name).'</td><td>'.e($s['section_name']??'Unassigned').'</td><td>'.e($s['strand_code']??'Unassigned').'</td></tr>'; }
echo '</tbody></table></div>';
page_end();
