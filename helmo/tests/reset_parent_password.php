<?php
require_once __DIR__ . '/../lib/db.php';

echo '<pre>';
try {
    $pdo = DB::conn();
    echo "DB connection: OK\n";

    $pdo->beginTransaction();
    // Ensure Parent role exists
    $roleId = $pdo->query("SELECT id FROM roles WHERE name='Parent' LIMIT 1")->fetchColumn();
    if (!$roleId) {
        $pdo->exec("INSERT INTO roles(name) VALUES ('Parent')");
        $roleId = $pdo->lastInsertId();
        echo "Created missing role 'Parent' (id=$roleId)\n";
    }

    // Ensure parent1 user exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute(['parent1']);
    $parentId = $stmt->fetchColumn();
    if (!$parentId) {
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $ins = $pdo->prepare('INSERT INTO users (username, password_hash, role_id) VALUES (?,?,?)');
        $ins->execute(['parent1', $hash, $roleId]);
        $parentId = $pdo->lastInsertId();
        echo "Created parent1 user with new hash. ID=$parentId\n";
        echo "Hash length: ".strlen($hash)."\n";
    } else {
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $upd = $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');
        $upd->execute([$hash, $parentId]);
        echo "Updated parent1 password hash. ID=$parentId\n";
        echo "Hash length: ".strlen($hash)."\n";
    }

    $pdo->commit();
    echo "Done. Try login with parent1 / password.\n";
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    echo 'ERROR: '.$e->getMessage()."\n";
}
echo '</pre>';
?>
