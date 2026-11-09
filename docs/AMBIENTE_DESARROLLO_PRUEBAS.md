# Ambiente de desarrollo y pruebas — GA8-220501096-AA1-EV02

## 1. Herramientas de desarrollo

Don Pan utiliza PHP procedural con mysqli, HTML, CSS y JavaScript. Las cuatro capturas de EV01 muestran el uso de Visual Studio Code, aunque no permiten identificar su versión. En la revisión anterior se registró Git 2.56.0.windows.1. No se creó ni modificó un repositorio en EV02.

El proyecto se organiza en public/ para las vistas, actions/ para procesar formularios, includes/ para funciones compartidas, api/ para respuestas JSON, config/ para la conexión, sql/ para el esquema, tests/ para pruebas y scripts/ para su ejecución. Algunas vistas contienen SQL; no es una arquitectura estrictamente MVC.

## 2. Ambiente utilizado en EV01

Las pruebas utilizaron PHP CLI 8.2.12, PHPUnit 11.5.57, Node.js 24.16.0 y MariaDB 11.4.13. PHP se ejecutó con -n y extensiones indicadas en el comando, sin cambiar php.ini. El servidor PHP escuchó en 127.0.0.1:18088 y MariaDB en 127.0.0.1:13348. No se usó Apache.

Las unitarias cargan includes/reglas.php y trabajan en memoria. La integración utiliza fetch y el cliente MariaDB con datos ficticios. Los registros muestran 37 pruebas unitarias, 10 casos de integración y 23 solicitudes HTTP. El registro de cierre indica que los procesos terminaron y los puertos quedaron libres; no describe su estado actual.

## 3. Qué falta para ejecutar EV02

Para esta evidencia organicé el código y los documentos. La copia no incluye .runtime, datos, herramientas, cuentas ni claves y no se ha iniciado un servidor. Por tanto, necesita un ambiente propio antes de ejecutarse.

config/db.php mantiene el nombre don_pan_ga8_ev01_pruebas y el puerto 13348, pero exige que el directorio de datos pertenezca a la carpeta de esta copia y comprueba la cuenta de aplicación. Usar el mismo nombre en una instancia independiente no autoriza conectarse a la base de EV01.

El ejecutor de integración solo admite la ruta de EV01. Para usarlo desde EV02 habrá que adaptarlo con autorización y conservar sus comprobaciones de aislamiento. No se debe ejecutar desde la carpeta anterior para evitar este bloqueo.

## 4. Preparación propuesta

Antes de escribir datos, comprobar que la carpeta no tenga redirecciones, que el directorio de datos y el de sesiones sean nuevos y propios de EV02 y que los puertos estén libres. La inicialización de la nueva instancia se realizaría sin TCP y con credenciales generadas en memoria, protegidas mediante DPAPI de Windows.

Antes de crear o utilizar la base, comprobar @@port, @@datadir, DATABASE() y CURRENT_USER(). El archivo sql/schema.sql se aplicaría una sola vez a la base nueva. La cuenta de aplicación solo necesita SELECT, INSERT y UPDATE. Los turnos, empleados y productos deben ser ficticios. Estos pasos aún no se han realizado en EV02.

Las claves no se escriben en el código ni se pasan como argumentos. GA8_DB_PASSWORD se proporciona solo al proceso PHP autorizado. Los archivos DPAPI dependen del usuario Windows que los crea y no se incluyen en la entrega.

## 5. Publicación

No se ha comprobado un ambiente de producción ni una URL pública. Las direcciones 127.0.0.1 solo sirven en el equipo local.

## 6. Problemas habituales

Si falta el PHAR de PHPUnit, el script se detiene; no lo descarga. Si faltan mysqli o mbstring, revisar la instalación de PHP. Si falla la ruta, la identidad del servidor o la credencial, detener la ejecución sin usar Don Pan como alternativa. Si un puerto está ocupado, identificar el proceso y no detener programas ajenos. Para errores de login, revisar el empleado ficticio y su hash en la nueva instancia, sin contraseñas por defecto.

La preparación descrita es una propuesta para un ambiente nuevo. No debe presentarse como un procedimiento ya comprobado en EV02.
