# Colecciones Bruno compartidas

Cada escenario HTTP tiene una única definición, independiente del lenguaje, y
utiliza `{{base_url}}`. **La ejecución activa es solo PHP**. Java se adaptará
cuando PHP esté funcional; compartir los archivos no demuestra todavía paridad.

## Ejecución

Desde la raíz del repositorio, en el anfitrión, con PostgreSQL arrancado,
la imagen `wms-php-app:latest` construida y las dependencias Composer instaladas:

```bash
./bin/bruno_test.sh
```

Usa `usebruno/cli:4.0.0` (configurable con `BRUNO_CLI_IMAGE`). Ejecuta las suites
secuencialmente: catálogo y operaciones comparten `database_bruno_php`.
Los lanzadores no arrancan Java ni modifican sus bases. No ejecutar estas suites
simultáneamente. Las bases de desarrollo se conservan.

| Orden | Colección | Comando individual | Preparación y alcance |
| --- | --- | --- | --- |
| 1 | `families/` | `./bin/bruno_families_test.sh` | 9 peticiones; reinicia `wms_review_v2` en `database_bruno_php`, aplica 004 sin semillas y verifica SQL. |
| 2 | `uoms/` | `./bin/bruno_uoms_test.sh` | 11 peticiones; misma preparación v2, crea su catálogo y verifica SQL. |
| 3 | `items/` | `./bin/bruno_items_test.sh` | 9 peticiones; misma preparación v2, crea sus dependencias por HTTP y verifica SQL. |
| 4 | `health/`, `state/`, `operations/` | `./bin/bruno_operations_test.sh` | 1 + 1 + 10 peticiones; reinicia únicamente `public` de `database_bruno_php` con 001–003 y las semillas antiguas. Comprueba salud y estado inicial antes de operar; verifica SQL al terminar. |
| 5 | `configuration/` | `./bin/php_configuration_test.sh` | 50 peticiones de bootstrap + 9 de bloqueo por cada una de dos bases PHP, PHPUnit y SQL. Usa el manifiesto PHP v2 sin semillas. |

Configuración recrea exclusivamente `wms_php_configuration_a_test` y
`wms_php_configuration_b_test`. Una fixture SQL introduce la calle necesaria para
probar el bloqueo de formato, hasta disponer de su endpoint. El lanzador sigue
siendo específico de PHP porque también ejecuta PHPUnit y sus migraciones.
Véase [el contrato de configuración](../../docs/architecture/php-configuration-api.md).

## Orden, población de datos y dependencias

**Crear correctamente los datos forma parte de las pruebas.** Las altas HTTP
comprueban el estado de respuesta y los campos devueltos; los escenarios
posteriores consultan o utilizan esos datos, y SQL verifica su persistencia.
La preparación de tablas mediante migraciones no sustituye esas altas.

El orden global es el de la tabla anterior: familias → unidades → artículos →
salud/estado/operaciones → configuración. Lo determina `bin/bruno_test.sh`.
Dentro de cada fase, los escenarios tienen un `seq` explícito y se ejecutan
secuencialmente; los prefijos numéricos de los archivos reflejan ese orden.

Actualmente son suites autosuficientes, **no un recorrido acumulativo de todo el
WMS**. Familias, unidades y artículos reinician `wms_review_v2` antes de empezar.
Artículos no reutiliza el catálogo de las suites anteriores: sus escenarios
01–03 crean la familia FOOD y las unidades BOX/PAL; 04–05 crean artículos;
06 comprueba el listado y 07–09 prueban errores. Familias crea cinco registros
antes de los errores y el listado; unidades crea siete antes del listado y los
negativos. Cada suite verifica SQL antes de que empiece la siguiente.

Operaciones prepara el modelo antiguo `public` con semillas SQL. Primero
comprueba salud, después el estado inicial y finalmente ejecuta las entradas y
salidas ordenadas; su SQL valida el resultado. La creación de los datos de esas
semillas no está probada mediante altas HTTP por este recorrido.

Configuración usa dos bases independientes de las anteriores. Primero ejecuta
migraciones y PHPUnit; luego recrea ambas bases y aplica de nuevo las migraciones
sin semillas, para que Bruno no herede fixtures de PHPUnit. En cada base ejecuta:

1. Bootstrap 01–02: comprobar que no hay almacenes ni tipos HU.
2. Bootstrap 03–04: crear almacenes; 05–07: crear tipos HU.
3. Bootstrap 08–12: crear tipos de hueco por almacén.
4. Bootstrap 13–16: crear políticas usando los identificadores obtenidos por HTTP.
5. Bootstrap 17–41: comprobar duplicados, referencias y entradas inválidas.
6. Bootstrap 42: modificar el formato antes de crear calles; 43–50: comprobar
   los registros persistidos mediante consultas HTTP.
