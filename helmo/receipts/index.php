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
    $student_id = (int)($_POST['student_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $details = trim($_POST['details'] ?? '');
    if ($student_id > 0 && $amount > 0) {
        $pdo->prepare('INSERT INTO receipts (student_id, amount, details) VALUES (?,?,?)')->execute([$student_id,$amount,$details]);
        redirect('/receipts/index.php');
    } else { $error = 'Invalid receipt input.'; }
}

$students = $pdo->query('SELECT id, first_name, last_name FROM students ORDER BY first_name')->fetchAll();
$receipts = $pdo->query('SELECT r.*, CONCAT(s.first_name, " ", s.last_name) AS student FROM receipts r JOIN students s ON s.id = r.student_id ORDER BY r.id DESC')->fetchAll();

require_once __DIR__ . '/../partials/header.php';
?>
<h2>Receipts</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<form method="post" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <div class="field"><label>Student</label>
    <select name="student_id">
      <?php foreach ($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['first_name'].' '.$s['last_name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="field"><label>Amount</label><input type="number" step="0.01" min="0.01" name="amount" required /></div>
  <div class="field" style="grid-column:1/5"><label>Details</label><input name="details" /></div>
  <div style="grid-column:1/5"><button class="btn" type="submit">Create Receipt</button></div>
</form>

<h3>All Receipts</h3>
<table>
  <tr><th>ID</th><th>Student</th><th>Amount</th><th>Issued</th><th>Actions</th></tr>
  <?php foreach ($receipts as $r): ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td><?= e($r['student']) ?></td>
      <td><?= number_format((float)$r['amount'],2) ?> <?= e($r['currency']) ?></td>
      <td><?= e($r['issued_at']) ?></td>
      <td><a class="btn" href="/receipts/view.php?id=<?= (int)$r['id'] ?>" target="_blank">Print</a></td>
    </tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
