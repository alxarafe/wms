# API de creación y listado de familias

> **Estado**: familias adaptadas sobre el esquema `wms_review_v2` (migración 004). Java queda aparcado hasta que PHP esté operativo; este documento describe el contrato común que Java deberá recuperar al reaparearse.

## Contrato común

PHP expone `POST /api/item-families` y `GET /api/item-families` con `Content-Type: application/json`:

```json
{
  "code": "FOOD",
  "name": "Alimentos",
  "attributes": []
}
```

`attributes` es obligatorio y puede ser `[]`. La respuesta de alta es `201` con `id` UUID v7, `code`, `name` y `attributes` (`UuidV7Id` acepta versiones 7 y 8; los datos heredados del volcado en la 005 usan v8). Un código ya existente devuelve `409` con `{"error":"Item family code already exists."}`. Un JSON mal formado devuelve `400` con `{"error":"Invalid JSON body."}`. Un cuerpo sin los campos requeridos o un atributo que no exista en el catálogo `storage_attribute` devuelve `400` con `{"error":"Unknown FAMILY attribute: <CODE>"}`. El código y el nombre no pueden estar vacíos, el nombre tiene un máximo de 255 caracteres y la lista no admite códigos repetidos.

En el esquema v2 los atributos de familia son un vínculo hacia `storage_attribute` (`family_storage_attribute`); no existe la distinción `target_type` FAMILY/LOCATION del esquema `public` eliminado. El catálogo `storage_attribute` se puebla con semillas en `005_seed_wms_review_v2_demo_data.sql` solo en entornos de desarrollo; la base de pruebas Bruno se monta con 004 (sin semillas) y por ahora las altas de la colección se hacen **sin atributos** (`attributes: []`).

El caso de uso reside en Application y utiliza un puerto de repositorio. Los adaptadores de persistencia guardan la familia y sus vínculos en una transacción. Los escenarios Bruno describen el comportamiento PHP que Java deberá recuperar cuando se retome su adaptación.

## Listado de familias

`GET /api/item-families` devuelve `200` con un `JSON` cuya raíz es **un array**. Cada elemento tiene la misma forma que la respuesta de alta (`id`, `code`, `name`, `attributes` con los códigos del catálogo `storage_attribute` vinculados, ordenados). Las familias se ordenan por `code` ascendente; sin familias registradas la respuesta es `[]`. No admite parámetros; un JSON de error no aplica a este endpoint.

## Pruebas aisladas

`bin/bruno_families_test.sh` usa la base dedicada `database_bruno_php`. En cada ejecución prepara una base limpia **solo con el esquema v2** (`database/migrations/004_create_wms_review_v2_schema.sql`), sin datos sembrados, y arranca la API de prueba PHP en el puerto local 28081. La colección realiza cinco altas con `attributes: []`, comprueba las respuestas y tres errores equivalentes (código duplicado, atributo no existente en el catálogo y cuerpo JSON sin objeto raíz). Al terminar, una consulta compara el contenido persistido completo con las cinco familias esperadas.

La cobertura de asignación de atributos de familia se aplaza hasta que exista un mecanismo para poblar `storage_attribute` sobre una base v2 limpia (mientras tanto, con 004 el catálogo está vacío). Los escenarios son únicos y usan `base_url`; actualmente solo se ejecutan contra PHP. Java queda pendiente de adaptación al catálogo v2 y reutilizará estas mismas colecciones. Véase la [decisión de pruebas Bruno](bruno-tests.md).

El servicio PostgreSQL debe estar arrancado. La base de prueba se conserva después para inspección; la siguiente ejecución la reinicia. La base `database` de desarrollo no se modifica. Este flujo requiere la migración 004 del repositorio.
