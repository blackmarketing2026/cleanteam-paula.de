<?php

require_once __DIR__ . '/helpers.php';

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

function require_login(): int
{
    $userId = current_user_id();
    if ($userId === null) {
        json_error('Nicht angemeldet.', 401);
    }

    return $userId;
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
