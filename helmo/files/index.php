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
    $title = trim($_POST['title'] ?? '');
    $path = trim($_POST['path'] ?? '');
    $course_id = (int)($_POST['course_id'] ?? 0) ?: null;
    if ($title && $path) {
        $pdo->prepare('INSERT INTO files (uploader_id, title, path, course_id) VALUES (?,?,?,?)')->execute([$user['id'],$title,$path?:null,$course_id]);
    } else { $error = 'Invalid file.'; }
}

if (isset($_GET['download'])) {
    $id = (int)$_GET['download'];
    $st = $pdo->prepare('SELECT * FROM files WHERE id=?'); $st->execute([$id]); $f=$st->fetch();
    if ($f) {
        // Log download
        $pdo->prepare('INSERT INTO file_downloads (file_id, user_id) VALUES (?,?)')->execute([$id, $user['id'] ?? null]);
        header('Location: '.$f['path']);
        exit;
    }
}

$courses = $pdo->query('SELECT id, code, name FROM courses ORDER BY code')->fetchAll();
$list = $pdo->query('SELECT f.*, c.code FROM files f LEFT JOIN courses c ON c.id=f.course_id ORDER BY f.id DESC')->fetchAll();

require_once __DIR__ . '/../partials/header.php';
?>
<h2>Files</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<?php if (in_array($user['role'], ['Admin','Teacher'], true)): ?>
<h3>Upload (URL/Path)</h3>
<form method="post" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <div class="field"><label>Title</label><input name="title" required /></div>
  <div class="field"><label>Path or URL</label><input name="path" placeholder="/uploads/file.pdf or https://..." required /></div>
  <div class="field"><label>Course (optional)</label><select name="course_id"><option value="">None</option><?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['code'].' - '.$c['name']) ?></option><?php endforeach; ?></select></div>
  <div><button class="btn" type="submit">Save</button></div>
</form>
<?php endif; ?>

<h3>Resources</h3>
<table>
  <tr><th>Title</th><th>Course</th><th>Link</th></tr>
  <?php foreach ($list as $f): ?>
    <tr><td><?= e($f['title']) ?></td><td><?= e($f['code'] ?? '') ?></td><td><a class="btn" href="?download=<?= (int)$f['id'] ?>">Download</a></td></tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
