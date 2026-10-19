<?php
require_once __DIR__.'/../includes/bootstrap.php'; require_role('admin');
if($_SERVER['REQUEST_METHOD']==='POST') {
    $action=$_POST['action']??'';
    if($action==='weights') {
        $id=(int)($_POST['subject_id']??0); $names=['Performance Tasks','Written Works','Quarterly Assessment']; $fields=['performance_tasks_weight','written_works_weight','quarterly_assessment_weight'];
        $transactionStarted=false;
        try {
            $q=$conn->prepare('SELECT subject_id FROM subjects WHERE subject_id=?'); $q->bind_param('i',$id); $q->execute(); if(!$q->get_result()->fetch_assoc()) throw new RuntimeException('Choose a valid subject.');
            $values=[]; foreach($fields as $field){$raw=trim($_POST[$field]??''); if($raw===''||!preg_match('/^\d{1,3}(\.\d{1,2})?$/',$raw)){$values[]=null;continue;} $value=(float)$raw;if($value<=0||$value>100)throw new RuntimeException('Each category must be between 0.01% and 100%.');$values[]=$value;}
            if(array_sum(array_filter($values,static fn($v)=>$v!==null))>100.0001) throw new RuntimeException('The three category weights cannot total more than 100%.');
            $conn->begin_transaction(); $transactionStarted=true;
            foreach($names as $index=>$category){if($values[$index]===null){$q=$conn->prepare('UPDATE subject_grading_components SET status="inactive" WHERE subject_id=? AND component_name=?');$q->bind_param('is',$id,$category);$q->execute();}else{$q=$conn->prepare('INSERT INTO subject_grading_components(subject_id,component_name,weight_percent,sort_order,status) VALUES(?,?,?, ?,"active") ON DUPLICATE KEY UPDATE weight_percent=VALUES(weight_percent),sort_order=VALUES(sort_order),status="active"');$order=$index+1;$q->bind_param('isdi',$id,$category,$values[$index],$order);$q->execute();}}
            $conn->commit(); $transactionStarted=false; audit($conn,$_SESSION['user_id'],'subject_weights_updated','subject',$id,['weights'=>$values]); flash('Subject category weights saved.');
        } catch(Throwable $e) { if($transactionStarted){try{$conn->rollback();}catch(Throwable $ignored){}} flash($e instanceof RuntimeException?$e->getMessage():'Could not save the subject weights.','error'); }
        go('/spi/admin/subjects.php');
    }
    if($action==='status') {
        $id=(int)($_POST['subject_id']??0); $status=$_POST['status']==='inactive'?'inactive':'active';
        $q=$conn->prepare('UPDATE subjects SET status=? WHERE subject_id=?'); $q->bind_param('si',$status,$id); $q->execute(); audit($conn,$_SESSION['user_id'],'subject_status_changed','subject',$id,['status'=>$status]); flash('Subject status updated.'); go('/spi/admin/subjects.php');
    }
    $id=(int)($_POST['subject_id']??0); $code=trim($_POST['subject_code']??''); $name=trim($_POST['subject_name']??''); $description=trim($_POST['description']??''); $grade=$_POST['grade_level']??''; $strand=($_POST['strand_id']??'')===''?null:(int)$_POST['strand_id'];
    if($code===''||$name===''||!in_array($grade,['11','12'],true)||strlen($code)>40||strlen($name)>150) flash('Enter a subject code, name, and Grade 11 or Grade 12.','error');
    else try {
        if($id){$q=$conn->prepare('UPDATE subjects SET subject_code=?,subject_name=?,description=?,grade_level=?,strand_id=? WHERE subject_id=?');$q->bind_param('ssssii',$code,$name,$description,$grade,$strand,$id);$q->execute();audit($conn,$_SESSION['user_id'],'subject_updated','subject',$id);}
        else{$q=$conn->prepare('INSERT INTO subjects(subject_code,subject_name,description,grade_level,strand_id) VALUES(?,?,?,?,?)');$q->bind_param('ssssi',$code,$name,$description,$grade,$strand);$q->execute();audit($conn,$_SESSION['user_id'],'subject_created','subject',(int)$conn->insert_id);}
        flash('Subject saved.');
    } catch(Throwable $e){flash('Could not save the subject. Check for a duplicate subject code.','error');}
    go('/spi/admin/subjects.php');
}
$edit=null;if(isset($_GET['edit'])){$id=(int)$_GET['edit'];$q=$conn->prepare('SELECT * FROM subjects WHERE subject_id=?');$q->bind_param('i',$id);$q->execute();$edit=$q->get_result()->fetch_assoc();}
$strands=$conn->query('SELECT strand_id,strand_code,strand_name FROM strands WHERE status="active" ORDER BY strand_name')->fetch_all(MYSQLI_ASSOC);
$rows=$conn->query('SELECT s.*,t.strand_code,(SELECT c.weight_percent FROM subject_grading_components c WHERE c.subject_id=s.subject_id AND c.component_name="Performance Tasks" AND c.status="active" LIMIT 1) AS performance_tasks_weight,(SELECT c.weight_percent FROM subject_grading_components c WHERE c.subject_id=s.subject_id AND c.component_name="Written Works" AND c.status="active" LIMIT 1) AS written_works_weight,(SELECT c.weight_percent FROM subject_grading_components c WHERE c.subject_id=s.subject_id AND c.component_name="Quarterly Assessment" AND c.status="active" LIMIT 1) AS quarterly_assessment_weight FROM subjects s LEFT JOIN strands t ON t.strand_id=s.strand_id ORDER BY s.grade_level,s.subject_name')->fetch_all(MYSQLI_ASSOC);
page_start('Subjects');
?>
<h1>Subjects</h1>
<p>Manage subjects and set their Performance Tasks, Written Works, and Quarterly Assessment weights together. Leave a category blank if it does not apply.</p>
<section class="card">
    <h2><?= $edit ? 'Edit subject' : 'Add a subject' ?></h2>
    <form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="subject_id" value="<?= (int)($edit['subject_id']??0) ?>">
        <div class="formgrid">
            <label>Subject code<input name="subject_code" maxlength="40" value="<?=e($edit['subject_code']??'')?>" required></label>
            <label>Subject name<input name="subject_name" maxlength="150" value="<?=e($edit['subject_name']??'')?>" required></label>
            <label>Grade level<select name="grade_level"><option value="11" <?=($edit['grade_level']??'11')==='11'?'selected':''?>>Grade 11</option><option value="12" <?=($edit['grade_level']??'')==='12'?'selected':''?>>Grade 12</option></select></label>
            <label>Strand / track <span class="optional">Optional</span><select name="strand_id"><option value="">Core subjects</option><?php foreach($strands as $strand): ?><option value="<?=$strand['strand_id']?>" <?=((string)($edit['strand_id']??'')===(string)$strand['strand_id'])?'selected':''?>><?=e($strand['strand_code'].' — '.$strand['strand_name'])?></option><?php endforeach; ?></select></label>
            <label class="field-email">Description <span class="optional">Optional</span><input name="description" value="<?=e($edit['description']??'')?>" maxlength="500"></label>
        </div><button name="action" value="save">Save subject</button><?php if($edit): ?> <a class="button ghost" href="/spi/admin/subjects.php">Cancel edit</a><?php endif; ?>
    </form>
