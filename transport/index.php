<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/util.php';
Auth::start();
Auth::requireRole(['Admin']);
$pdo = DB::conn();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add_route') {
        $name = trim($_POST['name'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 0);
        $driver = trim($_POST['driver_name'] ?? '');
        if ($name && $capacity>0) {
            $pdo->prepare('INSERT INTO bus_routes (name, capacity, driver_name) VALUES (?,?,?)')->execute([$name,$capacity,$driver?:null]);
        } else { $error='Invalid route.'; }
    } elseif ($action === 'assign_driver') {
        $id = (int)($_POST['route_id'] ?? 0);
        $driver = trim($_POST['driver_name'] ?? '');
        $pdo->prepare('UPDATE bus_routes SET driver_name=? WHERE id=?')->execute([$driver,$id]);
    } elseif ($action === 'register_transport') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $route_id = (int)($_POST['route_id'] ?? 0);
        $cap = $pdo->prepare('SELECT capacity FROM bus_routes WHERE id=?');
        $cap->execute([$route_id]);
        $capacity = (int)($cap->fetch()['capacity'] ?? 0);
        $cnt = $pdo->prepare('SELECT COUNT(*) c FROM transport_requests WHERE route_id=?');
        $cnt->execute([$route_id]);
        $cur = (int)$cnt->fetch()['c'];
        if ($cur >= $capacity) { $error = 'No seats available.'; }
        else {
          $pdo->prepare('INSERT INTO transport_requests (student_id, route_id) VALUES (?,?)')->execute([$student_id,$route_id]);
          $r = $pdo->prepare('SELECT name FROM bus_routes WHERE id=?');
          $r->execute([$route_id]);
          $routeName = $r->fetch()['name'] ?? '';
          $pdo->prepare("INSERT INTO sms_logs (message, recipients, status) VALUES (?,?, 'sent')")
            ->execute(['Transport registered for route '.$routeName, 'parent']);
        }
    }
}

$routes = $pdo->query('SELECT * FROM bus_routes ORDER BY id DESC')->fetchAll();
$students = $pdo->query('SELECT id, first_name, last_name FROM students ORDER BY first_name')->fetchAll();
$usage = $pdo->query('SELECT br.name, COUNT(tr.id) as cnt FROM bus_routes br LEFT JOIN transport_requests tr ON tr.route_id = br.id GROUP BY br.id')->fetchAll();

require_once __DIR__ . '/../partials/header.php';
?>
<h2>Transport</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<h3>Add Route</h3>
<form method="post" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="add_route" />
  <div class="field"><label>Name</label><input name="name" required /></div>
  <div class="field"><label>Capacity</label><input type="number" name="capacity" min="1" required /></div>
  <div class="field"><label>Driver</label><input name="driver_name" /></div>
  <div style="grid-column:1/4"><button class="btn" type="submit">Add</button></div>
</form>

<h3>Assign Driver</h3>
<form method="post" style="display:flex;gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="assign_driver" />
  <div class="field"><label>Route</label>
    <select name="route_id"><?php foreach ($routes as $r): ?><option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?></select>
  </div>
  <div class="field"><label>Driver</label><input name="driver_name" /></div>
  <button class="btn" type="submit">Assign</button>
</form>

<h3>Register Student for Transport</h3>
<form method="post" style="display:flex;gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="register_transport" />
  <div class="field"><label>Student</label>
    <select name="student_id"><?php foreach ($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['first_name'].' '.$s['last_name']) ?></option><?php endforeach; ?></select>
  </div>
  <div class="field"><label>Route</label>
    <select name="route_id"><?php foreach ($routes as $r): ?><option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?></select>
  </div>
  <button class="btn" type="submit">Register</button>
</form>

<h3>Report: Route Usage</h3>
<table>
  <tr><th>Route</th><th>Students</th></tr>
  <?php foreach ($usage as $u): ?>
    <tr><td><?= e($u['name']) ?></td><td><?= (int)$u['cnt'] ?></td></tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
