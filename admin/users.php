<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/util.php';
Auth::start();
Auth::requireRole(['Admin']);
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'assign') {
    require_post_csrf();
    $user_id = (int)($_POST['user_id'] ?? 0);
    $role_id = (int)($_POST['role_id'] ?? 0);
    $stmt = $pdo->prepare('UPDATE users SET role_id = ? WHERE id = ?');
    $stmt->execute([$role_id, $user_id]);
    redirect('/admin/users.php');
}

$users = $pdo->query('SELECT u.id, u.username, r.name AS role FROM users u JOIN roles r ON r.id = u.role_id ORDER BY u.id DESC')->fetchAll();
$roles = $pdo->query('SELECT id, name FROM roles ORDER BY id')->fetchAll();

// Report: users by role
$report = $pdo->query('SELECT r.name AS role, COUNT(u.id) AS cnt FROM roles r LEFT JOIN users u ON u.role_id = r.id GROUP BY r.id ORDER BY r.id')->fetchAll();

require_once __DIR__ . '/../partials/header.php';
?>
<h2>Users & Roles</h2>
<table>
  <tr><th>ID</th><th>Username</th><th>Role</th><th>Assign</th></tr>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?= (int)$u['id'] ?></td>
      <td><?= e($u['username']) ?></td>
      <td><?= e($u['role']) ?></td>
      <td>
        <form method="post" style="display:flex;gap:6px;align-items:center;">
          <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
          <input type="hidden" name="action" value="assign" />
          <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>" />
          <select name="role_id">
            <?php foreach ($roles as $r): ?>
              <option value="<?= (int)$r['id'] ?>" <?= $r['name']===$u['role']?'selected':'' ?>><?= e($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn" type="submit">Save</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<h3>Report: Users by Role</h3>
<table>
  <tr><th>Role</th><th>Count</th></tr>
  <?php foreach ($report as $r): ?>
    <tr><td><?= e($r['role']) ?></td><td><?= (int)$r['cnt'] ?></td></tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
