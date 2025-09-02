<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/util.php';
Auth::start();
Auth::requireRole(['Admin']);
$pdo = DB::conn();

$error = '';
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editFee = null;
if ($editId) { $st=$pdo->prepare('SELECT * FROM fees WHERE id=?'); $st->execute([$editId]); $editFee=$st->fetch(); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $action = $_POST['action'] ?? '';
  if ($action === 'create_fee' || $action === 'update_fee') {
        $name = trim($_POST['name'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $due = $_POST['due_date'] ?? '';
        $grade = trim($_POST['grade_level'] ?? '');
        if ($name && $amount > 0 && $due) {
      if ($action === 'create_fee') {
        $pdo->prepare('INSERT INTO fees (name, amount, due_date, grade_level) VALUES (?,?,?,?)')->execute([$name,$amount,$due,$grade?:null]);
      } else {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('UPDATE fees SET name=?, amount=?, due_date=?, grade_level=? WHERE id=?')->execute([$name,$amount,$due,$grade?:null,$id]);
      }
        } else { $error = 'Invalid fee input.'; }
    } elseif ($action === 'create_installments') {
        $fee_id = (int)($_POST['fee_id'] ?? 0);
        $student_id = (int)($_POST['student_id'] ?? 0);
        $count = max(1, (int)($_POST['count'] ?? 1));
        $total = (float)($_POST['total_amount'] ?? 0);
        if ($total <= 0) { $error = 'Invalid total amount.'; }
        else {
            $per = round($total / $count, 2);
            for ($i=1; $i<=$count; $i++) {
                $due = (new DateTime($_POST['first_due'] ?? date('Y-m-d')))->modify("+".($i-1)." month")->format('Y-m-d');
                $pdo->prepare('INSERT INTO fee_installments (fee_id, student_id, installment_no, amount, due_date) VALUES (?,?,?,?,?)')
                    ->execute([$fee_id,$student_id,$i,$per,$due]);
            }
        }
    }
}

$fees = $pdo->query('SELECT * FROM fees ORDER BY id DESC')->fetchAll();
$students = $pdo->query('SELECT id, first_name, last_name FROM students ORDER BY first_name')->fetchAll();
$unpaid = $pdo->query('SELECT s.id, CONCAT(s.first_name, " ", s.last_name) AS name, SUM(fi.amount * (1 - fi.paid)) AS balance
FROM students s LEFT JOIN fee_installments fi ON fi.student_id = s.id
GROUP BY s.id HAVING balance > 0 OR balance IS NULL ORDER BY balance DESC')->fetchAll();

require_once __DIR__ . '/../partials/header.php';
?>
<h2>Fees</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<h3><?= $editFee ? 'Edit Fee' : 'Create Fee' ?></h3>
<form method="post" style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="<?= $editFee ? 'update_fee' : 'create_fee' ?>" />
  <?php if ($editFee): ?><input type="hidden" name="id" value="<?= (int)$editFee['id'] ?>" /><?php endif; ?>
  <div class="field"><label>Name</label><input name="name" value="<?= e($editFee['name'] ?? '') ?>" required /></div>
  <div class="field"><label>Amount</label><input type="number" step="0.01" min="0.01" name="amount" value="<?= e($editFee['amount'] ?? '') ?>" required /></div>
  <div class="field"><label>Due Date</label><input type="date" name="due_date" value="<?= e($editFee['due_date'] ?? '') ?>" required /></div>
  <div class="field"><label>Grade (optional)</label><input name="grade_level" value="<?= e($editFee['grade_level'] ?? '') ?>" /></div>
  <div style="grid-column:1/5"><button class="btn" type="submit"><?= $editFee ? 'Update' : 'Add Fee' ?></button> <?php if ($editFee): ?><a class="btn secondary" href="/fees/index.php">Cancel</a><?php endif; ?></div>
</form>

<h3>Create Installment Plan</h3>
<form method="post" style="display:grid;grid-template-columns:repeat(5,1fr);gap:17px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="create_installments" />
  <div class="field"><label>Fee</label>
    <select name="fee_id">
      <?php foreach ($fees as $f): ?><option value="<?= (int)$f['id'] ?>"><?= e($f['name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="field"><label>Student</label>
    <select name="student_id">
      <?php foreach ($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['first_name'].' '.$s['last_name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="field"><label>Installments</label><input type="number" name="count" min="1" value="3" /></div>
  <div class="field"><label>Total Amount</label><input type="number" name="total_amount" min="0.01" step="0.01" required /></div>
  <div class="field"><label>First Due</label><input type="date" name="first_due" value="<?= e(date('Y-m-d')) ?>" /></div>
  <div style="grid-column:1/6"><button class="btn" type="submit">Create Plan</button></div>
</form>

<h3>Report: Unpaid Balances</h3>
<table>
  <tr><th>Student</th><th>Unpaid Balance</th></tr>
  <?php foreach ($unpaid as $u): ?>
    <tr>
      <td><?= e($u['name']) ?></td>
      <td><?= number_format((float)$u['balance'], 2) ?> EGP</td>
    </tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
