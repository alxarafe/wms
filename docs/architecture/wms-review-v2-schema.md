# Esquema WMS revisado v2 (`wms_review_v2`)

## Propósito y estado

**Actualización PHP (22/09/2026):** la primera entrega de
[configuración inicial por API](php-configuration-api.md) consume `warehouse`,
`handling_unit_type`, `location_type` y `location_type_hu_policy`. El catálogo PHP
previo consume `uom`, `item_family` e `item`; estado y operaciones siguen en `public`.
Las tablas de inventario v2 continúan modeladas, sin motor operativo nuevo.
El bootstrap PHP usa `004` y el incremento propio `006`, sin `005` ni los maestros
embebidos en `001`. Las comprobaciones y semillas históricas descritas más abajo
corresponden al lanzador SQL anterior, no al nuevo recorrido desde cero.

El esquema `wms_review_v2` es la **fuente de verdad del dominio WMS revisado**,
instalado como **esquema propio** en la base `database` mediante las migraciones:

- `004_create_wms_review_v2_schema.sql`: esquema, 27 tablas, claves, índices,
  funciones, triggers y comentarios.
- `005_seed_wms_review_v2_demo_data.sql`: datos de demostración.

Se instala **en paralelo** al esquema `public` existente, que conserva su
estructura, sus datos y los adaptadores que lo consumen. El esquema `public`
seguirá operando hasta que la adaptación de aplicación (fase 2) migre los
adaptadores PHP y Java a `wms_review_v2`.

Origen: el volcado privado `private/wms_lab_export.sql` (pg_dump de PostgreSQL
16.15), esquema `wms_review_v2`. No se instala `wms_preview`, que se descarta
como fuente de verdad.

## Decisiones adoptadas

| Decisión | Justificación |
| --- | --- |
| Esquema paralelo `wms_review_v2` en `database` | No destructivo; preserva `public` y el funcionamiento actual hasta adaptar la aplicación. |
| IDs `uuid` nativos | Fiel al volcado de referencia. La reconciliación con `public` (`VARCHAR(36)` con UUID v7) se decide en la fase 2. |
| Sin `search_path` de sesión | Todo accede calificado `wms_review_v2.`; no afecta al `public`. |
| FKs de restricción (`ON DELETE RESTRICT`) | Fiel al volcado: refuerza integridad, evita borrados silenciosos. |
| Migraciones idempotentes | Re-ejecutables sin error, siguiendo el patrón de `001`–`003`. |

## Qué está implementado

### Estructura física

| Tabla | Rol |
| --- | --- |
| `warehouse` | Almacén con formato de código configurable: `uses_zones`, `separator`, `include_zone_in_code`, `aisle_digits`, `bay_digits`, `level_digits`. |


La generación de `location.code` debe obedecer la configuración persistida del
almacén: `separator` separa los segmentos y cada coordenada se rellena a la
anchura indicada por `aisle_digits`, `bay_digits` y `level_digits`. Por ejemplo,
para el almacén A (`separator='.'`, `aisle_digits=1`, `bay_digits=2`,
`level_digits=1`) el primer hueco del nivel superior es `A.1.01.2`. El cliente
simulado usa la misma convención; no mantiene el formato legacy `P-A-01-02`.
La renumeración de huecos existentes al cambiar el formato sigue bloqueada tras
crear la primera calle, por lo que esta regla no implica una migración automática
ni una modificación de datos ya persistidos.

### Topología y huecos

| Tabla | Rol |
| --- | --- |
| `zone` | Zona de un almacén; puede aportar atributos (`zone_storage_attribute`). |
| `aisle` | Calle física, numerada por `(warehouse_id, number)` y única por almacén. |
| `aisle_definition` | Cuadrícula de una calle: `bay_count` × `level_count`. |
| `aisle_level_template` | Tipo de hueco inicial previsto por altura y almacén. |
| `aisle_void` | Rangos de bay/level sin hueco en la generación. |
| `aisle_allowed_family` | Familias admitidas en una calle (vacío = sin restricción de familia). |
| `location` | Hueco físico: `code`, coordenadas `(aisle_id, bay, level)` únicas y `is_enabled`. |

### Catálogo y atributos

| Tabla | Rol |
| --- | --- |
| `item_family` | Familias jerárquicas por `parent_family_id`. |
| `item` | Artículo por SKU, con familia, unidad base y gestión de lote/caducidad. |
| `uom` | Unidades de medida (p. ej. `EA`, `BOX`, `PAL`). |
| `storage_attribute` | Catálogo de atributos (p. ej. `CHILLED`, `FROZEN`) con `exclusive_group_code` opcional. |
| `family_storage_attribute` | Atributos asignados a familias. |
| `zone_storage_attribute` | Atributos aportados por zona a sus huecos. |
| `location_storage_attribute` | Atributos propios de un hueco, con `remove_attribute` (excepción futura, no evaluada). |
| `storage_rule` | Reglas `REQUIRE_LOCATION` y `SEPARATE` con índices únicos parciales. |

### Unidades de manejo e inventario

| Tabla | Rol |
| --- | --- |
| `handling_unit_type` | Tipos configurables de HU (`PALLET`, `BOX`); distintos de la unidad `PAL`. |
| `handling_unit` | HU con `code` único, `sscc` único opcional, ubicación, recepción y estados `FULL/PARTIAL` y `ACTIVE/SHIPPED/CLOSED`. |
| `handling_unit_content` | Contenido real (artículo, lote, caducidad, cajas y unidades por caja). Fuente de verdad del stock para HU activas. |
| `handling_unit_hold` | Retenciones de mercancía; una retención activa impide servir la HU. |
| `handling_unit_content_correction` | Correcciones autorizadas de lote/caducidad con rastro. |

