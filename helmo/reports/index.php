<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/util.php';
Auth::start();
Auth::requireRole(['Admin','Teacher']);
$pdo = DB::conn();

$grade = $_GET['grade'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

function valid_date($d){ return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)===1; }
if (($from && !valid_date($from)) || ($to && !valid_date($to))) {
    http_response_code(400); echo 'Invalid date range'; exit;
}

// Students per course
$spc = $pdo->query('SELECT * FROM v_students_per_course')->fetchAll();

// Enrollment per semester
$eps = $pdo->query('SELECT * FROM v_enrollment_per_semester')->fetchAll();

if (isset($_GET['export']) && $_GET['export']==='csv') {
    $rows = array_map(fn($r)=>[$r['code'],$r['course_name'],$r['student_count']], $spc);
    export_csv('students_per_course.csv', ['Code','Course','Students'], $rows);
}

require_once __DIR__ . '/../partials/header.php';
?>
<h2>Reports</h2>

<h3>Students per Course</h3>
<a class="btn" href="?export=csv">Export CSV</a>
<table>
  <tr><th>Code</th><th>Course</th><th>Students</th></tr>
  <?php foreach ($spc as $r): ?>
    <tr><td><?= e($r['code']) ?></td><td><?= e($r['course_name']) ?></td><td><?= (int)$r['student_count'] ?></td></tr>
  <?php endforeach; ?>
</table>

<h3>Enrollment per Semester</h3>
<table>
  <tr><th>Semester</th><th>Enrollments</th></tr>
  <?php foreach ($eps as $r): ?>
    <tr><td><?= e($r['semester']) ?></td><td><?= (int)$r['enrollments'] ?></td></tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
