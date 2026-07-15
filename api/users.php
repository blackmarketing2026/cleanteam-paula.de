<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$currentUser = require_admin();
$pdo = db();
ensure_users_role_column($pdo);
$method = $_SERVER['REQUEST_METHOD'];

function user_response(array $user): array
{
    $role = normalize_user_role((string) ($user['role'] ?? ''));

    return [
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'role' => $role,
        'roleLabel' => user_role_label($role),
        'createdAt' => to_iso($user['created_at'] ?? null),
    ];
}

function validate_role(string $role): string
{
    if (!array_key_exists($role, user_roles())) {
        json_error('Ungueltige Rolle.', 422);
    }

    return $role;
}

function load_user(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT id, email, role, created_at FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function admin_count(PDO $pdo): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
}

if ($method === 'GET') {
    $stmt = $pdo->query('SELECT id, email, role, created_at FROM users ORDER BY created_at ASC, id ASC');
    $users = array_map('user_response', $stmt->fetchAll());

    json_response([
        'users' => $users,
        'roles' => user_roles(),
        'currentUserId' => $currentUser['id'],
    ]);
}

if ($method === 'POST') {
    $body = read_json_body();
    $email = strtolower(trim((string) ($body['email'] ?? '')));
    $password = (string) ($body['password'] ?? '');
    $role = validate_role((string) ($body['role'] ?? USER_ROLE_ONE));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Bitte eine gueltige E-Mail-Adresse eintragen.', 422);
    }

    if (strlen($password) < 6) {
        json_error('Bitte ein Passwort mit mindestens 6 Zeichen eintragen.', 422);
    }

    if ($email === ADMIN_USER_EMAIL) {
        $role = USER_ROLE_ADMIN;
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO users (email, password_hash, role, created_at)
             VALUES (:email, :password_hash, :role, UTC_TIMESTAMP())'
        );
        $stmt->execute([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
        ]);
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            json_error('Dieser User existiert bereits.', 422);
        }
        throw $exception;
    }

    $user = load_user($pdo, (int) $pdo->lastInsertId());
    json_response(['ok' => true, 'user' => user_response($user)], 201);
}

if ($method === 'PUT') {
    $body = read_json_body();
    $id = (int) ($_GET['id'] ?? ($body['id'] ?? 0));
    $role = validate_role((string) ($body['role'] ?? USER_ROLE_ONE));
    $password = (string) ($body['password'] ?? '');

    if ($id <= 0) {
        json_error('User-ID fehlt.', 422);
    }

    $user = load_user($pdo, $id);
    if ($user === null) {
        json_error('User wurde nicht gefunden.', 404);
    }

    if (strtolower((string) $user['email']) === ADMIN_USER_EMAIL && $role !== USER_ROLE_ADMIN) {
        json_error('Dieser Admin-User darf nicht herabgestuft werden.', 422);
    }

    if ((int) $currentUser['id'] === $id && $role !== USER_ROLE_ADMIN) {
        json_error('Der eigene Admin-Zugang darf nicht herabgestuft werden.', 422);
    }

    if (normalize_user_role((string) $user['role']) === USER_ROLE_ADMIN && $role !== USER_ROLE_ADMIN && admin_count($pdo) <= 1) {
        json_error('Der letzte Admin darf nicht herabgestuft werden.', 422);
    }

    if ($password !== '' && strlen($password) < 6) {
        json_error('Das neue Passwort muss mindestens 6 Zeichen haben.', 422);
    }

    if ($password !== '') {
        $stmt = $pdo->prepare('UPDATE users SET role = :role, password_hash = :password_hash WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'role' => $role,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'role' => $role,
        ]);
    }

    $user = load_user($pdo, $id);
    json_response(['ok' => true, 'user' => user_response($user)]);
}

json_error('Methode nicht erlaubt.', 405);
