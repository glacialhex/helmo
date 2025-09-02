<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/util.php';
Auth::start();
Auth::requireRole(['Admin','Teacher']);
$pdo = DB::conn();

$error = '';

function valid_national_id(string $nid): bool {
    return preg_match('/^[0-9]{14}$/', $nid) === 1; // Egypt-style, adjust as needed
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create' || $action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $first = trim($_POST['first_name'] ?? '');
        $last = trim($_POST['last_name'] ?? '');
        $nid = trim($_POST['national_id'] ?? '');
        $grade = trim($_POST['grade_level'] ?? '');
        $contact = trim($_POST['guardian_contact'] ?? '');
        if ($first === '' || $last === '' || !valid_national_id($nid) || $grade === '') {
            $error = 'Invalid input (check names, grade and national ID).';
        } else {
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO students (first_name,last_name,national_id,grade_level,guardian_contact) VALUES (?,?,?,?,?)');
                $stmt->execute([$first,$last,$nid,$grade,$contact]);
            } else {
                $stmt = $pdo->prepare('UPDATE students SET first_name=?, last_name=?, national_id=?, grade_level=?, guardian_contact=? WHERE id=?');
                $stmt->execute([$first,$last,$nid,$grade,$contact,$id]);
            }
            redirect('/students/index.php');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM students WHERE id=?')->execute([$id]);
        redirect('/students/index.php');
    }
}

$students = $pdo->query('SELECT * FROM students ORDER BY id DESC')->fetchAll();
$byGrade = $pdo->query('SELECT grade_level, COUNT(*) cnt FROM students GROUP BY grade_level')->fetchAll();
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = null;
if ($editId) { $st=$pdo->prepare('SELECT * FROM students WHERE id=?'); $st->execute([$editId]); $editRow=$st->fetch(); }

require_once __DIR__ . '/../partials/header.php';
?>
<h2>Students</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<h3><?= $editRow ? 'Edit Student' : 'Add Student' ?></h3>
<form method="post" style="display:grid;grid-template-columns:repeat(2,minmax(200px,1fr));gap:12px;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'create' ?>" />
  <?php if ($editRow): ?><input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>" /><?php endif; ?>
  <div class="field"><label>First Name</label><input name="first_name" value="<?= e($editRow['first_name'] ?? '') ?>" required /></div>
  <div class="field"><label>Last Name</label><input name="last_name" value="<?= e($editRow['last_name'] ?? '') ?>" required /></div>
  <div class="field"><label>National ID</label><input name="national_id" value="<?= e($editRow['national_id'] ?? '') ?>" required /></div>
  <div class="field"><label>Grade</label><input name="grade_level" value="<?= e($editRow['grade_level'] ?? '') ?>" required /></div>
  <div class="field" style="grid-column:1/3"><label>Guardian Contact</label><input name="guardian_contact" value="<?= e($editRow['guardian_contact'] ?? '') ?>" /></div>
  <div style="grid-column:1/3"><button class="btn" type="submit">Save</button> <?php if ($editRow): ?><a class="btn secondary" href="/students/index.php">Cancel</a><?php endif; ?></div>
  <small style="grid-column:1/3;color:#6b7280;">National ID must be 14 digits.</small>
</form>

<h3>All Students</h3>
<table>
  <tr><th>ID</th><th>Name</th><th>National ID</th><th>Grade</th><th>Guardian</th><th>Actions</th></tr>
  <?php foreach ($students as $s): ?>
    <tr>
      <td><?= (int)$s['id'] ?></td>
      <td><?= e($s['first_name'] . ' ' . $s['last_name']) ?></td>
      <td><?= e($s['national_id']) ?></td>
      <td><?= e($s['grade_level']) ?></td>
      <td><?= e($s['guardian_contact']) ?></td>
      <td>
        <a class="btn" href="/students/index.php?edit=<?= (int)$s['id'] ?>">Edit</a>
        <form method="post" style="display:inline;">
          <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
          <input type="hidden" name="action" value="delete" />
          <input type="hidden" name="id" value="<?= (int)$s['id'] ?>" />
          <button class="btn danger" onclick="return confirm('Delete this student?');">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$students): ?>
    <tr><td colspan="6">No students yet.</td></tr>
  <?php endif; ?>
</table>

<h3>Report: Students by Grade</h3>
<table>
  <tr><th>Grade</th><th>Count</th></tr>
  <?php foreach ($byGrade as $g): ?>
    <tr><td><?= e($g['grade_level']) ?></td><td><?= (int)$g['cnt'] ?></td></tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
