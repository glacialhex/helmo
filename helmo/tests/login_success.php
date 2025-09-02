<?php
require_once __DIR__ . '/../lib/auth.php';
Auth::start();
// Manual test instructions: open /login.php, login admin/password, expect redirect to dashboard
echo 'Open /login.php, use admin/password. Expect success.';
