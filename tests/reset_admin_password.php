<?php
require_once __DIR__ . '/../lib/db.php';

echo '<pre>';
try {
    $pdo = DB::conn();
    echo "DB connection: OK\n";

    $pdo->beginTransaction();
    // Ensure Admin role exists
    $roleId = $pdo->query("SELECT id FROM roles WHERE name='Admin' LIMIT 1")->fetchColumn();
    if (!$roleId) {
        $pdo->exec("INSERT INTO roles(name) VALUES ('Admin')");
        $roleId = $pdo->lastInsertId();
        echo "Created missing role 'Admin' (id=$roleId)\n";
    }

    // Ensure admin user exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute(['admin']);
    $adminId = $stmt->fetchColumn();
    if (!$adminId) {
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $ins = $pdo->prepare('INSERT INTO users (username, password_hash, role_id) VALUES (?,?,?)');
        $ins->execute(['admin', $hash, $roleId]);
        $adminId = $pdo->lastInsertId();
        echo "Created admin user with new hash. ID=$adminId\n";
        echo "Hash length: ".strlen($hash)."\n";
    } else {
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $upd = $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');
        $upd->execute([$hash, $adminId]);
        echo "Updated admin password hash. ID=$adminId\n";
        echo "Hash length: ".strlen($hash)."\n";
    }

    $pdo->commit();
    echo "Done. Try login with admin / password.\n";
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    echo 'ERROR: '.$e->getMessage()."\n";
}
echo '</pre>';
?>
