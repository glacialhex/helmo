<?php
require_once __DIR__ . '/../lib/db.php';

echo '<pre>';
try {
    $pdo = DB::conn();
    echo "DB connection: OK\n";

    $pdo->beginTransaction();
    // Ensure Teacher role exists
    $roleId = $pdo->query("SELECT id FROM roles WHERE name='Teacher' LIMIT 1")->fetchColumn();
    if (!$roleId) {
        $pdo->exec("INSERT INTO roles(name) VALUES ('Teacher')");
        $roleId = $pdo->lastInsertId();
        echo "Created missing role 'Teacher' (id=$roleId)\n";
    }

    // Ensure teacher1 user exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute(['teacher1']);
    $teacherId = $stmt->fetchColumn();
    if (!$teacherId) {
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $ins = $pdo->prepare('INSERT INTO users (username, password_hash, role_id) VALUES (?,?,?)');
        $ins->execute(['teacher1', $hash, $roleId]);
        $teacherId = $pdo->lastInsertId();
        echo "Created teacher1 user with new hash. ID=$teacherId\n";
        echo "Hash length: ".strlen($hash)."\n";
    } else {
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $upd = $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');
        $upd->execute([$hash, $teacherId]);
        echo "Updated teacher1 password hash. ID=$teacherId\n";
        echo "Hash length: ".strlen($hash)."\n";
    }

    $pdo->commit();
    echo "Done. Try login with teacher1 / password.\n";
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    echo 'ERROR: '.$e->getMessage()."\n";
}
echo '</pre>';
?>
