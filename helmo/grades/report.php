<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/util.php';
Auth::start();
Auth::requireRole(['Admin','Teacher']);
$pdo = DB::conn();
$student_id = (int)($_GET['student_id'] ?? 0);
$student = $pdo->prepare('SELECT * FROM students WHERE id=?');
$student->execute([$student_id]);
$s = $student->fetch();
if (!$s) { http_response_code(404); echo 'Not found'; exit; }
$grades = $pdo->prepare('SELECT g.*, c.code, c.name FROM grades g JOIN courses c ON c.id=g.course_id WHERE g.student_id=?');
$grades->execute([$student_id]);
$rows = $grades->fetchAll();
$avg = $rows ? array_sum(array_map(fn($r)=> (float)$r['grade'], $rows)) / count($rows) : 0;
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8" /><title>Report Card</title>
<style>body{font-family:Arial,sans-serif;padding:20px} table{width:100%;border-collapse:collapse} th,td{border:1px solid #ddd;padding:6px} th{background:#f3f4f6}</style>
<script>function doPrint(){window.print();}</script>
</head>
<body>
  <h2>Report Card</h2>
  <p><strong>Student:</strong> <?= e($s['first_name'].' '.$s['last_name']) ?> (ID <?= (int)$s['id'] ?>)</p>
  <table>
    <tr><th>Course</th><th>Grade</th></tr>
    <?php foreach ($rows as $r): ?>
      <tr><td><?= e($r['code'].' - '.$r['name']) ?></td><td><?= number_format((float)$r['grade'],2) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="2">No grades yet.</td></tr><?php endif; ?>
    <tr><th>Average</th><th><?= number_format((float)$avg,2) ?></th></tr>
  </table>
  <p><button onclick="doPrint()">Print</button></p>
</body>
</html>