</section>
<h2>Subject list</h2>
<div class="tablewrap"><table><thead><tr><th>Code</th><th>Subject</th><th>Grade</th><th>Strand / track</th><th>Performance Tasks</th><th>Written Works</th><th>Quarterly Assessment</th><th>Save</th></tr></thead><tbody>
<?php foreach($rows as $subject): $weightsForm='weights-'.$subject['subject_id']; ?><tr><td><?=e($subject['subject_code'])?><form id="<?=$weightsForm?>" method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="subject_id" value="<?=$subject['subject_id']?>"><input type="hidden" name="action" value="weights"></form></td><td><?=e($subject['subject_name'])?></td><td>Grade <?=e($subject['grade_level'])?></td><td><?=e($subject['strand_code']??'Core subjects')?></td><td><input form="<?=$weightsForm?>" aria-label="Performance Tasks weight for <?=e($subject['subject_name'])?>" name="performance_tasks_weight" type="number" min="0.01" max="100" step="0.01" value="<?=e($subject['performance_tasks_weight']===null?'':(string)$subject['performance_tasks_weight'])?>" placeholder="—"></td><td><input form="<?=$weightsForm?>" aria-label="Written Works weight for <?=e($subject['subject_name'])?>" name="written_works_weight" type="number" min="0.01" max="100" step="0.01" value="<?=e($subject['written_works_weight']===null?'':(string)$subject['written_works_weight'])?>" placeholder="—"></td><td><input form="<?=$weightsForm?>" aria-label="Quarterly Assessment weight for <?=e($subject['subject_name'])?>" name="quarterly_assessment_weight" type="number" min="0.01" max="100" step="0.01" value="<?=e($subject['quarterly_assessment_weight']===null?'':(string)$subject['quarterly_assessment_weight'])?>" placeholder="—"></td><td><button form="<?=$weightsForm?>">Save</button></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php page_end(); ?>
