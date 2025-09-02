<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/util.php';
Auth::start();
$user = Auth::user();
Auth::requireRole(['Admin','Teacher','Student']);
$pdo = DB::conn();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    if (!in_array($user['role'], ['Admin','Teacher'], true)) { http_response_code(403); exit; }
    $course_id = (int)($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $due = $_POST['due_date'] ?? '';
    $path = trim($_POST['attachment_path'] ?? '');
    if ($course_id>0 && $title && $due) {
        $pdo->prepare('INSERT INTO homework (course_id, title, description, due_date, attachment_path, created_by) VALUES (?,?,?,?,?,?)')
            ->execute([$course_id,$title,$desc,$due,$path?:null,$user['id']]);
        $pdo->prepare("INSERT INTO sms_logs (message, recipients, status) VALUES (?,?, 'sent')")
            ->execute(['New homework: '.$title, 'students']);
    } else { $error = 'Invalid homework.'; }
}

$courses = $pdo->query('SELECT id, code, name FROM courses ORDER BY code')->fetchAll();
$list = $pdo->query('SELECT h.*, c.code, c.name course FROM homework h JOIN courses c ON c.id=h.course_id ORDER BY h.id DESC')->fetchAll();

require_once __DIR__ . '/../partials/header.php';
?>
<h2>Homework</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<?php if (in_array($user['role'], ['Admin','Teacher'], true)): ?>
<h3>Create</h3>
<form method="post" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <div class="field"><label>Course</label><select name="course_id"><?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['code'].' - '.$c['name']) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label>Title</label><input name="title" required /></div>
  <div class="field"><label>Due</label><input type="date" name="due_date" required /></div>
  <div class="field"><label>Attachment URL (optional)</label><input name="attachment_path" placeholder="/files/sample.pdf" /></div>
  <div class="field" style="grid-column:1/5"><label>Description</label><input name="description" /></div>
  <div style="grid-column:1/5"><button class="btn" type="submit">Publish</button></div>
</form>
<?php endif; ?>

<h3>All Homework</h3>
<table>
  <tr><th>Course</th><th>Title</th><th>Due</th><th>Attachment</th></tr>
  <?php foreach ($list as $h): ?>
    <tr>
      <td><?= e($h['code'].' - '.$h['course']) ?></td>
      <td><?= e($h['title']) ?></td>
      <td><?= e($h['due_date']) ?></td>
      <td><?php if ($h['attachment_path']): ?><a class="btn" href="<?= e($h['attachment_path']) ?>" target="_blank">Download</a><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
