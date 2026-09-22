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

| Colección | Comando individual | Preparación y alcance |
| --- | --- | --- |
| `families/` | `./bin/bruno_families_test.sh` | 9 peticiones; reinicia `wms_review_v2` en `database_bruno_php`, aplica 004 sin semillas y verifica SQL. |
| `uoms/` | `./bin/bruno_uoms_test.sh` | 11 peticiones; misma preparación v2, crea su catálogo y verifica SQL. |
| `items/` | `./bin/bruno_items_test.sh` | 9 peticiones; misma preparación v2, crea sus dependencias por HTTP y verifica SQL. |
| `health/`, `state/`, `operations/` | `./bin/bruno_operations_test.sh` | 1 + 1 + 10 peticiones; reinicia únicamente `public` de `database_bruno_php` con 001–003 y las semillas antiguas. Comprueba salud y estado inicial antes de operar; verifica SQL al terminar. |
| `configuration/` | `./bin/php_configuration_test.sh` | 50 peticiones de bootstrap + 9 de bloqueo por cada una de dos bases PHP, PHPUnit y SQL. Usa el manifiesto PHP v2 sin semillas. |

Configuración recrea exclusivamente `wms_php_configuration_a_test` y
`wms_php_configuration_b_test`. Una fixture SQL introduce la calle necesaria para
probar el bloqueo de formato, hasta disponer de su endpoint. El lanzador sigue
siendo específico de PHP porque también ejecuta PHPUnit y sus migraciones.
Véase [el contrato de configuración](../../docs/architecture/php-configuration-api.md).

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
