<?php
/** GET /api/session.php - estado de sesión + token CSRF para el cliente. */
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

exigir_metodo('GET');
$token = csrfToken(); // crea o re-emite el token de la sesión actual

if (!isLoggedIn()) {
    json_salida(['authenticated' => false, 'user' => null, 'csrf' => $token]);
}

json_salida([
    'authenticated' => true,
    'user' => [
        'id' => (int) $_SESSION['id_empleado'],
        'nombre' => $_SESSION['nombre'],
        'rol' => $_SESSION['rol'],
    ],
    'csrf' => $token,
]);
