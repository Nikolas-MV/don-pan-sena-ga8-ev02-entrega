<?php
/**
 * Protección CSRF. Incluir después de auth.php en vistas con formularios
 * y en los actions que procesan esos formularios.
 */

require_once __DIR__ . '/reglas.php';

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function csrfVerify(): void
{
    if (!donPanCsrfValido($_SESSION['csrf_token'] ?? null, $_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Token CSRF inválido.');
    }
}