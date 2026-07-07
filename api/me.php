<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_method('GET');

$userId = current_user_id();
if ($userId === null) {
    json_response(['loggedIn' => false]);
}

$stmt = db()->prepare('SELECT email FROM users WHERE id = :id');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    logout_user();
    json_response(['loggedIn' => false]);
}

json_response(['loggedIn' => true, 'email' => $user['email']]);
