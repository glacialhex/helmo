<?php
require_once __DIR__ . '/../lib/db.php';
echo "<pre>";
try {
  $pdo = DB::conn();
  echo "DB connection: OK\n";
  $cnt = $pdo->query('SELECT COUNT(*) AS c FROM users')->fetch();
  echo "Users count: ".$cnt['c']."\n";
  $u = $pdo->prepare('SELECT username, password_hash FROM users WHERE username = ?');
  $u->execute(['admin']);
  $admin = $u->fetch();
  if (!$admin) {
    echo "Admin user not found.\n";
  } else {
    echo "Admin found. Hash prefix: ".substr($admin['password_hash'], 0, 4)."\n";
    $ok = password_verify('password', $admin['password_hash']);
    echo "password_verify('password', hash): ".($ok ? 'TRUE' : 'FALSE')."\n";
  }
} catch (Throwable $e) {
  echo "ERROR: ".$e->getMessage()."\n";
}
echo "</pre>";
?>
