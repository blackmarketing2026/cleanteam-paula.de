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
$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

if ($userCount === 0) {
    // Erster Start: die zuerst eingegebenen Zugangsdaten werden zum Admin-Konto.
    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, created_at) VALUES (:email, :hash, UTC_TIMESTAMP())');
    $stmt->execute([
        'email' => $email,
        'hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    login_user((int) $pdo->lastInsertId());
    json_response(['ok' => true, 'email' => $email, 'bootstrapped' => true]);
}

$stmt = $pdo->prepare('SELECT id, email, password_hash FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    json_error('Die Zugangsdaten stimmen nicht.', 401);
}

login_user((int) $user['id']);
json_response(['ok' => true, 'email' => $user['email']]);
