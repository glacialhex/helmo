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
    if ($action === 'add_attr') {
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['data_type'] ?? 'text';
        if ($name && in_array($type, ['text','number','date','bool'], true)) {
            $pdo->prepare("INSERT INTO eav_attributes (entity_type, name, data_type) VALUES ('student', ?, ?)")->execute([$name,$type]);
        } else { $error = 'Invalid attribute.'; }
    } elseif ($action === 'save_value') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $attribute_id = (int)($_POST['attribute_id'] ?? 0);
        $type = $_POST['data_type'] ?? 'text';
        $value = $_POST['value'] ?? '';
        $cols = ['value_text'=>null,'value_number'=>null,'value_date'=>null,'value_bool'=>null];
        if ($type==='number') $cols['value_number'] = (float)$value;
        elseif ($type==='date') $cols['value_date'] = $value;
        elseif ($type==='bool') $cols['value_bool'] = $value==='1'?1:0;
        else $cols['value_text'] = trim($value);
        $pdo->prepare('INSERT INTO eav_values (entity_type, entity_id, attribute_id, value_text, value_number, value_date, value_bool) VALUES ("student",?,?,?,?,?,?) ON DUPLICATE KEY UPDATE value_text=VALUES(value_text), value_number=VALUES(value_number), value_date=VALUES(value_date), value_bool=VALUES(value_bool)')
            ->execute(['student',$student_id,$attribute_id,$cols['value_text'],$cols['value_number'],$cols['value_date'],$cols['value_bool']]);
    }
}

$attrs = $pdo->query("SELECT * FROM eav_attributes WHERE entity_type='student' ORDER BY name")->fetchAll();
$students = $pdo->query('SELECT id, first_name, last_name FROM students ORDER BY first_name')->fetchAll();
$values = $pdo->query("SELECT ev.*, ea.name, ea.data_type, CONCAT(s.first_name,' ',s.last_name) AS student FROM eav_values ev JOIN eav_attributes ea ON ea.id=ev.attribute_id JOIN students s ON s.id=ev.entity_id WHERE ev.entity_type='student' ORDER BY s.first_name, ea.name")->fetchAll();

require_once __DIR__ . '/../partials/header.php';
?>
<h2>Custom Fields (EAV)</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<h3>Add Attribute</h3>
<form method="post" style="display:flex;gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="add_attr" />
  <div class="field"><label>Name</label><input name="name" required /></div>
  <div class="field"><label>Type</label>
    <select name="data_type"><option>text</option><option>number</option><option>date</option><option>bool</option></select>
  </div>
  <button class="btn" type="submit">Add</button>
</form>

<h3>Set Value</h3>
<form method="post" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="save_value" />
  <div class="field"><label>Student</label><select name="student_id"><?php foreach ($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['first_name'].' '.$s['last_name']) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label>Attribute</label><select name="attribute_id" id="attrSelect" onchange="updateType()"><?php foreach ($attrs as $a): ?><option value="<?= (int)$a['id'] ?>" data-type="<?= e($a['data_type']) ?>"><?= e($a['name']) ?></option><?php endforeach; ?></select></div>
  <input type="hidden" name="data_type" id="dataType" value="<?= e($attrs[0]['data_type'] ?? 'text') ?>" />
  <div class="field" id="valueField"><label>Value</label><input name="value" /></div>
  <div style="grid-column:1/5"><button class="btn" type="submit">Save</button></div>
</form>

<script>
function updateType(){
  const sel = document.getElementById('attrSelect');
  const type = sel.options[sel.selectedIndex]?.dataset.type || 'text';
  document.getElementById('dataType').value = type;
  const vf = document.getElementById('valueField');
  let input = '<input name="value" />';
  if (type==='number') input = '<input type="number" step="0.01" name="value" />';
  if (type==='date') input = '<input type="date" name="value" />';
  if (type==='bool') input = '<select name="value"><option value="1">Yes</option><option value="0">No</option></select>';
  vf.innerHTML = '<label>Value</label>'+input;
}
</script>

<h3>Report: Custom Attributes</h3>
<table>
  <tr><th>Student</th><th>Attribute</th><th>Value</th></tr>
  <?php foreach ($values as $v): ?>
    <?php $val = $v['value_text'] ?? ($v['value_number'] ?? ($v['value_date'] ?? ($v['value_bool']!==null?($v['value_bool']?'Yes':'No'):''))); ?>
    <tr><td><?= e($v['student']) ?></td><td><?= e($v['name']) ?></td><td><?= e((string)$val) ?></td></tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
