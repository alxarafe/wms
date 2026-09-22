# API de creación y listado de artículos (items)

> **Estado**: artículos adaptados sobre el esquema `wms_review_v2` (migración 004), sin coste. Java queda aparcado hasta que PHP esté operativo; este documento describe el contrato común que Java deberá recuperar al reaparearse.

## Contrato común

PHP expone `POST /api/items` y `GET /api/items` con `Content-Type: application/json`:

```json
{
  "sku": "BOLSA50",
  "name": "Bolsa de 50 kg",
  "familyCode": "FOOD",
  "baseUomCode": "BOX",
  "isBatchManaged": true,
  "isExpirable": true
}
```

Campos de la petición:

- `sku` (obligatorio): string no vacío de hasta 50 caracteres (regla del VO `Sku`).
- `name` (obligatorio): string no vacío de hasta 255 caracteres (restricción de la tabla `item`).
- `familyCode` (obligatorio): código de familia existente. Si no existe devuelve `400` con `{"error":"Item family code does not exist."}`.
- `baseUomCode` (obligatorio): código de unidad existente con formato `^[A-Z][A-Z0-9]{1,9}$`. Si no existe devuelve `400` con `{"error":"Base unit of measure code does not exist."}`.
- `isBatchManaged` y `isExpirable` (obligatorios): booleanos. Invariante de dominio: un artículo expirable debe ser también con lote (`400` si se incumple).

Un `sku` ya existente devuelve `409` con `{"error":"Item sku already exists."}`. Un JSON mal formado devuelve `400` con `{"error":"Invalid JSON body."}`. Cuerpos sin los campos requeridos (o con un tipo incorrecto) devuelven `400`. Los campos `baseCost` y `currency` **no se aceptan**: el esquema v2 no modela coste y el dominio los rechaza con `400`.

La respuesta de alta es `201` con `id` UUID v7, `sku`, `name`, `familyCode`, `baseUomCode`, `isBatchManaged` e `isExpirable`. El dominio `Item`/`Sku`/`ItemId` aplica las mismas validaciones (`UuidV7Id` acepta versiones 7 y 8; los datos heredados del volcado en la 005 usan v8). La familia y la unidad se referencian por código en la API y se resuelven a sus identificadores al persistir.

> **Cambio frente al contrato anterior**: se retira `baseCost`/`currency` por decisión del responsable (el esquema v2 no modela coste). Se elimina el VO `Money` del dominio PHP y sus pruebas; Java lo conserva aún porque su adaptación al catálogo queda pendiente de reaparear.

## Listado de artículos

`GET /api/items` devuelve `200` con un `JSON` cuya raíz es **un array**. Cada elemento tiene la misma forma que la respuesta de alta. Los artículos se ordenan por `sku` ascendente; el listado incluye únicamente los artículos creados por la API (el esquema v2 no siembra catálogo). Sin artículos la respuesta es `[]`.

## Ampliaciones no incluidas

- Las conversiones entre unidades (`item_uom_conversion`, modeladas en `Item.addUomConversion`) no se exponen todavía en este incremento; el artículo se crea sin conversiones.
- No hay actualización ni borrado de artículos en el piloto (mismo alcance que familias y unidades).

## Pruebas aisladas

`bin/bruno_items_test.sh` usa la base dedicada `database_bruno_php`. En cada ejecución prepara una base limpia **solo con el esquema v2** (`database/migrations/004_create_wms_review_v2_schema.sql`), sin datos sembrados, y arranca la API de prueba PHP en el puerto local 28081. La colección es autosuficiente: crea primero la familia `FOOD` y las unidades `BOX` y `PAL` mediante las APIs de `/api/item-families` y `/api/uoms` (necesarias como `baseUomCode`), después crea dos artículos (`BOLSA50`, `CAJA24`), comprueba el listado ordenado y tres errores equivalentes (`sku` duplicado, familia inexistente y cuerpo JSON sin objeto raíz). Al terminar, una consulta compara el contenido persistido completo con los dos artículos esperados.

Java (ficheros `*-java-*.bru`) queda sin ejecutar hasta que su implementación se adapte al catálogo v2.