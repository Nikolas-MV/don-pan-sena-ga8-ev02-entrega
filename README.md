# Don Pan — GA8-220501096-AA1-EV02

Módulos integrados. Aprendiz: Nikolás MV. Ficha: 3336171. Programa: Análisis y Desarrollo de Software.

## Presentación

En esta evidencia reúno el código PHP y la documentación de los módulos integrados de Don Pan. Los resultados y las cuatro capturas corresponden al laboratorio de EV01. Para preparar EV02 no modifiqué el funcionamiento del sistema ni volví a ejecutar las pruebas.

## Documentos de consulta

- docs/DOCUMENTO_TECNICO_EV02_REVISADO.docx y .pdf: funcionamiento, arquitectura, base de datos y resultados.
- docs/ACTA_PRUEBAS_ACEPTACION_EV02_REVISADO.docx y .pdf: resultados y espacios para revisión y aceptación.
- docs/MODULOS.md: entradas, validaciones y salidas de cada módulo.
- docs/MANUAL_TECNICO.md y docs/AMBIENTE_DESARROLLO_PRUEBAS.md: dependencias y condiciones de ejecución.
- docs/MATRIZ_PRUEBAS.csv y .md: detalle de los casos.
- docs/CONTROL_VERSIONES.md: origen del código y pasos pendientes de Git.

## Resultados disponibles

PHPUnit registró 37 pruebas y 37 aserciones, sin fallos ni errores. También hay 10 casos de integración aprobados y 23 solicitudes HTTP. Sus archivos originales están en docs/resultados/. No se midió el porcentaje de cobertura.

## Antes de ejecutar

El ambiente de EV01 utilizó PHP 8.2.12, PHPUnit 11.5.57 y MariaDB 11.4.13. Esta entrega no incluye la base de datos, las herramientas, las sesiones ni las credenciales. config/CONFIGURACION_EJEMPLO.json describe la configuración; la aplicación no lee ese archivo.

Para ejecutar el sistema en EV02 falta preparar una base independiente y adaptar el ejecutor de integración, que solo admite la ruta de EV01. Esos cambios requieren autorización. No debe ejecutarse desde EV01 para evitar la restricción ni conectarse al servidor principal.

Las pruebas unitarias necesitan PHP con mbstring y el PHAR oficial verificado en tools/. El script no descarga herramientas. El manual conserva los comandos de referencia; no se ejecutaron en esta revisión.

## Revisión y entrega

Se describen seis módulos: autenticación y permisos, productos, inventario, ventas, anulación e indicadores/reportes. Las demás funciones PHP incluidas no tienen pruebas adicionales. React no forma parte del paquete.

EV01 usa main y tiene los commits cf28d0e y f2037ac, además de cambios posteriores de documentación. EV02 aún no tiene repositorio Git ni URL pública comprobada. La decisión de cómo dar acceso al código está pendiente.

La aceptación formal también está pendiente. El acta permite registrar la revisión sin dar por aprobadas funciones que no se han evaluado. Antes de entregar falta añadir la portada personalizada y revisar las condiciones indicadas en docs/CRITERIOS_Y_PENDIENTES.md.

Los Word tienen tablas editables, numeración manual y formato Calibri 12/13/14. Queda pendiente comprobar su paginación al abrirlos en Word; los PDF se revisan por separado. El manifiesto y SHA256_ENTREGA.txt permiten comprobar los archivos del paquete.