### Operaciones

| Tabla | Rol |
| --- | --- |
| `receipt` / `receipt_line` | Entradas y sus líneas; una línea puede corresponder a varias HU. |
| `stock_movement` | Historial inmutable de operaciones (`RECEIVE`, `TRANSFER`, `SEPARATE`, `DISPATCH`, `CORRECT`); la aplicación actualiza HU/contenido e inserta el movimiento en la misma transacción. |
| `location_block` | Bloqueos históricos del hueco (`blocks_putaway`/`blocks_removal`). |

### Invariantes en SQL

- **`check_zone_mode`** (trigger `location_zone_mode`): `zone_id` presente si y solo si `warehouse.uses_zones`.
- **`guard_warehouse_format`** (trigger `warehouse_format_guard`): formato y modo de zonas se bloquean tras crear la primera calle.
- `item_check`: un artículo expirable gestiona lote.
- `handling_unit` CHECKs: concurrencia de `source_pallet_id`/`source_ordinal`, no auto-referencia, estados y formato `sscc`.
- `storage_rule_check`: forma válida por tipo (alcance `NULL` en `REQUIRE_LOCATION`; `AISLE` y `attribute_a_id < attribute_b_id` en `SEPARATE`).
- Índices únicos parciales: `uq_v2_rule_require` (por par de atributos en `REQUIRE_LOCATION`) y `uq_v2_rule_separate` (por par y alcance en `SEPARATE`).

### Datos sembrados

`005` carga los datos de demostración del volcado: 2 almacenes (A sin zonas, B con zona `FRIO`), 2 calles, 8 plantillas de altura, 5 huecos, 5 tipos de hueco, 6 políticas `location_type_hu_policy`, 2 tipos de HU, 2 HU con su contenido, 1 retención, 1 recepción y línea, 2 artículos, 4 familias, 3 uoms, 4 atributos, 3 reglas y 2 movimientos `RECEIVE`. Tablas con datos vacíos en el volcado (`handling_unit_content_correction`, `location_block`, `location_storage_attribute`) no reciben `INSERT`.

## Qué no está implementado

- **Adaptadores operativos de estado y movimientos:** siguen apuntando a `public`.
  PHP ya consume v2 para catálogo y configuración inicial, sin trasladar todavía
  las operaciones de stock. La sincronización Java queda fuera de esta entrega.
- **Motor de reglas:** `storage_rule`, el grupo exclusivo `exclusive_group_code`, la herencia de atributos desde familias y zona, las políticas `location_type_hu_policy` y `aisle_allowed_family` son **configuración modelada**; su evaluación requiere un servicio, no se instala en SQL.
- **`remove_attribute`** (`location_storage_attribute`): excepción futura documentada, no evaluada.
- **Generador de huecos** desde `aisle_definition`/`aisle_level_template`/`aisle_void`: pendiente de servicio.
- **Descuentos/separaciones** de HU caja, expediciones, correcciones operativas: pendientes de operaciones de aplicación.

## Alternativas consideradas

| Alternativa | Veredicto |
| --- | --- |
| Reemplazar `public` por `wms_review_v2` y migrar los datos | **Descartada por ahora**: destructiva y de gran alcance; rompería la operación mientras no existan adaptadores v2. |
| Esquema paralelo en la misma base | **Adoptada**: no destructiva, control de versiones y migración gradual en fase 2. |
| IDs `VARCHAR(36)` para igualar `public` | **Aplazada**: se mantiene `uuid` nativo del volcado; la unificación de tipos se decide al migrar los adaptadores. |
| Base de datos separada | Descartada: multiplica el coste operativo sin aportar separación conceptual para este laboratorio. |

## Evolución prevista (fase 2)

1. `Database.php`: cualificación de esquema configurable (`WMS_REVIEW_V2_SCHEMA`) —
   **hecha** para el catálogo (`uom`, `item_family`, `item`); estado y operaciones
   siguen en `public`.
2. Reescribir los repositorios PDO/JDBC contra `wms_review_v2`: **hecho** en PHP para
   `item`, `item_family`, `uom`; pendiente operaciones de stock y estado (Java aparcado).
3. Ajustar `DatabaseTestCase`, scripts Bruno y `verify.sql` — **hecho** para el
   catálogo PHP; Bruno sobre base v2 limpia (004, sin semillas).
4. Reconciliación de tipos de ID: **parcial**. El VO `UuidV7Id` acepta ahora versiones
   7 y 8 (los seeds de la 005 usan v8); la unificación plena se decide al migrar el
   resto de adaptadores.
5. Actualizar `docs/domain/` y este documento con el comportamiento efectivo —
   **hecho** para el catálogo (ver `item-api.md`, `uom-api.md`, `item-family-api.md`).

## Comprobación

Tras la instalación: 27 tablas, 27 PK, 18 UNIQUE, 45 FK, 51 CHECK, 2 índices
únicos parciales, 2 funciones, 2 triggers y 13 comentarios en `wms_review_v2`;
conteos de filas por tabla coinciden con el volcado; `./bin/migrate.sh`
re-ejecutado completo sin errores (idempotencia).
