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
    $date = $_POST['date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'present';
    try {
        $stmt = $pdo->prepare('INSERT INTO attendance (student_id, course_id, attended_on, status) VALUES (?,?,?,?)');
        $stmt->execute([$student_id,$course_id,$date,$status]);
    } catch (PDOException $e) {
        $error = 'Duplicate or invalid attendance entry.';
    }
}

$students = $pdo->query('SELECT id, first_name, last_name FROM students ORDER BY first_name')->fetchAll();
$courses = $pdo->query('SELECT id, code, name FROM courses ORDER BY code')->fetchAll();

// Attendance % by student
$percentages = $pdo->query("SELECT s.id, CONCAT(s.first_name,' ',s.last_name) AS name,
  SUM(a.status='present') AS presents,
  COUNT(a.id) AS total,
  CASE WHEN COUNT(a.id)=0 THEN 0 ELSE ROUND(SUM(a.status='present')/COUNT(a.id)*100,2) END AS percent
FROM students s LEFT JOIN attendance a ON a.student_id = s.id
GROUP BY s.id ORDER BY s.id DESC")->fetchAll();

require_once __DIR__ . '/../partials/header.php';
?>
<h2>Attendance</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<form method="post" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;align-items:end;">
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
  <div class="field">
    <label>Date</label>
    <input type="date" name="date" value="<?= e(date('Y-m-d')) ?>" />
  </div>
  <div class="field">
    <label>Status</label>
    <select name="status">
      <option value="present">Present</option>
      <option value="absent">Absent</option>
      <option value="late">Late</option>
    </select>
  </div>
  <div style="grid-column:1/5"><button class="btn" type="submit">Mark Attendance</button></div>
</form>

<h3>Report: Attendance % by Student</h3>
<table>
  <tr><th>Student</th><th>Presents</th><th>Total</th><th>%</th></tr>
  <?php foreach ($percentages as $p): ?>
    <tr>
      <td><?= e($p['name']) ?></td>
      <td><?= (int)$p['presents'] ?></td>
      <td><?= (int)$p['total'] ?></td>
      <td><?= number_format($p['percent'],2) ?>%</td>
    </tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
