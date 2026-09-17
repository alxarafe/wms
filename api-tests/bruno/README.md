# Colecciones Bruno

`hexagonal-template/` contiene las peticiones de salud existentes. `families/` contiene las cinco altas solicitadas para cada API y los casos de código duplicado, atributo de ubicación y cuerpo JSON sin objeto raíz.

Para ejecutar las familias, arranca primero el servicio PostgreSQL y ejecuta desde la raíz:

```bash
./bin/bruno_families_test.sh
```

El script usa la imagen oficial `usebruno/cli:4.0.0` (se puede cambiar con `BRUNO_CLI_IMAGE`). Reinicia únicamente `database_bruno_php` y `database_bruno_java`, aplica la migración y sus seeds, arranca PHP y Java de prueba, ejecuta Bruno y comprueba en SQL las cinco familias y sus atributos exactos. La base `database` de desarrollo no se modifica. Las bases de prueba y las APIs quedan disponibles para inspección al terminar.

Para abrir la colección en la aplicación Bruno, carga `families/` y selecciona el entorno `isolated`: PHP responde en `http://localhost:28081` y Java en `http://localhost:28082`. El entorno `docker` se usa por el lanzador en la red de contenedores. Si se cambian los puertos con `BRUNO_PHP_PORT` o `BRUNO_JAVA_PORT`, actualiza también el entorno `isolated` en Bruno.
