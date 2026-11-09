# Documentación de módulos — GA8-220501096-AA1-EV02

En este documento explico seis módulos de Don Pan a partir de su código. Las pruebas citadas se realizaron en EV01 y sus resultados se mantienen sin cambios.

## 1. Autenticación y permisos

**Objetivo:** Identificar al empleado activo y restringir operaciones por sesión y rol.

**Funcionalidades existentes:** Login PHP y JSON, consulta de sesión, cierre de sesión y comprobación del rol dueño.

**Archivos:** public/index.php; public/logout.php; actions/login_action.php; api/login.php; api/session.php; api/logout.php; includes/auth.php; includes/reglas.php; includes/csrf.php; api/bootstrap.php.

**Datos de entrada:** Formulario: usuario, password y csrf_token. API login: JSON usuario/password y cabecera CSRF de la sesión. Cookie de sesión en el navegador.

**Validaciones:** POST y CSRF; consulta de empleado activo; password_verify; regeneración del ID de sesión. API: campos obligatorios y límites de longitud. Dueño: sesión con id_empleado y rol dueño.

**Procesamiento:** Busca empleados mediante consulta preparada, verifica el hash y guarda id_empleado, nombre y rol en sesión; las comprobaciones de acceso se reutilizan antes de las operaciones protegidas.

**Datos de salida:** Formulario: redirección 302 a ventas o error. API: JSON público; 401 credenciales o sesión; 422 validación; 429 bloqueo. No devuelve password_hash.

**Relación con otros módulos:** Protege productos, inventario, ventas, anulación e indicadores. requireOwner restringe anulación y reportes.

**Pruebas y resultados:** AutorizacionTest: 6 casos. CsrfTest: 11 casos. I7 e I8 aprobados. El inicio de sesión se utiliza para preparar clientes en la integración, sin ser un caso I independiente.

**Limitaciones:** Bloqueo de 5 intentos y 60 segundos ligado a la sesión; puede evadirse con otra sesión. El límite de intentos no se conserva entre sesiones. La redirección relativa de requireOwner se conserva; el rechazo se comprobó sin seguirla.

## 2. Productos

**Objetivo:** Administrar el catálogo y consultar precio y existencias.

**Funcionalidades existentes:** Consulta y búsqueda por nombre/código, activos por defecto, opción de ver inactivos, alta, cambio de precio/stock mínimo, baja lógica y reactivación; API de lectura.

**Archivos:** public/productos.php; public/inventario.php; actions/producto_action.php; actions/producto_editar_action.php; actions/producto_estado_action.php; api/productos.php; config/db.php.

**Datos de entrada:** GET q e inactivos. Alta: nombre, precio, stock, stock_minimo, id_categoria. Edición: id_producto, precio, stock_minimo. Estado: id_producto, accion=baja/reactivar. POST con CSRF.

**Validaciones:** Sesión; POST y CSRF en acciones; alta con nombre no vacío, precio positivo y stock no negativo; edición con ID/precio positivos y mínimo no negativo; estado dentro de lista permitida.

**Procesamiento:** Consultas preparadas cuando hay parámetros. Alta transaccional y movimiento inicial cuando stock > 0; cambios de estado conservan historial. API aplica activo=1.

**Datos de salida:** HTML de catálogo y redirecciones de éxito/error; GET api/productos.php devuelve ok y productos con id_producto, nombre, precio, stock_actual, stock_minimo y codigo_barra.

**Relación con otros módulos:** Alimenta ventas e inventario y aporta las existencias a indicadores.

**Pruebas y resultados:** I10: producto activo visible, inactivo excluido y acceso sin sesión HTTP 401. I4 rechaza vender un inactivo. No hay resultados que comprueben todas las operaciones de alta, edición y baja del catálogo.

**Limitaciones:** Algunas acciones convierten los valores recibidos antes de comprobarlos. El alta convierte valores antes de validar; estas comprobaciones son diferentes de las reglas de cantidades utilizadas en las ventas.

## 3. Inventario de productos

**Objetivo:** Registrar entradas y mantener su trazabilidad.

**Funcionalidades existentes:** Consulta de existencias y registro de entrada positiva con movimiento asociado.

**Archivos:** public/inventario.php; actions/inventario_action.php; movimientos_inventario en sql/schema.sql.

**Datos de entrada:** id_producto, cantidad y csrf_token por POST; sesión autenticada.

**Validaciones:** Sesión, POST, token válido, ID y cantidad positivos tras conversión a entero.

**Procesamiento:** Transacción que incrementa productos.stock_actual e inserta un movimiento de entrada; commit o rollback.

**Datos de salida:** Redirección 302 con ok/error y stock actualizado en la base de laboratorio.

**Relación con otros módulos:** Las ventas descuentan el mismo stock y la anulación lo restituye mediante movimientos.

