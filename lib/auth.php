<?php
require_once __DIR__ . '/db.php';

class Auth {
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(string $username, string $password): bool {
        self::start();
        $pdo = DB::conn();
        $stmt = $pdo->prepare('SELECT u.id, u.username, u.password_hash, r.name AS role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if (!$user) return false;
        if (!password_verify($password, $user['password_hash'])) return false;

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];
        return true;
    }

    public static function logout(): void {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function user(): ?array {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    public static function requireRole(array $roles): void {
        $u = self::user();
        if (!$u || !in_array($u['role'], $roles, true)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }
}
