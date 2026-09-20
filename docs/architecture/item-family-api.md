# API de creación y listado de familias

## Contrato común

PHP y Java exponen `POST /api/item-families` y `GET /api/item-families` con `Content-Type: application/json`:

```json
{
  "code": "REFRIGERATED_FOOD",
  "name": "Alimentos refrigerados",
  "attributes": ["IS_FOOD", "IS_REFRIGERATED"]
}
```

`attributes` es obligatorio y puede ser `[]`. La respuesta de alta es `201` con `id` UUID v7, `code`, `name` y `attributes`. Un código ya existente devuelve `409` con `{"error":"Item family code already exists."}`. Un JSON mal formado devuelve `400` con `{"error":"Invalid JSON body."}`. Un cuerpo sin los campos requeridos o un atributo que no exista como `FAMILY` también devuelve `400` con `error`; un atributo de tipo `LOCATION` se considera inválido para esta operación. El código y el nombre no pueden estar vacíos, el nombre tiene un máximo de 255 caracteres y la lista no admite códigos repetidos.

El caso de uso reside en Application y utiliza un puerto de repositorio. Los adaptadores de persistencia de PHP y Java guardan la familia y sus vínculos en una transacción. Las APIs mantienen el mismo comportamiento observable para los escenarios incluidos en la colección Bruno.

## Listado de familias

`GET /api/item-families` devuelve `200` con un `JSON` cuya raíz es **un array**. Cada elemento tiene la misma forma que la respuesta de alta (`id`, `code`, `name`, `attributes` con los códigos de atributos `FAMILY` ordenados). Las familias se ordenan por `code` ascendente; sin familias registradas la respuesta es `[]`. No admite parámetros; un JSON de error no aplica a este endpoint.

## Pruebas aisladas

`bin/bruno_families_test.sh` usa dos bases dedicadas, `database_bruno_php` y `database_bruno_java`. En cada ejecución recrea el esquema `public` **solo en esas dos bases**, aplica `database/migrations/001_create_wms_schema.sql` con sus seeds y arranca las APIs de prueba en los puertos locales 28081 y 28082. Bruno realiza cinco altas por implementación, comprueba las respuestas y tres errores equivalentes por implementación (código duplicado, atributo de ubicación y cuerpo JSON sin objeto raíz). Al terminar, una consulta compara el contenido persistido completo con las cinco familias esperadas.

El servicio PostgreSQL debe estar arrancado. Las bases de prueba se conservan después para inspección; la siguiente ejecución las reinicia. La base `database` de desarrollo no se modifica. Este flujo requiere la migración consolidada del repositorio.
