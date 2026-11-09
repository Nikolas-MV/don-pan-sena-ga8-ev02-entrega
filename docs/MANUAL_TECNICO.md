# Manual técnico — GA8-220501096-AA1-EV02

## 1. Alcance y dependencias

El manual describe lo necesario para revisar y ejecutar los módulos. La base, las credenciales, las herramientas y el servidor se preparan por separado. PHP 8.2.12 y PHPUnit 11.5.57 son las versiones usadas; integración realizada en EV01 requiere mysqli, mbstring, sesiones, Node.js fetch y cliente/servidor MariaDB 11.4.13.

## 2. Revisión de estructura

Leer README, docs/MODULOS.md, documento técnico Word/PDF y config/CONFIGURACION_EJEMPLO.json. La plantilla JSON es informativa y no es cargada por PHP. Las vistas y acciones incluyen conexión protegida; no ejecutarlas contra un servidor cualquiera.

## 3. Base independiente y datos de prueba

Antes de ejecutar integración en EV02 se necesita autorización para adaptar tests/Integration/ejecutar.cjs y preparar una instancia independiente. No copiar .runtime ni secretos de EV01. Mantener comprobaciones de raíz, puerto, datadir, base, usuario y privilegios. No importar el DDL en una base existente.

Comprobación SQL propuesta solo de lectura, sobre una conexión administrativa ya identificada y aislada:

    SELECT @@port, @@datadir, DATABASE(), CURRENT_USER(), VERSION();

Si no coincide la identidad esperada de EV02, detenerse. El puerto 3306, el datadir original o uno de EV01 no son destinos válidos.

Con la instancia nueva aprobada, seleccionar explícitamente la base antes de DDL, aplicar sql/schema.sql una sola vez y crear datos ficticios. Las claves deben generarse de forma aleatoria fuera del código. Usar password_hash/PASSWORD_DEFAULT para empleados; no distribuir el hash ni su contraseña. Asegurar turno asignado al empleado antes de vender.

## 4. Pruebas unitarias: comando de referencia, no ejecutado en EV02

Colocar en tools/phpunit-11.5.57.phar la herramienta oficial cuya suma SHA-256 es c704fdd37c6c6ab7adb386f31b4e186cd51156801fa9028b5ea8099a2cd7f106. El script valida el hash y no descarga automáticamente. La herramienta está excluida del paquete.

Desde la raíz de una copia preparada, sustituir la ruta de PHP por una instalación local válida:

    .\scripts\probar.ps1 -Php "<ruta-absoluta-a-php.exe>"

El script usa -n, carga mbstring desde el directorio ext de ese PHP y el bootstrap de reglas. No iniciar integración para comprobar unitarias.

## 5. Inicio manual: ambiente pendiente de preparación

No ejecutar este comando hasta que la identidad completa, cuenta mínima, variable temporal GA8_DB_PASSWORD y directorio local .runtime/sesiones estén preparados y aprobados. Las variables de ruta siguientes no deben apuntar al original o EV01. Desde EV02:

    & $phpEv02 -n -d "extension_dir=$extEv02" -d extension=mysqli -d extension=mbstring -d "session.save_path=$sesionesEv02" -d session.name=GA8_DONPAN_SESSID -d display_errors=0 -d log_errors=0 -S 127.0.0.1:18088 -t $raizEv02 "$raizEv02/scripts/router.php"

El comando sirve como referencia: todavía hay que asignar las rutas locales y preparar la base antes de utilizarlo. No se comprobó su ejecución en EV02. No se expone una clave en argumentos. log_errors=0 evita crear registros privados en esta propuesta manual; para diagnosticar, acordar almacenamiento local protegido.

URL local prevista una vez arrancado: http://127.0.0.1:18088/public/index.php. No es un despliegue público ni se comprobó en EV02. Iniciar sesión con el usuario ficticio generado, sin contraseñas publicadas. Navegar a Productos, Inventario, Ventas, Historial y Dashboard; Reportes requiere dueño.

Para detener el PHP de esa terminal, usar Ctrl+C. El servidor MariaDB necesita su cierre ordenado por conexión administrativa identificada; no taskkill, no FLUSH/REPAIR ni intervención del servidor principal. Falta preparar el supervisor/lanzador exclusivo de EV02.

## 6. Integración

El comando histórico scripts/probar.ps1 con -Integracion necesita Node y el entorno ya preparado. El ejecutor copiado está restringido a EV01 y no es ejecutable en EV02. Además, genera nuevos datos de prueba y puede escribir resultados; no usarlo sin una autorización de pruebas y adaptación. No modificar rutas de seguridad para superar el bloqueo.

## 7. Resultados y diagnóstico

Consultar docs/resultados/unitarias.xml, integracion.json y terminales; docs/MATRIZ_PRUEBAS.csv relaciona cada caso. Los archivos describen las pruebas de EV01, no una ejecución nueva. No hay cobertura medida. La captura de PHPUnit presenta una duración propia distinta de la salida TXT; ambas conservan 37/37 y no se afirma identidad de tiempos.

Si falta el PHAR, una extensión, una credencial temporal o coincide otra instancia, detener. No usar credenciales reales ni quitar validaciones para resolver el fallo. La preparación de una base nueva y la ejecución integrada siguen pendientes en EV02.
