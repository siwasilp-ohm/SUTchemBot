<?php
require_once __DIR__ . '/db.php';

function pro_roles(): array {
    return ['admin', 'ceo', 'lab_manager', 'user'];
}

function pro_permissions(): array {
    return [
        'dashboard' => ['admin', 'ceo', 'lab_manager', 'user'],
        'chemicals.view' => ['admin', 'ceo', 'lab_manager', 'user'],
        'chemicals.transact' => ['admin', 'lab_manager', 'user'],
        'barcodes.view' => ['admin', 'ceo', 'lab_manager', 'user'],
        'ar.view' => ['admin', 'ceo', 'lab_manager', 'user'],
    ];
}

function pro_login(string $username, string $password): bool {
    $username = trim($username);
    if ($username === '' || $password === '') {
        return false;
    }

    $stmt = pro_db()->prepare('SELECT id, username, role, full_name, password_hash FROM pro_users WHERE username = :username AND is_active = 1');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['pro_user'] = [
        'id' => (int) $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
        'full_name' => $user['full_name'],
    ];

    return true;
}

function pro_user(): ?array {
    return $_SESSION['pro_user'] ?? null;
}

function pro_can(string $permission): bool {
    $user = pro_user();
    if (!$user) {
        return false;
    }

    $permissions = pro_permissions();
    $allowedRoles = $permissions[$permission] ?? [];

    return in_array($user['role'], $allowedRoles, true);
}

function pro_require_auth(): array {
    $user = pro_user();
    if (!$user) {
        header('Location: index.php?page=login');
        exit;
    }
    return $user;
}

function pro_require_permission(string $permission): void {
    if (!pro_can($permission)) {
        http_response_code(403);
        echo '<main style="font-family:system-ui;padding:24px"><h2>403 Forbidden</h2><p>คุณไม่มีสิทธิ์เข้าถึงส่วนนี้</p><a href="index.php?page=dashboard">กลับ Dashboard</a></main>';
        exit;
    }
}

function pro_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }
    session_destroy();
}
