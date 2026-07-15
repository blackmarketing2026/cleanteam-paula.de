<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_method('POST');

$body = read_json_body();
$email = trim((string) ($body['email'] ?? ''));
$password = (string) ($body['password'] ?? '');

if ($email === '' || $password === '') {
    json_error('E-Mail-Adresse und Passwort werden benoetigt.', 422);
}

$pdo = db();
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(30) NOT NULL DEFAULT 'role_one',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
ensure_users_role_column($pdo);

$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

if ($userCount === 0) {
    // Erster Start: die zuerst eingegebenen Zugangsdaten werden zum Admin-Konto.
    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role, created_at) VALUES (:email, :hash, :role, UTC_TIMESTAMP())');
    $stmt->execute([
        'email' => $email,
        'hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => USER_ROLE_ADMIN,
    ]);

    $userId = (int) $pdo->lastInsertId();
    login_user($userId);
    $user = current_user();
    json_response(['ok' => true, 'user' => $user, 'email' => $email, 'bootstrapped' => true]);
}

$stmt = $pdo->prepare('SELECT id, email, password_hash, role FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    json_error('Die Zugangsdaten stimmen nicht.', 401);
}

login_user((int) $user['id']);
$currentUser = current_user();
json_response(['ok' => true, 'user' => $currentUser, 'email' => $user['email']]);
