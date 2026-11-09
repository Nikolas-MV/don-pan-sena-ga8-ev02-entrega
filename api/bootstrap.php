<?php
/**
 * Punto de entrada compartido de la API JSON del front-end React.
 * Responsabilidades:
 *  - Sesión PHP endurecida (hereda de includes/auth.php).
 *  - Cabeceras de seguridad (nunca cachear, no MIME-sniffing).
 *  - CORS por LISTA BLANCA: jamás `*` (imposible con credenciales).
 *  - Utilidades de validación: método, sesión, cuerpo JSON y token CSRF.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Frame-Options: DENY');

// --- CORS: sólo orígenes explícitamente permitidos -----------------
$permitidos = array_filter(array_map('trim', explode(',',
    getenv('CORS_ORIGENES') ?: 'http://localhost:5173,http://localhost:4173'
)));
$origen = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origen !== '' && in_array($origen, $permitidos, true)) {
    header('Access-Control-Allow-Origin: ' . $origen);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/** Responde JSON y termina. */
function json_salida(array $datos, int $codigo = 200): never
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Responde un error normalizado {ok:false, error, message}. */
function json_error(int $codigo, string $error, string $mensaje): never
{
    json_salida(['ok' => false, 'error' => $error, 'message' => $mensaje], $codigo);
}

/** 405 si el método HTTP no coincide. */
function exigir_metodo(string $metodo): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $metodo) {
        header('Allow: ' . $metodo);
        json_error(405, 'metodo_no_permitido', 'Método no permitido.');
    }
}

/** 401 si no hay sesión PHP activa. */
function exigir_sesion(): void
{
    if (!isLoggedIn()) {
        json_error(401, 'sin_sesion', 'Sesión no válida o expirada. Vuelve a entrar.');
    }
}

/** Lee y valida un cuerpo application/json. */
function leer_json(): array
{
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) {
        json_error(415, 'tipo_invalido', 'La petición debe enviar application/json.');
    }
    $crudo = file_get_contents('php://input');
    $datos = ($crudo === '' || $crudo === false) ? null : json_decode($crudo, true);
    if (!is_array($datos)) {
        json_error(400, 'json_invalido', 'Cuerpo JSON ausente o malformado.');
    }
    return $datos;
}

/**
 * Token CSRF por cabecera X-CSRF-Token (misma sesión que emitió el token).
 * Aplica a TODA escritura: impide que otro sitio envíe peticiones en tu nombre.
 */
function exigir_csrf(): void
{
    $enviado = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $esperado = $_SESSION['csrf_token'] ?? '';
    if (!donPanCsrfValido($esperado, $enviado)) {
        json_error(403, 'csrf', 'Token de seguridad inválido. Recarga la aplicación.');
    }
}

/** Normaliza un usuario para no filtrar jamás password_hash. */
function usuario_publico(array $fila): array
{
    return [
        'id' => (int) $fila['id_empleado'],
        'nombre' => $fila['nombre'],
        'rol' => $fila['rol'],
    ];
}