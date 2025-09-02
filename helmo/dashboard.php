<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/i18n.php';
Auth::start();
$user = Auth::user();
if (!$user) { header('Location: /login.php'); exit; }
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>
<h2><?= I18n::t('dashboard') ?></h2>
<p>Welcome, <strong><?= htmlspecialchars($user['username']) ?></strong> (<?= htmlspecialchars($user['role']) ?>)</p>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
  <a class="btn" href="/students/index.php">Students</a>
  <a class="btn" href="/courses/index.php">Courses</a>
  <a class="btn" href="/enrollments/index.php">Enrollments</a>
  <a class="btn" href="/attendance/index.php">Attendance</a>
  <a class="btn" href="/fees/index.php">Fees</a>
  <a class="btn" href="/receipts/index.php">Receipts</a>
  <a class="btn" href="/payments/index.php">Online Payments</a>
  <a class="btn" href="/reports/index.php">Reports</a>
  <a class="btn" href="/library/index.php">Library</a>
  <a class="btn" href="/transport/index.php">Transport</a>
  <a class="btn" href="/communication/index.php">Communication</a>
  <a class="btn" href="/grades/index.php">Grades</a>
  <a class="btn" href="/highered/index.php">Higher Ed</a>
  <a class="btn" href="/facilities/index.php">Facilities</a>
  <a class="btn" href="/eav/index.php">Custom Fields</a>
  <a class="btn" href="/homework/index.php">Homework</a>
  <a class="btn" href="/files/index.php">Files</a>
  <a class="btn" href="/safety/index.php">Safety</a>
  <a class="btn" href="/admin/users.php">Users & Roles</a>
  <a class="btn secondary" href="/feedback.php">Feedback</a>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