**Pruebas y resultados:** I1: entrada de 5, stock 10 a 15 y movimiento. I8: CSRF ausente/falso, HTTP 403 sin escritura. Ambos aprobados.

**Limitaciones:** No se ha probado la concurrencia y algunas entradas se convierten a números antes de validarse. No se ejecutaron pruebas nuevas de EV02.

## 4. Ventas

**Objetivo:** Registrar venta, detalle y salida de inventario de forma transaccional.

**Funcionalidades existentes:** Selección de cantidades, cliente opcional, método de pago y cálculo servidor del total; asignación del turno del empleado.

**Archivos:** public/ventas.php; public/js/venta-total.js; actions/venta_action.php; includes/reglas.php; includes/helpers.php; config/db.php.

**Datos de entrada:** cantidad[id_producto], id_cliente opcional, metodo_pago y csrf_token; empleado de sesión con turno.

**Validaciones:** Sesión y CSRF; cantidades enteras positivas hasta 2147483647; omisión solo de casillas vacías/cero; ID válido, producto existente y activo y stock suficiente. Pago fuera de lista se sustituye por efectivo.

**Procesamiento:** Abre transacción, crea cabecera, lee producto FOR UPDATE, valida línea, inserta detalle, descuenta stock, registra salida y actualiza total; revierte todo ante excepción.

**Datos de salida:** Venta/detalle/movimiento persistidos y redirección 302; ante fallo, rollback y redirección genérica de error.

**Relación con otros módulos:** Depende de empleados/turnos, clientes opcionales y productos; alimenta historial, anulación e indicadores.

**Pruebas y resultados:** ReglasVentaTest: 20 casos aprobados. I2: dos unidades, total 2501 y stock 15 a 13. I3: rollback tras una primera línea válida. I4: inactivo rechazado.

**Limitaciones:** El cálculo PHP usa float pese al almacenamiento DECIMAL. No se midió carga o concurrencia; El código usa FOR UPDATE, pero no se probó con operaciones simultáneas.

## 5. Anulación de ventas

**Objetivo:** Anular una venta una sola vez, restituir stock y conservar auditoría.

**Funcionalidades existentes:** Consulta de historial y anulación restringida al dueño; prevención de restitución duplicada.

**Archivos:** public/historial_ventas.php; actions/anular_venta_action.php; includes/auth.php; includes/reglas.php; includes/csrf.php.

**Datos de entrada:** id_venta y csrf_token por POST; sesión del dueño.

**Validaciones:** requireLogin, método POST, csrfVerify y requireOwner antes de getConnection; ID positivo; existencia y estado no anulado.

**Procesamiento:** Transacción con bloqueo FOR UPDATE de la venta; obtiene detalles, repone cantidades, inserta movimientos y marca anulada, anulada_por y fecha_anulacion. Rollback ante error.

**Datos de salida:** Redirección 302; venta marcada y stock restituido una sola vez, sin borrado de historial.

**Relación con otros módulos:** Integra venta, productos, movimientos y empleado responsable; indicadores excluyen ventas anuladas.

**Pruebas y resultados:** I5: stock 13 a 15 y auditoría. I6: segunda anulación rechazada sin reponer. I7: empleada rechazada con datos intactos. Todos aprobados.

**Limitaciones:** No se ejecutó prueba de dos anulaciones simultáneas. La política de autorización sí tiene 6 pruebas puras compartidas con el código.

## 6. Indicadores y reportes

**Objetivo:** Consultar ventas vigentes y existencias para seguimiento del negocio.

**Funcionalidades existentes:** Dashboard diario, totales, productos/stock bajo, últimas ventas, reportes por período y cinco productos más vendidos; API resumen.

**Archivos:** public/dashboard.php; public/reportes.php; api/resumen.php; includes/auth.php; config/db.php.

**Datos de entrada:** Sesión; reportes requiere dueño y GET desde/hasta con formato YYYY-MM-DD.

**Validaciones:** Autenticación; rol dueño en reportes; fechas con patrón básico y orden corregido; API solo GET y sesión.

**Procesamiento:** Agrega ventas con anulada=0. Dashboard/API cuentan productos activos. Reportes agrega detalle por producto y período con consultas preparadas.

**Datos de salida:** HTML de indicadores/reportes; API ok, ventasHoy, totalHoy, productos y stockBajo.

**Relación con otros módulos:** Consume ventas, detalle_ventas, productos y empleados; refleja el efecto de venta/anulación.

**Pruebas y resultados:** I9: totales aumentan con la venta y vuelven al valor inicial tras anular; dashboard y reportes respondieron 200. Ese resultado no comprueba todos los filtros ni la presentación de la pantalla.

**Limitaciones:** La lista de alertas de stock de public/reportes.php no filtra activo=1; las últimas ventas del dashboard tampoco excluyen anuladas. Estas listas no aplican todos los filtros de los indicadores. Las fechas se comprueban por su formato, no por su validez completa en el calendario.
