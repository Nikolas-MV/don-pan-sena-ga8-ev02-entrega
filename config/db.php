<?php
/** Conexión exclusiva al laboratorio: sin valores heredados del original. */
declare(strict_types=1);

date_default_timezone_set('America/Bogota');

function getConnection(): mysqli
{
    $raiz = dirname(__DIR__);
    $datos = $raiz . '/.runtime/mariadb/data';
    $normalizar = static fn (string $p): string => strtolower(rtrim(str_replace('\\', '/', $p), '/'));
    if (realpath($raiz) === false || realpath($datos) === false
        || $normalizar(realpath($raiz)) !== $normalizar($raiz)
        || $normalizar(realpath($datos)) !== $normalizar($datos)) {
        throw new RuntimeException('Destino de laboratorio no valido.');
    }
    $clave = getenv('GA8_DB_PASSWORD');
    if (!is_string($clave) || $clave === '') {
        throw new RuntimeException('Credencial efimera del laboratorio ausente.');
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli('127.0.0.1', 'ga8_app', $clave, 'don_pan_ga8_ev01_pruebas', 13348);
    $identidad = $conn->query(
        'SELECT @@port AS puerto, @@datadir AS datos, DATABASE() AS base, CURRENT_USER() AS cuenta'
    )->fetch_assoc();
    if ((int) $identidad['puerto'] !== 13348
        || $normalizar($identidad['datos']) !== $normalizar(realpath($datos))
        || $identidad['base'] !== 'don_pan_ga8_ev01_pruebas'
        || $identidad['cuenta'] !== 'ga8_app@127.0.0.1') {
        $conn->close();
        throw new RuntimeException('Identidad de laboratorio incorrecta; operacion detenida.');
    }
    $conn->set_charset('utf8mb4');
    $conn->query("SET time_zone = '-05:00'");
    return $conn;
}
