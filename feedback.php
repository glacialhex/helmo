<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/util.php';
Auth::start();
$pdo = DB::conn();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $user = Auth::user();
    $uid = $user['id'] ?? null;
    $message = trim($_POST['message'] ?? '');
    if ($message) {
        $stmt = $pdo->prepare('INSERT INTO feedback (user_id, message) VALUES (?,?)');
        $stmt->execute([$uid, $message]);
        $msg = 'Thank you for the feedback!';
    }
}
require_once __DIR__ . '/partials/header.php';
?>
<h2>Feedback</h2>
<?php if ($msg): ?><div class="alert success"><?= e($msg) ?></div><?php endif; ?>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= e(CSRF::token()) ?>" />
  <div class="field"><label>Your feedback</label><textarea name="message" required></textarea></div>
  <button class="btn" type="submit">Send</button>
</form>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
