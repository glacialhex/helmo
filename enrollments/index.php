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
    // capacity check
    $cap = $pdo->prepare('SELECT capacity FROM courses WHERE id=?');
    $cap->execute([$course_id]);
    $row = $cap->fetch();
    if ($row) {
        $capacity = (int)$row['capacity'];
        $count = $pdo->prepare('SELECT COUNT(*) c FROM enrollments WHERE course_id=?');
        $count->execute([$course_id]);
        $enrolled = (int)$count->fetch()['c'];
        if ($enrolled >= $capacity) {
            $error = 'Course is at full capacity.';
        } else {
            try {
                $pdo->prepare('INSERT INTO enrollments (student_id, course_id) VALUES (?,?)')->execute([$student_id,$course_id]);
            } catch (PDOException $e) {
                $error = 'Student already enrolled or invalid.';
            }
        }
    }
}

$students = $pdo->query('SELECT id, first_name, last_name FROM students ORDER BY first_name')->fetchAll();
$courses = $pdo->query('SELECT id, code, name FROM courses ORDER BY code')->fetchAll();
$report = $pdo->query('SELECT * FROM v_students_per_course')->fetchAll();

require_once __DIR__ . '/../partials/header.php';
?>
<h2>Enrollments</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<form method="post" style="display:flex;gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <div class="field">
    <label>Student</label>
    <select name="student_id">
      <?php foreach ($students as $s): ?>
        <option value="<?= (int)$s['id'] ?>"><?= e($s['first_name'].' '.$s['last_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label>Course</label>
    <select name="course_id">
      <?php foreach ($courses as $c): ?>
        <option value="<?= (int)$c['id'] ?>"><?= e($c['code'].' - '.$c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button class="btn" type="submit">Enroll</button>
</form>

<h3>Report: Students per Course</h3>
<table>
  <tr><th>Code</th><th>Course</th><th>Students</th></tr>
  <?php foreach ($report as $r): ?>
    <tr><td><?= e($r['code']) ?></td><td><?= e($r['course_name']) ?></td><td><?= (int)$r['student_count'] ?></td></tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
