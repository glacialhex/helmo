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
    if ($action === 'add_classroom') {
        $building = trim($_POST['building'] ?? '');
        $room = trim($_POST['room_number'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 0);
        if ($building && $room && $capacity>0) {
            $pdo->prepare('INSERT INTO classrooms (building, room_number, capacity) VALUES (?,?,?)')->execute([$building,$room,$capacity]);
        } else { $error = 'Invalid classroom.'; }
    } elseif ($action === 'add_lab') {
        $name = trim($_POST['name'] ?? '');
        $building = trim($_POST['building'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 0);
        if ($name && $building && $capacity>0) {
            $pdo->prepare('INSERT INTO labs (name, building, capacity) VALUES (?,?,?)')->execute([$name,$building,$capacity]);
        } else { $error = 'Invalid lab.'; }
    } elseif ($action === 'add_maintenance') {
        $lab_id = (int)($_POST['lab_id'] ?? 0);
        $desc = trim($_POST['description'] ?? '');
        $date = $_POST['maintenance_date'] ?? date('Y-m-d');
        $next = $_POST['next_check'] ?? null;
        if ($lab_id>0 && $desc) {
            $pdo->prepare('INSERT INTO lab_maintenance (lab_id, description, maintenance_date, next_check) VALUES (?,?,?,?)')->execute([$lab_id,$desc,$date,$next]);
        } else { $error = 'Invalid maintenance.'; }
  } elseif ($action === 'delete_classroom') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare('DELETE FROM classrooms WHERE id=?')->execute([$id]);
  } elseif ($action === 'delete_lab') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare('DELETE FROM labs WHERE id=?')->execute([$id]);
  } elseif ($action === 'delete_maintenance') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare('DELETE FROM lab_maintenance WHERE id=?')->execute([$id]);
  }
}

$classrooms = $pdo->query('SELECT * FROM classrooms ORDER BY building, room_number')->fetchAll();
$labs = $pdo->query('SELECT * FROM labs ORDER BY name')->fetchAll();
$maintenance = $pdo->query('SELECT lm.*, l.name FROM lab_maintenance lm JOIN labs l ON l.id=lm.lab_id ORDER BY lm.maintenance_date DESC')->fetchAll();
$roomsByBuilding = $pdo->query('SELECT building, COUNT(*) cnt FROM classrooms GROUP BY building')->fetchAll();
// Due inspections within 7 days
$dueSoon = $pdo->query("SELECT * FROM equipment WHERE next_inspection IS NOT NULL AND next_inspection <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY next_inspection")->fetchAll();

require_once __DIR__ . '/../partials/header.php';
?>
<h2>Facilities</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<h3>Classrooms</h3>
<form method="post" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="add_classroom" />
  <div class="field"><label>Building</label><input name="building" required /></div>
  <div class="field"><label>Room</label><input name="room_number" required /></div>
  <div class="field"><label>Capacity</label><input type="number" name="capacity" min="1" required /></div>
  <div style="grid-column:1/4"><button class="btn" type="submit">Add Classroom</button></div>
</form>
<table>
  <tr><th>Building</th><th>Room</th><th>Capacity</th></tr>
  <?php foreach ($classrooms as $c): ?>
    <tr>
      <td><?= e($c['building']) ?></td><td><?= e($c['room_number']) ?></td><td><?= (int)$c['capacity'] ?></td>
    </tr>
  <?php endforeach; ?>
</table>

<h3>Labs</h3>
<form method="post" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="add_lab" />
  <div class="field"><label>Name</label><input name="name" required /></div>
  <div class="field"><label>Building</label><input name="building" required /></div>
  <div class="field"><label>Capacity</label><input type="number" name="capacity" min="1" required /></div>
  <div style="grid-column:1/4"><button class="btn" type="submit">Add Lab</button></div>
</form>

<h3>Lab Maintenance</h3>
<form method="post" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="add_maintenance" />
  <div class="field"><label>Lab</label>
    <select name="lab_id"><?php foreach ($labs as $l): ?><option value="<?= (int)$l['id'] ?>"><?= e($l['name']) ?></option><?php endforeach; ?></select>
  </div>
  <div class="field"><label>Description</label><input name="description" required /></div>
  <div class="field"><label>Date</label><input type="date" name="maintenance_date" value="<?= e(date('Y-m-d')) ?>" /></div>
  <div class="field"><label>Next Check</label><input type="date" name="next_check" /></div>
  <div style="grid-column:1/5"><button class="btn" type="submit">Add Maintenance</button></div>
</form>

<table>
  <tr><th>Lab</th><th>Description</th><th>Date</th><th>Next Check</th><th>Action</th></tr>
  <?php foreach ($maintenance as $m): ?>
    <tr>
      <td><?= e($m['name']) ?></td><td><?= e($m['description']) ?></td><td><?= e($m['maintenance_date']) ?></td><td><?= e($m['next_check']) ?></td>
      <td>
        <form method="post" style="display:inline;">
          <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
          <input type="hidden" name="action" value="delete_maintenance" />
          <input type="hidden" name="id" value="<?= (int)$m['id'] ?>" />
          <button class="btn danger" onclick="return confirm('Delete?');">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<h3>Report: Classrooms per Building</h3>
<table>
  <tr><th>Building</th><th>Rooms</th></tr>
  <?php foreach ($roomsByBuilding as $rb): ?>
    <tr><td><?= e($rb['building']) ?></td><td><?= (int)$rb['cnt'] ?></td></tr>
  <?php endforeach; ?>
</table>

<h3>Report: Equipment Due for Inspection (7 days)</h3>
<table>
  <tr><th>Name</th><th>Location</th><th>Next Inspection</th></tr>
  <?php foreach ($dueSoon as $d): ?>
    <tr><td><?= e($d['name']) ?></td><td><?= e($d['location']) ?></td><td><?= e($d['next_inspection']) ?></td></tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