7. Verificar SQL e insertar la calle de prueba mediante la fixture provisional.
8. Format-lock 51–59: localizar el almacén, probar cambios prohibidos, permitir
   el formato idéntico y comprobar que no hay modificaciones indebidas.
9. Verificar SQL de nuevo; repetir el recorrido completo en la segunda base.

Los scripts se detienen si una invocación de Bruno o una comprobación SQL termina
con error. **No está activado `--bail`**: no se garantiza la detención en la primera
petición o aserción fallida dentro de una colección; pueden aparecer errores
posteriores causados por una alta fallida. La detención inmediata de las cadenas
dependientes queda pendiente.

El criterio solicitado para la evolución es poblar mediante altas HTTP
verificadas y después continuar con los datos creados. Para disponer de ese
recorrido integral habrá que conservar el estado entre fases, completar las APIs
pendientes y sustituir las semillas/fixtures. Cambiar solo el orden del lanzador
no basta, porque sus suites reinician los datos. Esta documentación no modifica
los lanzadores ni introduce todavía ese recorrido.

## Estado de las altas

Estado contrastado con las rutas, adaptadores y pruebas PHP del repositorio:

| Recurso | Estado y evidencia | Límite actual |
| --- | --- | --- |
| Almacenes | Alta y listado HTTP, persistencia SQL y cambio de formato implementados y probados. | El bloqueo de formato se prueba introduciendo una calle mediante SQL. |
| Tipos de HU | Alta y listado HTTP, campos y persistencia probados. | Crear el tipo no crea una HU física. |
| Tipos de hueco y políticas | Altas, consultas, referencias, validaciones y persistencia probadas. | Los movimientos antiguos de public todavía no aplican estas políticas. |
| Unidades y artículos | Altas, listados y errores del contrato actual probados con SQL. | No prueban todavía la integración completa con movimientos. |
| Familias y atributos | La suite families crea CHILLED_FOOD con FOOD y CHILLED, además de familias sin atributos, y verifica sus vínculos. | La suite usa una fixture mínima de atributos; no carga todas las semillas v2. |
| Catálogo y familias con atributos | El código PHP crea, lista y consulta storage_attribute; valida códigos existentes y guarda/lee vínculos en family_storage_attribute. Las pruebas de persistencia cubren creación, duplicados, referencias ausentes, rollback y migración 007. | El recorrido HTTP de familias con atributos está cubierto por la colección families. |

La gestión del catálogo usa UUID estables para las relaciones y códigos legibles
para la consulta del catálogo. La API no crea atributos implícitamente al crear
una familia. Los códigos históricos IS_FOOD, IS_CHEMICAL, IS_CHILLED e
IS_FROZEN se normalizan mediante la migración 007 a FOOD, CHEMICAL, CHILLED y
FROZEN, conservando UUID y vínculos.

El grupo exclusivo es informativo en esta fase. No se aplican todavía reglas de
exclusividad, bajas, edición ni compatibilidad durante los movimientos. Java
sigue aplazado hasta que PHP esté operativo.

## Uso en la aplicación Bruno

Abrir la carpeta de la colección y seleccionar `php-local` (puerto 28081).
Los lanzadores usan `php-docker` dentro de la red de contenedores. Si se cambia
`BRUNO_PHP_PORT`, ajustar también `base_url` en el entorno local.

En `configuration/`, seleccionar `php-local-a` (28181) o `php-local-b` (28182).
Su script proporciona `base_url` mediante `--env-var`. Los archivos auxiliares de
`api-tests/environments/` no se cargan automáticamente en estas colecciones.

Los lanzadores dejan sus APIs y bases disponibles para inspección. Repetir las
altas manualmente sin reiniciar los datos producirá duplicados. `state/` describe
las semillas iniciales y debe ejecutarse antes de las operaciones; para repetir
el recorrido, usar el lanzador. En configuración hay que respetar las fases
bootstrap → SQL de comprobación → fixture → format-lock → SQL de comprobación.

Las carpetas anteriores `php-configuration/` y `hexagonal-template/` pasan a
`configuration/` y `health/`. Reabrir las colecciones en Bruno si estaban cargadas.
Los entornos `docker`/`isolated`/`local` pasan a `php-docker`/`php-local`;
configuración renombra `local-a`/`local-b` a `php-local-a`/`php-local-b`.

## Decisión y evolución

La [decisión de pruebas compartidas](../../docs/architecture/bruno-tests.md)
detalla la cobertura conservada, la separación de los esquemas, las limitaciones
y los pasos para activar Java cuando su adaptación esté autorizada.
