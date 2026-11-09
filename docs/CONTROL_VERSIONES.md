# Control de versiones y procedencia — EV02

## 1. Historial observado en EV01

Git 2.56.0.windows.1; rama main; HEAD f2037ac6592807ccadec84748e6cc41daf5500d5. La revisión de EV01 registró 73 rutas en el índice. Algunas corresponden a documentos que se retiraron de la carpeta de trabajo y que no se incluyeron en esta copia.

- f2037ac Documentar resultados y preparar informe PDF - GA8 AA1 EV01
- cf28d0e Integrar reglas de seguridad y pruebas de modulos - GA8 AA1 EV01

En la carpeta de trabajo de EV01 hay cambios de documentación, cinco documentos eliminados y PDF/capturas que aún no se han incluido en un commit. No hay remoto configurado. Se conserva la captura 01 del git log y las fuentes actuales, ese commit no incluye todos los cambios actuales de la documentación.

## 2. Copia EV02

No se copió .git ni se inicializó un repositorio. Las rutas copiadas y sus hashes de procedencia están en docs/VERIFICACION_REVISION.json. El código y las pruebas se copiaron sin cambios. Los documentos de EV02 se elaboraron para esta entrega. La carpeta local no es una URL Git pública.

## 3. Plan pendiente de autorización

1. Revisar .gitignore, excluir infraestructura, claves, datos, temporales y archivos de preparación.
2. Revisar el contenido seleccionado y sus hashes, incluyendo resultados y capturas.
3. Tras autorización expresa: git init -b main; agregar rutas explícitas revisadas; comprobar git status y git diff --cached --check.
4. Preservar bytes de registros históricos si contienen CRLF/espacios y revisar .gitattributes antes de renormalizar.
5. Crear primer commit solo con autorización; registrar que deriva de EV01 y que su documentación no es el misma versión que el commit anterior.
6. Proponer repositorio privado y acceso del evaluador; publicar únicamente con autorización separada y comprobación de secretos.

No se han ejecutado esos comandos. No se entrega un bundle Git ni una URL inventada. Falta decidir cómo entregar el repositorio y facilitar su acceso al evaluador para completar este criterio.

## 4. Archivos versionados existentes verificables

El inventario de procedencia identifica cuáles de los archivos seleccionados figuran en el índice EV01. No se regeneran documentos retirados para igualar su conteo histórico.
