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
    if ($action === 'create_appt') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $parent = trim($_POST['parent_name'] ?? '');
        $when = $_POST['scheduled_at'] ?? '';
        $notes = trim($_POST['notes'] ?? '');
    if ($student_id>0 && $parent && $when) {
      $pdo->prepare('INSERT INTO appointments (student_id,parent_name,scheduled_at,notes) VALUES (?,?,?,?)')->execute([$student_id,$parent,$when,$notes]);
      $msg = 'Appointment booked for '.$parent.' at '.$when;
      $pdo->prepare("INSERT INTO sms_logs (message, recipients, status) VALUES (?,?, 'sent')")->execute([$msg, 'parent']);
        } else { $error = 'Invalid appointment.'; }
    } elseif ($action === 'send_sms') {
        $message = trim($_POST['message'] ?? '');
        $recipients = trim($_POST['recipients'] ?? '');
        if ($message && $recipients) {
            // Demo only: integrate SMS API in production
            $pdo->prepare("INSERT INTO sms_logs (message, recipients, status) VALUES (?,?, 'sent')")->execute([$message,$recipients]);
        } else { $error = 'Message and recipients required.'; }
    }
}

$students = $pdo->query('SELECT id, first_name, last_name FROM students ORDER BY first_name')->fetchAll();
$appts = $pdo->query('SELECT a.*, CONCAT(s.first_name, " ", s.last_name) student FROM appointments a JOIN students s ON s.id=a.student_id ORDER BY a.scheduled_at DESC')->fetchAll();
$sms = $pdo->query('SELECT * FROM sms_logs ORDER BY id DESC')->fetchAll();

require_once __DIR__ . '/../partials/header.php';
?>
<h2>Communication</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<h3>Parent Appointments</h3>
<form method="post" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;align-items:end;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="create_appt" />
  <div class="field"><label>Student</label>
    <select name="student_id"><?php foreach ($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['first_name'].' '.$s['last_name']) ?></option><?php endforeach; ?></select>
  </div>
  <div class="field"><label>Parent</label><input name="parent_name" required /></div>
  <div class="field"><label>When</label><input type="datetime-local" name="scheduled_at" required /></div>
  <div class="field" style="grid-column:1/5"><label>Notes</label><input name="notes" /></div>
  <div style="grid-column:1/5"><button class="btn" type="submit">Book</button></div>
</form>

<table>
  <tr><th>Student</th><th>Parent</th><th>When</th><th>Notes</th></tr>
  <?php foreach ($appts as $a): ?>
    <tr><td><?= e($a['student']) ?></td><td><?= e($a['parent_name']) ?></td><td><?= e($a['scheduled_at']) ?></td><td><?= e($a['notes']) ?></td></tr>
  <?php endforeach; ?>
</table>

<h3>Bulk SMS</h3>
<form method="post" style="display:grid;grid-template-columns:1fr;gap:12px;">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <input type="hidden" name="action" value="send_sms" />
  <div class="field"><label>Message</label><textarea name="message" required></textarea></div>
  <div class="field"><label>Recipients (comma-separated phone numbers)</label><input name="recipients" required /></div>
  <div><button class="btn" type="submit">Send</button></div>
</form>

<h3>SMS Logs</h3>
<table>
  <tr><th>Message</th><th>Recipients</th><th>Status</th><th>At</th></tr>
  <?php foreach ($sms as $s): ?>
    <tr><td><?= e($s['message']) ?></td><td><?= e($s['recipients']) ?></td><td><?= e($s['status']) ?></td><td><?= e($s['created_at']) ?></td></tr>
  <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
