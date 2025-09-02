<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/i18n.php';

I18n::start();
Auth::start();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!CSRF::validate($token)) {
        http_response_code(400);
        $error = 'Bad CSRF token';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        if ($username === '' || $password === '') {
            $error = I18n::t('invalid_credentials');
        } else if (Auth::login($username, $password)) {
            header('Location: /dashboard.php');
            exit;
        } else {
            $error = I18n::t('invalid_credentials');
        }
    }
}
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>
<h2><?= I18n::t('login') ?></h2>
<?php if ($error): ?>
  <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form method="post" action="">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CSRF::token()) ?>" />
  <div class="field">
    <label><?= I18n::t('username') ?></label>
    <input type="text" name="username" required />
  </div>
  <div class="field">
    <label><?= I18n::t('password') ?></label>
    <input type="password" name="password" required />
  </div>
  <button class="btn" type="submit"><?= I18n::t('submit') ?></button>
  <a class="btn secondary" href="/index.php">Home</a>
  <p style="margin-top:10px;color:#6b7280;">Demo login: admin/password</p>
  </form>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
