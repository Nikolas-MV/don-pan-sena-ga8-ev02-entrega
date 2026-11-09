<?php
/** Router de PHP CLI local: no publica configuracion, sesiones o herramientas. */
declare(strict_types=1);
$ruta = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if ($ruta === '/') {
    header('Location: /public/index.php');
    exit;
}
$permitida = preg_match('~^/(public|actions|api)/[a-zA-Z0-9_-]+\\.php$~D', $ruta)
    || preg_match('~^/public/(css|js|img)/[a-zA-Z0-9_.-]+\\.(css|js|png|ico)$~D', $ruta);
$raiz = dirname(__DIR__);
$archivo = $raiz . $ruta;
if (!$permitida || !is_file($archivo) || realpath($archivo) !== $archivo) {
    // Windows puede devolver barras invertidas: comparar normalizado.
    $normal = static fn ($p) => strtolower(str_replace('\\', '/', (string) $p));
    if (!$permitida || !is_file($archivo) || $normal(realpath($archivo)) !== $normal($archivo)) {
        http_response_code(404);
        exit('Recurso no disponible.');
    }
}
return false;
