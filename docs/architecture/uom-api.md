# API de creación y listado de unidades de medida (UoM)

> **Estado**: unidades adaptadas sobre el esquema `wms_review_v2` (migración 004). Java queda aparcado hasta que PHP esté operativo; este documento describe el contrato común que Java deberá recuperar al reaparearse.

## Contrato común

PHP expone `POST /api/uoms` y `GET /api/uoms` con `Content-Type: application/json`:

```json
{
  "code": "BOX",
  "description": "Standard Box"
}
```

`code` es obligatorio y debe cumplir el formato `^[A-Z][A-Z0-9]{1,9}$` (mayúsculas alfanuméricas de 2 a 10 caracteres), el mismo `CHECK` que aplica la tabla `uom` del esquema. `description` es obligatorio y no puede superar 255 caracteres. Un código ya existente devuelve `409` con `{"error":"Uom code already exists."}`. Un JSON mal formado devuelve `400` con `{"error":"Invalid JSON body."}`. Un cuerpo sin los campos requeridos o con `code` en formato no válido también devuelve `400` con `error`.

La respuesta de alta es `201` con `id` UUID v7, `code` y `description`. El dominio `Uom`/`UomCode`/`UomId` aplica las mismas validaciones (`UuidV7Id` acepta versiones 7 y 8; los datos heredados del volcado en la 005 usan v8).

## Listado de unidades

`GET /api/uoms` devuelve `200` con un `JSON` cuya raíz es **un array**. Cada elemento tiene la misma forma que la respuesta de alta (`id`, `code`, `description`). Las unidades se ordenan por `code` ascendente; el listado incluye únicamente las unidades creadas por la API (el esquema v2 no siembra catálogo). Sin unidades la respuesta es `[]`.

## Pruebas aisladas

`bin/bruno_uoms_test.sh` usa la base dedicada `database_bruno_php`. En cada ejecución prepara una base limpia **solo con el esquema v2** (`database/migrations/004_create_wms_review_v2_schema.sql`), sin datos sembrados, y arranca la API de prueba PHP en el puerto local 28081. La colección es autosuficiente: crea las siete unidades que verificará (LTR, BAG, PZA, BOX, EA, KG, PAL), comprueba el listado completo (7 unidades ordenadas) y tres errores equivalentes (código duplicado reutilizando una unidad ya creada, código en minúsculas y cuerpo JSON sin objeto raíz). Al terminar, una consulta compara el contenido persistido completo con las siete unidades esperadas.

Los escenarios son únicos y usan `base_url`; actualmente solo se ejecutan contra PHP. Java queda pendiente de adaptación al catálogo v2 y reutilizará estas mismas colecciones. Véase la [decisión de pruebas Bruno](bruno-tests.md).
