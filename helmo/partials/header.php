<?php
require_once __DIR__ . '/../lib/i18n.php';
I18n::start();
$config = require __DIR__ . '/../config.php';
$title = $config['app']['name'];
$lang = $_SESSION['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($title) ?></title>
  <style>
    body { font-family: Arial, sans-serif; margin:0; background:#f7f7f9; color:#222; }
    header { background:#1f2937; color:#fff; padding:12px 16px; display:flex; align-items:center; justify-content:space-between; }
    header .brand { font-weight:bold; }
    nav a { color:#fff; margin:0 8px; text-decoration:none; }
    .container { max-width:1000px; margin:20px auto; background:#fff; padding:20px; border-radius:8px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    .btn { display:inline-block; padding:8px 12px; background:#2563eb; color:#fff; border:none; border-radius:6px; text-decoration:none; cursor:pointer; }
    .btn.secondary { background:#6b7280; }
    .btn.danger { background:#dc2626; }
    .field { margin-bottom:12px; }
    label { display:block; margin-bottom:4px; }
    input, select, textarea { width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:8px; border-bottom:1px solid #e5e7eb; text-align: left; }
    th { background:#f3f4f6; }
    .alert { padding:8px 12px; border-radius:6px; margin-bottom:12px; }
    .alert.error { background:#fee2e2; color:#991b1b; }
    .alert.success { background:#dcfce7; color:#14532d; }
    .lang-switch { margin-left:12px; }
  </style>
</head>
<body>
  <header>
    <div class="brand"><?= htmlspecialchars($title) ?></div>
    <nav>
      <a href="/index.php">Home</a>
      <a href="/dashboard.php">Dashboard</a>
      <a href="/logout.php"><?= I18n::t('logout') ?></a>
      <span class="lang-switch">
        <a class="btn secondary" href="?lang=en">EN</a>
        <a class="btn secondary" href="?lang=ar">AR</a>
      </span>
    </nav>
  </header>
  <div class="container">
