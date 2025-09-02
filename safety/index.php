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
    if ($action === 'add_equipment') {
        $name = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $next = $_POST['next_inspection'] ?? null;
        if ($name && $location) {
            $pdo->prepare('INSERT INTO equipment (name, location, next_inspection) VALUES (?,?,?)')->execute([$name,$location,$next]);
        } else { $error = 'Invalid equipment.'; }
    } elseif ($action === 'add_inspection') {
        $eid = (int)($_POST['equipment_id'] ?? 0);
        $on = $_POST['inspected_on'] ?? date('Y-m-d');
        $notes = trim($_POST['notes'] ?? '');
        if ($eid>0) {
            $pdo->prepare('INSERT INTO equipment_inspections (equipment_id, inspected_on, notes) VALUES (?,?,?)')->execute([$eid,$on,$notes]);
        } else { $error = 'Invalid inspection.'; }
    } elseif ($action === 'add_incident') {
        $eid = (int)($_POST['equipment_id'] ?? 0) ?: null;
        $desc = trim($_POST['description'] ?? '');
        if ($desc) {
            $pdo->prepare('INSERT INTO incident_logs (equipment_id, description) VALUES (?,?)')->execute([$eid,$desc]);
        } else { $error = 'Invalid incident.'; }
    }
}

$equip = $pdo->query('SELECT * FROM equipment ORDER BY location, name')->fetchAll();
$inspections = $pdo->query('SELECT ei.*, e.name, e.location FROM equipment_inspections ei JOIN equipment e ON e.id=ei.equipment_id ORDER BY ei.inspected_on DESC')->fetchAll();
$incidents = $pdo->query('SELECT il.*, e.name FROM incident_logs il LEFT JOIN equipment e ON e.id=il.equipment_id ORDER BY il.id DESC')->fetchAll();

require_once __DIR__ . '/../partials/header.php';
?>
<h2>Safety Equipment</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<h3>Add Equipment</h3>
<form method="post" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="add_equipment" />
  <div class="field"><label>Name</label><input name="name" required /></div>
  <div class="field"><label>Location</label><input name="location" required /></div>
  <div class="field"><label>Next Inspection</label><input type="date" name="next_inspection" /></div>
  <div style="grid-column:1/4"><button class="btn" type="submit">Add</button></div>
</form>

<h3>Equipment List</h3>
<table>
  <tr><th>Name</th><th>Location</th><th>Next Inspection</th></tr>
  <?php foreach ($equip as $e): ?>
    <tr><td><?= e($e['name']) ?></td><td><?= e($e['location']) ?></td><td><?= e($e['next_inspection']) ?></td></tr>
  <?php endforeach; ?>
</table>

<h3>Log Inspection</h3>
<form method="post" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="add_inspection" />
  <div class="field"><label>Equipment</label><select name="equipment_id"><?php foreach ($equip as $e): ?><option value="<?= (int)$e['id'] ?>"><?= e($e['name'].' - '.$e['location']) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label>Date</label><input type="date" name="inspected_on" value="<?= e(date('Y-m-d')) ?>" /></div>
  <div class="field"><label>Notes</label><input name="notes" /></div>
  <div style="grid-column:1/4"><button class="btn" type="submit">Save</button></div>
</form>

<h3>Inspections</h3>
<table>
  <tr><th>Equipment</th><th>Location</th><th>Date</th><th>Notes</th></tr>
  <?php foreach ($inspections as $i): ?>
    <tr><td><?= e($i['name']) ?></td><td><?= e($i['location']) ?></td><td><?= e($i['inspected_on']) ?></td><td><?= e($i['notes']) ?></td></tr>
  <?php endforeach; ?>
</table>

<h3>Incident Logs</h3>
<form method="post" style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="add_incident" />
  <div class="field"><label>Equipment (optional)</label><select name="equipment_id"><option value="">None</option><?php foreach ($equip as $e): ?><option value="<?= (int)$e['id'] ?>"><?= e($e['name']) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label>Description</label><input name="description" required /></div>
  <div style="grid-column:1/3"><button class="btn" type="submit">Log Incident</button></div>
</form>

<table>
  <tr><th>ID</th><th>Equipment</th><th>Description</th><th>At</th></tr>
  <?php foreach ($incidents as $il): ?>
    <tr><td><?= (int)$il['id'] ?></td><td><?= e($il['name'] ?? 'N/A') ?></td><td><?= e($il['description']) ?></td><td><?= e($il['logged_at']) ?></td></tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
