<?php

require_once __DIR__ . '/db.php';

function app_key(): string
{
    $key = config()['app_key'] ?? '';
    if (strlen($key) < 32) {
        throw new RuntimeException('app_key in config.php fehlt oder ist zu kurz.');
    }

    return hash('sha256', $key, true);
}

function encrypt_secret(string $plain): string
{
    if ($plain === '') {
        return '';
    }

    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plain, 'aes-256-cbc', app_key(), OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $cipher);
}

function decrypt_secret(?string $encoded): string
{
    if ($encoded === null || $encoded === '') {
        return '';
    }

    $raw = base64_decode($encoded, true);
    if ($raw === false || strlen($raw) < 17) {
        return '';
    }

    $iv = substr($raw, 0, 16);
    $cipher = substr($raw, 16);
    $plain = openssl_decrypt($cipher, 'aes-256-cbc', app_key(), OPENSSL_RAW_DATA, $iv);
    return $plain === false ? '' : $plain;
}
