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
    if ($action === 'create_tx') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $provider = $_POST['provider'] === 'Vodafone' ? 'Vodafone' : 'Fawry';
        $reference = 'TX'.time().rand(100,999);
        $amount = (float)($_POST['amount'] ?? 0);
        if ($student_id>0 && $amount>0) {
            $pdo->prepare('INSERT INTO transactions (student_id, provider, reference, amount) VALUES (?,?,?,?)')->execute([$student_id,$provider,$reference,$amount]);
        } else { $error = 'Invalid transaction.'; }
  } elseif ($action === 'confirm_tx') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("UPDATE transactions SET status='confirmed', confirmed_at=NOW() WHERE id=?")->execute([$id]);
    // Notify via SMS (demo)
    $tx = $pdo->prepare('SELECT t.*, CONCAT(s.first_name, " ", s.last_name) AS student FROM transactions t JOIN students s ON s.id=t.student_id WHERE t.id=?');
    $tx->execute([$id]);
    if ($row = $tx->fetch()) {
      $msg = 'Payment confirmed for '.$row['student'].' / Ref '.$row['reference'].' / Amount '.number_format((float)$row['amount'],2);
      $pdo->prepare("INSERT INTO sms_logs (message, recipients, status) VALUES (?,?, 'sent')")->execute([$msg, 'student']);
    }
  }
}

$students = $pdo->query('SELECT id, first_name, last_name FROM students ORDER BY first_name')->fetchAll();
$txs = $pdo->query('SELECT t.*, CONCAT(s.first_name, " ", s.last_name) AS student FROM transactions t JOIN students s ON s.id = t.student_id ORDER BY t.id DESC')->fetchAll();
$daily = $pdo->query("SELECT DATE(created_at) dt, COUNT(*) cnt, SUM(amount) sum FROM transactions GROUP BY DATE(created_at) ORDER BY dt DESC")->fetchAll();

require_once __DIR__ . '/../partials/header.php';
?>
<h2>Online Payments</h2>
<p style="color:#6b7280;">Demo only: integrate Fawry/Vodafone SDKs or APIs for production.</p>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<h3>Create Transaction (Demo)</h3>
<form method="post" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="create_tx" />
  <div class="field"><label>Student</label>
    <select name="student_id">
      <?php foreach ($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['first_name'].' '.$s['last_name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="field"><label>Provider</label>
    <select name="provider"><option>Fawry</option><option>Vodafone</option></select>
  </div>
  <div class="field"><label>Amount</label><input type="number" step="0.01" min="0.01" name="amount" required /></div>
  <div><button class="btn" type="submit">Create</button></div>
  </form>

<h3>Transactions</h3>
<table>
  <tr><th>ID</th><th>Student</th><th>Provider</th><th>Reference</th><th>Amount</th><th>Status</th><th>Actions</th></tr>
  <?php foreach ($txs as $t): ?>
    <tr>
      <td><?= (int)$t['id'] ?></td>
      <td><?= e($t['student']) ?></td>
      <td><?= e($t['provider']) ?></td>
      <td><?= e($t['reference']) ?></td>
      <td><?= number_format((float)$t['amount'],2) ?></td>
      <td><?= e($t['status']) ?></td>
      <td>
        <?php if ($t['status'] !== 'confirmed'): ?>
        <form method="post" style="display:inline;">
          <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
          <input type="hidden" name="action" value="confirm_tx" />
          <input type="hidden" name="id" value="<?= (int)$t['id'] ?>" />
          <button class="btn">Confirm</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<h3>Daily Transactions</h3>
<table>
  <tr><th>Date</th><th>Count</th><th>Sum</th></tr>
  <?php foreach ($daily as $d): ?>
    <tr><td><?= e($d['dt']) ?></td><td><?= (int)$d['cnt'] ?></td><td><?= number_format((float)$d['sum'],2) ?></td></tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
