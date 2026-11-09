<?php
/**
 * Control de sesión y autenticación.
 * Incluir SIEMPRE al inicio de cualquier página protegida (public/*.php).
 */

require_once __DIR__ . '/reglas.php';

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

function isLoggedIn(): bool
{
    return isset($_SESSION['id_empleado']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function isOwner(): bool
{
    return donPanEsDueno($_SESSION);
}

function requireOwner(): void
{
    requireLogin();
    if (!isOwner()) {
        header('Location: ventas.php');
        exit;
    }
}
