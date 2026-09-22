# Entornos auxiliares

Estos archivos JSON conservan una única variable `base_url` apuntando a la API
PHP de prueba: `local` usa el puerto 28081; `docker` y `ci`, `php-api-test:80`.
No son los entornos que cargan automáticamente las colecciones o los lanzadores.
Los entornos Bruno activos son los `.bru` dentro de cada colección; consultar
[las instrucciones de ejecución](../bruno/README.md). Java sigue aplazado.
