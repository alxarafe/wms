# API de operaciones de stock (entradas y salidas)

## Contrato común

PHP y Java exponen `POST /api/receipts` y `POST /api/issues` con `Content-Type: application/json`. Ambas devuelven la proyección del hueco afectado con el mismo contrato que el visor de estado (`LocationView`).

### Entrada (`POST /api/receipts`)

```json
{
  "locationId": "01a0aca9-bc00-7020-8000-000000000007",
  "itemCode": "ARROZ LARGO",
  "quantity": 40,
  "unit": "EA"
}
```

- `locationId`, `itemCode`, `quantity` y `unit` son obligatorios. `batchCode` y `expirationDate` son opcionales.
- `quantity` se interpreta como número con coma flotante en la frontera y se convierte a entero escalado 10⁻⁶ con redondeo HALF_UP (decisión M5). NaN/Infinity se rechazan.
- Reglas de lote (contrato del cliente): si el artículo está gestionado por lotes, `batchCode` es obligatorio y debe existir para el artículo; si no lo está, `batchCode` queda prohibido.
- `expirationDate` (ISO-8601: fecha `YYYY-MM-DD` o instante con zona horaria) captura la caducidad del lote en la recepción: si el lote no tiene caducidad almacenada, se establece con el valor enviado; si ya tiene una distinta, la entrada se rechaza con `409`. Si se envía sin `batchCode` o para un artículo sin lotes, `400`. Requiere que el lote exista (igual que `batchCode`).
- Respuesta de éxito: `201` con la `LocationView` del hueco (la referencia incluye `huCode` SSCC de 18 dígitos generado al crear la HU).
- El hueco debe estar vacío (`references == []`) y disponible: `status == ACTIVE` y pasillo no bloqueado (decisión M3 provisional).

### Salida (`POST /api/issues`)

```json
{
  "locationId": "01a0aca9-bc00-7020-8000-000000000008",
  "itemCode": "YOGUR FRESA",
  "quantity": 30,
  "unit": "EA"
}
```

- `batchCode` no forma parte de la salida: la HU es monoreferencia y el hueco es monoreferencia.
- La salida exige por ahora el **total de la HU**: cantidad y unidad deben coincidir exactamente con el stock almacenado. No hay salida parcial (decisión M5).
- Efectos persistidos (decisión M6): se borra el `stock_quant`, se desvincula la HU del hueco (`handling_unit.location_id = NULL`, la HU queda como registro histórico porque `stock_movement.hu_id` la referencia y el ledger es inmutable) y se inserta un movimiento `OUTBOUND` con `from_location_id` no nula y `to_location_id` nula.
- Respuesta de éxito: `200` con la `LocationView` del hueco ya vacío (`references == []`).

## Estados HTTP y motivos

| Estado | Motivo |
| --- | --- |
| `201` | Entrada creada (hueco ocupado) |
| `200` | Salida creada (hueco desocupado) |
| `400` | JSON mal formado, campo faltante o vacío, lote ausente/prohibido/inexistente, caducidad inválida o sin lote, unidad distinta del stock o cantidad inválida |
| `404` | Ubicación inexistente, artículo inexistente o lote inexistente |
| `409` | Hueco ocupado, hueco no disponible (bloqueado o pasillo bloqueado), caducidad distinta de la almacenada en el lote, artículo distinto del stock, cantidad distinta del total almacenado |

El cuerpo de error usa `{"error": "<motivo>"}`. Los motivos de la API están en inglés (coherencia de contrato con el visor y el cliente); el mock del cliente usa español como referencia de interfaz, sin implicar el contrato HTTP.

## Decisiones resueltas

### M5: precisión y consumo completo

- `Quantity` en dominio es un entero escalado 10⁻⁶ (PHP `int`, Java `long`); la coma flotante solo aparece en la frontera HTTP (`fromDecimal`, redondeo HALF_UP). No se admite `Quantity == 0`.
- La persistencia continúa en `stock_quant.quantity NUMERIC(18,6)` mediante `toDecimalString()`.
- La salida exige el **total** exacto de la HU. Una salida parcial o con cantidad/unidad distinta se rechaza (`409`/`400`). La coexistencia de varias salidas parciales sobre la misma HU es una decisión pendiente.

### M6: semántica de `stock_movement` por tipo

| Tipo | `from_location_id` | `to_location_id` |
| --- | --- | --- |
| `INBOUND` | `NULL` | no nula |
| `OUTBOUND` | no nula | `NULL` |
| `TRANSFER` | no nula | no nula y distinta |
| `ADJUSTMENT` | `NULL` | `NULL` |

La restricción `ck_stock_movement_directions` (migración `003_allow_outbound_movements.sql`) garantiza la tabla; el dominio también valida la dirección por tipo en la construcción de `StockMoved`. El campo `to_location_id` de `StockMoved` es opcional (PHP `?LocationId`, Java `Optional<LocationId>`).

### M3 (herencia de bloqueo de pasillo, resolución provisional)

El visor y las operaciones consideran un hueco **no disponible** si `location.status != 'ACTIVE'` o `aisle.is_blocked` está activo. Esta herencia es consistente entre el visor (pinta `blocked`) y las operaciones (rechazan entrada/salida con `409`), en PHP y Java.

### Captura de caducidad del lote en recepción

A petición del responsable, `POST /api/receipts` acepta `expirationDate` opcional (ISO-8601) y lo persiste sobre el lote referenciado con semántica de captura: se establece si el lote no tiene caducidad y se rechaza (`409`) si difiere de la almacenada. El lote sigue existiendo en el catálogo (se siembra o se crea fuera de la entrada); la entrada no crea lotes. PHP y Java implementan la misma validación y el mismo efecto, y la paridad se comprueba en la huella de `verify.sql`.

## Cómo se verifica la paridad

El escenario de paridad ejecuta el mismo recorrido contra ambas APIs sobre la base de verificación reiniciada (migraciones `001` a `003`): entrada ARROZ LARGO 40 EA, entrada repetida (409), YOGUR FRESA sin lote (400), YOGUR FRESA con lote (201), salida parcial (409), salida con unidad incorrecta (400), salida completa (200), PALITOS CANGREJO con lote sin caducidad y `expirationDate` (201, la captura), caducidad distinta de la almacenada (409) y caducidad con formato inválido (400). Las respuestas de estado al final se comparan normalizadas (número `N` y `N.0` se consideran equivalentes y el `huCode` generado aleatoriamente se excluye); la huella incluye la caducidad de los lotes `L-YOG-001` y `L-PAL-001`.

El recorrido también está disponible como colección Bruno en `api-tests/bruno/operations/` (secuencias `01`–`10` para PHP y `11`–`20` para Java). `./bin/bruno_operations_test.sh` la ejecuta contra ambas APIs sobre bases aisladas recreadas (migraciones `001` a `003`) y compara la huella de estado persistido entre PHP y Java.

## Documentos relacionados

- `docs/architecture/location-capacity-and-replenishment.md` (modelo discreto, 1 HU por hueco, monoreferencia).
- `docs/architecture/item-family-api.md` (patrón de contrato API por endpoint).
- `database/migrations/003_allow_outbound_movements.sql` (decisión M6 en esquema).
- `api-tests/bruno/operations/` (escenarios Bruno de entrada y salida).
- `private/auditoria-2026-09-17/informe.md` (registro de decisiones pendientes M2, M3, M5, M6).