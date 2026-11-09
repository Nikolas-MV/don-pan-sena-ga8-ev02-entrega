<?php
/**
 * Registra el error real en el log del servidor y redirige
 * con un mensaje genérico (evita filtrar detalles internos por URL).
 */
function logAndRedirect(string $location, \Throwable $e): void
{
    error_log('[don_pan] ' . $e->getMessage());
    header('Location: ' . $location . '?error=1');
    exit;
}