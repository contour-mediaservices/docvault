<?php
declare(strict_types=1);

function dv_encrypt(string $plain): string
{
    $key = hash('sha256', DOCVAULT_MASTER_KEY, true);
    $iv  = random_bytes(16);

    $cipher = openssl_encrypt(
        $plain,
        'AES-256-CBC',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );

    return base64_encode($iv . $cipher);
}

function dv_decrypt(?string $value): string
{
    if (!$value) return '';

    $raw = base64_decode($value, true);
    if ($raw === false || strlen($raw) < 17) return '';

    $key    = hash('sha256', DOCVAULT_MASTER_KEY, true);
    $iv     = substr($raw, 0, 16);
    $cipher = substr($raw, 16);

    return openssl_decrypt(
        $cipher,
        'AES-256-CBC',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    ) ?: '';
}