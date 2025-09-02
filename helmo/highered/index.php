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
    $action = $_POST['action'] ?? '';
    if ($action === 'add_project') {
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $student_id = (int)($_POST['student_id'] ?? 0);
        $supervisor_id = (int)($_POST['supervisor_id'] ?? 0) ?: null;
        if ($title && $student_id>0) {
            $pdo->prepare('INSERT INTO projects (title, description, student_id, supervisor_id) VALUES (?,?,?,?)')->execute([$title,$desc,$student_id,$supervisor_id]);
        } else { $error = 'Invalid project.'; }
    } elseif ($action === 'add_exam_hall') {
        $name = trim($_POST['hall_name'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 0);
        if ($name && $capacity>0) {
            $pdo->prepare('INSERT INTO exam_halls (hall_name, capacity) VALUES (?,?)')->execute([$name,$capacity]);
        } else { $error = 'Invalid hall.'; }
    } elseif ($action === 'randomize_seating') {
        $hall_id = (int)($_POST['hall_id'] ?? 0);
        $course_id = (int)($_POST['course_id'] ?? 0);
        $exam_date = $_POST['exam_date'] ?? date('Y-m-d');
        // Pull enrolled students and shuffle
        $st = $pdo->prepare('SELECT s.id FROM enrollments e JOIN students s ON s.id=e.student_id WHERE e.course_id=?');
        $st->execute([$course_id]);
        $studentIds = array_map(fn($r)=>(int)$r['id'], $st->fetchAll());
        shuffle($studentIds);
        // Clear previous for the same hall/course/date
        $pdo->prepare('DELETE FROM exam_seating WHERE hall_id=? AND course_id=? AND exam_date=?')->execute([$hall_id,$course_id,$exam_date]);
        $i = 1;
        foreach ($studentIds as $sid) {
            $pdo->prepare('INSERT INTO exam_seating (hall_id, course_id, exam_date, student_id, seat_no) VALUES (?,?,?,?,?)')
                ->execute([$hall_id,$course_id,$exam_date,$sid,$i++]);
        }
    }
}

$students = $pdo->query('SELECT id, first_name, last_name FROM students ORDER BY first_name')->fetchAll();
$teachers = $pdo->query("SELECT u.id, u.username FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='Teacher'")->fetchAll();
$projects = $pdo->query('SELECT p.*, CONCAT(s.first_name, " ", s.last_name) student FROM projects p JOIN students s ON s.id=p.student_id ORDER BY p.id DESC')->fetchAll();
$halls = $pdo->query('SELECT * FROM exam_halls ORDER BY hall_name')->fetchAll();
$courses = $pdo->query('SELECT id, code, name FROM courses ORDER BY code')->fetchAll();
$seating = $pdo->query('SELECT es.*, h.hall_name, c.code, c.name as course FROM exam_seating es JOIN exam_halls h ON h.id=es.hall_id JOIN courses c ON c.id=es.course_id ORDER BY es.exam_date DESC, es.hall_id, es.seat_no')->fetchAll();

require_once __DIR__ . '/../partials/header.php';
?>
<h2>Higher Education</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<h3>Projects</h3>
<form method="post" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="add_project" />
  <div class="field"><label>Title</label><input name="title" required /></div>
  <div class="field"><label>Student</label>
    <select name="student_id"><?php foreach ($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['first_name'].' '.$s['last_name']) ?></option><?php endforeach; ?></select>
  </div>
  <div class="field"><label>Supervisor</label>
    <select name="supervisor_id"><option value="">None</option><?php foreach ($teachers as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['username']) ?></option><?php endforeach; ?></select>
  </div>
  <div class="field" style="grid-column:1/5"><label>Description</label><input name="description" /></div>
  <div style="grid-column:1/5"><button class="btn" type="submit">Add</button></div>
</form>

<table>
  <tr><th>Title</th><th>Student</th><th>Milestone</th></tr>
  <?php foreach ($projects as $p): ?>
    <tr><td><?= e($p['title']) ?></td><td><?= e($p['student']) ?></td><td><?= e($p['milestone']) ?></td></tr>
  <?php endforeach; ?>
</table>

<h3>Exam Halls</h3>
<form method="post" style="display:flex;gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="add_exam_hall" />
  <div class="field"><label>Hall</label><input name="hall_name" required /></div>
  <div class="field"><label>Capacity</label><input type="number" name="capacity" min="1" required /></div>
  <button class="btn" type="submit">Add</button>
  </form>

<h3>Randomize Seating</h3>
<form method="post" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="randomize_seating" />
  <div class="field"><label>Hall</label><select name="hall_id"><?php foreach ($halls as $h): ?><option value="<?= (int)$h['id'] ?>"><?= e($h['hall_name']) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label>Course</label><select name="course_id"><?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['code'].' - '.$c['name']) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label>Date</label><input type="date" name="exam_date" value="<?= e(date('Y-m-d')) ?>" /></div>
  <div style="grid-column:1/5"><button class="btn" type="submit">Generate</button></div>
</form>

<h3>Seating Chart</h3>
<table>
  <tr><th>Date</th><th>Hall</th><th>Course</th><th>Student ID</th><th>Seat</th></tr>
  <?php foreach ($seating as $s): ?>
    <tr><td><?= e($s['exam_date']) ?></td><td><?= e($s['hall_name']) ?></td><td><?= e($s['code'].' - '.$s['course']) ?></td><td><?= (int)$s['student_id'] ?></td><td><?= (int)$s['seat_no'] ?></td></tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
