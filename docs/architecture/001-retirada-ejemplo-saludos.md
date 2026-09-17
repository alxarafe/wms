# Retirada del ejemplo de saludos

## Decisión

Se retira el ejemplo `greet` de las APIs PHP y Java, junto con sus casos de uso, adaptadores, pruebas y peticiones Bruno. Las dos APIs conservan `GET /api/health` para comprobar que responden.

## Motivo y alcance

El saludo procedía de la plantilla y no representaba una operación WMS. Antes, `GET /api/greet` creaba un registro y `GET /api/greetings` consultaba esos registros. Ahora ambas rutas dejan de existir. La comprobación de salud responde sin depender de la tabla de ejemplo.

Las clases del dominio WMS y su esquema permanecen. Este cambio no aprueba las reglas del esquema como requisitos de negocio ni añade persistencia WMS.

La retirada de la migración que crea `greetings` se publicará por separado con los demás cambios de base de datos. La tabla ya existente y sus datos no se borran mediante este cambio de API.
