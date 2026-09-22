# Colecciones Bruno

## Configuración inicial PHP desde cero

`php-configuration/` y `./bin/php_configuration_test.sh` ejecutan la primera entrega
de configuración en dos bases PostgreSQL exclusivas de PHP. Incluyen PHPUnit,
migraciones v2 sin semillas, 50 peticiones de bootstrap y 9 de bloqueo de formato
por base, más verificación SQL. El script elimina únicamente el contenido de
`wms_php_configuration_a_test` y `wms_php_configuration_b_test` al recrearlas.
Las altas de almacenes, tipos y políticas son HTTP; una fixture SQL posterior crea
la calle necesaria para probar el bloqueo, hasta disponer de su endpoint.
Consulta [el contrato y la ejecución](../../docs/architecture/php-configuration-api.md).
Java y sus colecciones no se modifican en este bloque.

## Colecciones previas

`hexagonal-template/` contiene las peticiones de salud existentes. `families/` contiene las cinco altas solicitadas para cada API y los casos de código duplicado, atributo de ubicación y cuerpo JSON sin objeto raíz.

Para ejecutar las familias, arranca primero el servicio PostgreSQL y ejecuta desde la raíz:

```bash
./bin/bruno_families_test.sh
```

El script usa la imagen oficial `usebruno/cli:4.0.0` (se puede cambiar con `BRUNO_CLI_IMAGE`). Reinicia únicamente `database_bruno_php` y `database_bruno_java`, aplica la migración y sus seeds, arranca PHP y Java de prueba, ejecuta Bruno y comprueba en SQL las cinco familias y sus atributos exactos. La base `database` de desarrollo no se modifica. Las bases de prueba y las APIs quedan disponibles para inspección al terminar.

Para abrir la colección en la aplicación Bruno, carga `families/` y selecciona el entorno `isolated`: PHP responde en `http://localhost:28081` y Java en `http://localhost:28082`. El entorno `docker` se usa por el lanzador en la red de contenedores. Si se cambian los puertos con `BRUNO_PHP_PORT` o `BRUNO_JAVA_PORT`, actualiza también el entorno `isolated` en Bruno.
