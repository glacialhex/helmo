<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/util.php';
Auth::start();
$user = Auth::user();
if (!$user) { header('Location: /login.php'); exit; }
$canManage = in_array($user['role'], ['Admin','Teacher'], true);
$pdo = DB::conn();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$canManage) { http_response_code(403); echo 'Forbidden'; exit; }
    require_post_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $code = trim($_POST['code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 0);
        $semester = trim($_POST['semester'] ?? '');
        if ($code && $name && $capacity > 0 && $semester) {
            $stmt = $pdo->prepare('INSERT INTO courses (code,name,capacity,semester) VALUES (?,?,?,?)');
            $stmt->execute([$code,$name,$capacity,$semester]);
        }
        redirect('/courses/index.php');
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM courses WHERE id=?')->execute([$id]);
        redirect('/courses/index.php');
    }
}

$courses = $pdo->query('SELECT * FROM courses ORDER BY id DESC')->fetchAll();
require_once __DIR__ . '/../partials/header.php';
?>
<h2>Courses</h2>
<?php if ($canManage): ?>
<form method="post" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="create" />
  <div class="field"><label>Code</label><input name="code" required /></div>
  <div class="field"><label>Name</label><input name="name" required /></div>
  <div class="field"><label>Capacity</label><input type="number" name="capacity" min="1" required /></div>
  <div class="field"><label>Semester</label><input name="semester" required /></div>
  <div style="grid-column:1/5"><button class="btn" type="submit">Add Course</button></div>
</form>
<?php endif; ?>

<table>
  <tr><th>ID</th><th>Code</th><th>Name</th><th>Capacity</th><th>Semester</th><?php if ($canManage): ?><th>Actions</th><?php endif; ?></tr>
  <?php foreach ($courses as $c): ?>
    <tr>
      <td><?= (int)$c['id'] ?></td>
      <td><?= e($c['code']) ?></td>
      <td><?= e($c['name']) ?></td>
      <td><?= (int)$c['capacity'] ?></td>
      <td><?= e($c['semester']) ?></td>
      <?php if ($canManage): ?>
      <td>
        <form method="post" style="display:inline;">
          <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
          <input type="hidden" name="action" value="delete" />
          <input type="hidden" name="id" value="<?= (int)$c['id'] ?>" />
          <button class="btn danger" onclick="return confirm('Delete course?');">Delete</button>
        </form>
      </td>
      <?php endif; ?>
    </tr>
  <?php endforeach; ?>
  <?php if (!$courses): ?><tr><td colspan="<?= $canManage ? '6' : '5' ?>">No courses yet.</td></tr><?php endif; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
