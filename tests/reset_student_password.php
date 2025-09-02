<?php
require_once __DIR__ . '/../lib/db.php';

echo '<pre>';
try {
    $pdo = DB::conn();
    echo "DB connection: OK\n";

    $pdo->beginTransaction();
    // Ensure Student role exists
    $roleId = $pdo->query("SELECT id FROM roles WHERE name='Student' LIMIT 1")->fetchColumn();
    if (!$roleId) {
        $pdo->exec("INSERT INTO roles(name) VALUES ('Student')");
        $roleId = $pdo->lastInsertId();
        echo "Created missing role 'Student' (id=$roleId)\n";
    }

    // Ensure student1 user exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute(['student1']);
    $studentId = $stmt->fetchColumn();
    if (!$studentId) {
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $ins = $pdo->prepare('INSERT INTO users (username, password_hash, role_id) VALUES (?,?,?)');
        $ins->execute(['student1', $hash, $roleId]);
        $studentId = $pdo->lastInsertId();
        echo "Created student1 user with new hash. ID=$studentId\n";
        echo "Hash length: ".strlen($hash)."\n";
    } else {
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $upd = $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');
        $upd->execute([$hash, $studentId]);
        echo "Updated student1 password hash. ID=$studentId\n";
        echo "Hash length: ".strlen($hash)."\n";
    }

    $pdo->commit();
    echo "Done. Try login with student1 / password.\n";
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    echo 'ERROR: '.$e->getMessage()."\n";
}
echo '</pre>';
?>
