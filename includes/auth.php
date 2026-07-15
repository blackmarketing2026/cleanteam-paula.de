<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

const ADMIN_USER_EMAIL = 'cleanteam@function-concept.de';
const USER_ROLE_ADMIN = 'admin';
const USER_ROLE_ONE = 'role_one';

function start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function current_user_id(): ?int
{
    start_session();
    return $_SESSION['user_id'] ?? null;
}

function user_roles(): array
{
    return [
        USER_ROLE_ADMIN => 'Admin',
        USER_ROLE_ONE => 'Rolle 1',
    ];
}

function normalize_user_role(?string $role): string
{
    return array_key_exists((string) $role, user_roles()) ? (string) $role : USER_ROLE_ONE;
}

function user_role_label(string $role): string
{
    $role = normalize_user_role($role);
    return user_roles()[$role];
}

function ensure_users_role_column(PDO $pdo): void
{
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(30) NOT NULL DEFAULT 'role_one' AFTER password_hash");
    }

    $stmt = $pdo->prepare('UPDATE users SET role = :role WHERE LOWER(email) = :email');
    $stmt->execute([
        'role' => USER_ROLE_ADMIN,
        'email' => ADMIN_USER_EMAIL,
    ]);

    $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($adminCount === 0) {
        $pdo->exec("UPDATE users SET role = 'admin' ORDER BY id ASC LIMIT 1");
    }
}

function ensure_users_profile_columns(PDO $pdo): void
{
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'name'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN name VARCHAR(190) NULL AFTER id");
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'password_encrypted'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN password_encrypted TEXT NULL AFTER password_hash");
    }
}

function current_user(): ?array
{
    $userId = current_user_id();
    if ($userId === null) {
        return null;
    }

    $pdo = db();
    ensure_users_role_column($pdo);

    $stmt = $pdo->prepare('SELECT id, email, role FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        return null;
    }

    $role = normalize_user_role((string) ($user['role'] ?? ''));

    return [
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'role' => $role,
        'roleLabel' => user_role_label($role),
        'isAdmin' => $role === USER_ROLE_ADMIN,
    ];
}

function require_login(): int
{
    $userId = current_user_id();
    if ($userId === null) {
        json_error('Nicht angemeldet.', 401);
    }

    return $userId;
}

function require_admin(): array
{
    require_login();
    $user = current_user();
    if ($user === null) {
        logout_user();
        json_error('Nicht angemeldet.', 401);
    }

    if (!$user['isAdmin']) {
        json_error('Keine Berechtigung fuer Einstellungen.', 403);
    }

    return $user;
}

function login_user(int $userId): void
{
    start_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function logout_user(): void
{
    start_session();
    $_SESSION = [];
    session_destroy();
}
