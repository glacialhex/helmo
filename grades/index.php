<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/util.php';
Auth::start();
Auth::requireRole(['Admin','Teacher']);
$pdo = DB::conn();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $student_id = (int)($_POST['student_id'] ?? 0);
    $course_id = (int)($_POST['course_id'] ?? 0);
    $grade = (float)($_POST['grade'] ?? -1);
    if ($student_id>0 && $course_id>0 && $grade >= 0 && $grade <= 100) {
        try {
            $pdo->prepare('INSERT INTO grades (student_id, course_id, grade) VALUES (?,?,?) ON DUPLICATE KEY UPDATE grade=VALUES(grade), graded_at=CURRENT_TIMESTAMP')
                ->execute([$student_id,$course_id,$grade]);
        } catch (PDOException $e) { $error = 'Unable to save grade.'; }
    } else { $error = 'Invalid grade input.'; }
}

$students = $pdo->query('SELECT id, first_name, last_name FROM students ORDER BY first_name')->fetchAll();
$courses = $pdo->query('SELECT id, code, name FROM courses ORDER BY code')->fetchAll();
$list = $pdo->query('SELECT g.*, CONCAT(s.first_name, " ", s.last_name) student, c.code, c.name course FROM grades g JOIN students s ON s.id=g.student_id JOIN courses c ON c.id=g.course_id ORDER BY g.graded_at DESC')->fetchAll();

require_once __DIR__ . '/../partials/header.php';
?>
<h2>Grades</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<form method="post" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <div class="field"><label>Student</label>
    <select name="student_id"><?php foreach ($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['first_name'].' '.$s['last_name']) ?></option><?php endforeach; ?></select>
  </div>
  <div class="field"><label>Course</label>
    <select name="course_id"><?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['code'].' - '.$c['name']) ?></option><?php endforeach; ?></select>
  </div>
  <div class="field"><label>Grade (0-100)</label><input type="number" step="0.01" min="0" max="100" name="grade" required /></div>
  <div><button class="btn" type="submit">Save</button></div>
</form>

<h3>All Grades</h3>
<table>
  <tr><th>Student</th><th>Course</th><th>Grade</th><th>Report</th></tr>
  <?php foreach ($list as $g): ?>
    <tr>
      <td><?= e($g['student']) ?></td>
      <td><?= e($g['code'].' - '.$g['course']) ?></td>
      <td><?= number_format((float)$g['grade'],2) ?></td>
      <td><a class="btn" href="/grades/report.php?student_id=<?= (int)$g['student_id'] ?>" target="_blank">Report Card</a></td>
    </tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
