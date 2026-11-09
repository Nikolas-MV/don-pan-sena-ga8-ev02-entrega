<?php
/**
 * POST /api/login.php - autenticación real contra la tabla empleados.
 *
 * Controles aplicados:
 *  - sólo POST + cuerpo JSON + token CSRF (anti-CSRF y anti-método cruzado),
 *  - validación de longitud/tipo ANTES de tocar la base,
 *  - consulta preparada (sin inyección SQL),
 *  - password_verify() sobre hash bcrypt, jamás comparaciones de texto,
 *  - MENSAJE GENÉRICO de fallo: no revela si el usuario existe (enumeración),
 *  - límite de 5 intentos por sesión con bloqueo de 1 minuto (igual que el
 *    formulario PHP); el contador se limpia sólo al acertar,
 *  - session_regenerate_id(true) al autenticar (anti session fixation),
 *  - la respuesta NUNCA incluye password_hash.
 */
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

exigir_metodo('POST');
exigir_csrf();

$bloqueadoHasta = (int) ($_SESSION['login_bloqueado_hasta'] ?? 0);
if (time() < $bloqueadoHasta) {
    json_error(429, 'bloqueado', 'Demasiados intentos. Espera 1 minuto e inténtalo de nuevo.');
}

$entrada = leer_json();
$usuario = trim((string) ($entrada['usuario'] ?? ''));
$password = (string) ($entrada['password'] ?? '');

if ($usuario === '' || $password === '') {
    json_error(422, 'validacion', 'Usuario y contraseña son obligatorios.');
}
if (mb_strlen($usuario) > 50 || strlen($password) > 72) {
    json_error(422, 'validacion', 'Datos fuera de los límites permitidos.');
}

try {
    $conn = getConnection();
    $stmt = $conn->prepare(
        'SELECT id_empleado, nombre, password_hash, rol FROM empleados WHERE usuario = ? AND activo = 1'
    );
    $stmt->bind_param('s', $usuario);
    $stmt->execute();
    $empleado = $stmt->get_result()->fetch_assoc();
} catch (Throwable $e) {
    error_log('[api/login] ' . $e->getMessage());
    json_error(500, 'servidor', 'Error interno al consultar la base de datos.');
}

$credencialesOk = $empleado !== null
    && password_verify($password, (string) $empleado['password_hash']);

if (!$credencialesOk) {
    $intentos = (int) ($_SESSION['login_intentos'] ?? 0) + 1;
    $_SESSION['login_intentos'] = $intentos;
    if ($intentos >= 5) {
        $_SESSION['login_intentos'] = 0;
        $_SESSION['login_bloqueado_hasta'] = time() + 60;
    }
    // Mismo mensaje exista o no el usuario: evita enumerar cuentas.
    json_error(401, 'credenciales', 'Usuario o contraseña incorrectos.');
}

unset($_SESSION['login_intentos'], $_SESSION['login_bloqueado_hasta']);
session_regenerate_id(true); // nueva sesión tras autenticar

$_SESSION['id_empleado'] = (int) $empleado['id_empleado'];
$_SESSION['nombre'] = $empleado['nombre'];
$_SESSION['rol'] = $empleado['rol'];

json_salida([
    'ok' => true,
    'user' => usuario_publico($empleado),
    'csrf' => csrfToken(),
]);
