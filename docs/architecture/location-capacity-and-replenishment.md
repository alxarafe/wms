# Capacidad de ubicaciones y estrategias de reposición

**Estado:** decisión adoptada para el ejercicio de laboratorio (modelo discreto KISS) y registro de alternativas para producción. Las opciones de producción son **propuestas documentadas, no implementadas** y no forman parte del alcance actual. Las decisiones de dominio pendientes que afecten al comportamiento quedan señaladas explícitamente.

## 1. Principio aplicado: Navaja de Ockham

Para el ejercicio se implementa el **modelo más simple** que permita demostrar el cliente visual y las APIs equivalentes de PHP/Java. La complejidad de un WMS de producción (volumen, peso, dimensiones, reposición automática, slotting, antiguos modelos de capacidad por referencia) se documenta como alternativa, no se codifica.

Motivo: un modelo de capacidad complejo no añade valor observable al ejercicio y retrasa la verificación de entrada/salida, paridad PHP/Java y representación de calles.

## 2. Decisión adoptada para el ejercicio

Ocupación **discreta por Unidad de Carga** (`handling_unit` / SSCC) en **cualquier rol** de hueco (RESERVE, PICKING u otros):

| Aspecto | Valor |
| --- | --- |
| Regla | Un hueco admite **a lo sumo 1 HU completa** |
| Dimensiones del hueco | Asumimos la de **un palet**; no se contempla profundidad para más palets por hueco |
| Referencia | **Monoreferencia**: la HU constituye una única referencia (mercancía homogénea) |
| Cantidad | La cantidad de unidades dentro de la HU depende del artículo; **no** define la ocupación del hueco |
| Estado visual | `VACÍO` (sin HU), `OCUPADO` (1 HU) o `BLOQUEADO` |
| Representación | Un rectángulo único por hueco que conmuta: vacío / ocupado / bloqueado |

Reglas operativas derivadas:

1. **Una ubicación admite a lo sumo una HU.**
2. Esa HU contiene **una única referencia**.

Intentar colocar una segunda HU o una segunda referencia en un hueco ocupado es un **fracaso esperado** por la API (a rechazar con su motivo).

No se introduce `location.max_quantity`: la ocupación no es proporcional a cantidades, por lo que ese campo carece de función en este modelo. La cantidad por HU se muestra como dato informativo del contenido.

## 3. Representación visual en el cliente

Por cada hueco de una calle (`aisle`, `bay`, `level`, `code`):

| Aspecto | Datos del visor | Dibujo |
| --- | --- | --- |
| Hueco | ¿Existe HU? ¿hueco bloqueado? | Rectángulo único vacío / lleno / bloqueado (color de estado) |
| Contenido | Referencia, cantidad, unidad, lote (informativos) | Etiqueta o tooltip del rectángulo |
| Bloqueado | `location.status`, `aisle.is_blocked` | Marcado bloqueado (ver § 5, decisión M3) |

El cliente **no calcula reglas y no decide si un hueco está lleno**: envía la petición, recibe la respuesta de la API (éxito o fracaso con motivo) y actúa en consecuencia; para pintar, consume el endpoint de estado y dibuja lo que este devuelve.

Consecuencias de diseño:

- La decisión de ocupación/capacidad ("¿está completo el hueco?", "¿cabe esta entrada?") pertenece al **WMS (Domain + API)**, no al cliente.
- El contrato de operaciones se simplifica: petición + respuesta éxito/fracaso con motivo. El formato exacto del cuerpo de respuesta puede **decidirse a posteriori** sin afectar la estructura del cliente, que reacciona de forma genérica ante el resultado.
- El visor no depende de un modelo de capacidad proporcional: cada hueco se pinta según tenga o no HU y según su estado.

## 4. Efecto sobre el esquema y los contratos

### 4.1 Cambios de esquema

No se añade ninguna columna de capacidad a `location`. La regla "1 HU por hueco" no se impone por índice global (el requisito es monoreferencia por HU, no una restricción de tabla), sino que se **refuerza en el caso de uso de entrada** (Domain + API), donde se validan la HU presente y la referencia.

### 4.2 Dependencias con carencias ya detectadas

- `stock_quant.unit` es VARCHAR libre y no referencia `uom`; al no depender la ocupación de cantidades, solo es información, pero conviene resolver la carencia antes de dar por válido el dato como trazable.
- Precisión de cantidades (NUMERIC(18,6) en BD frente a float/double en dominio) y política ante consumo completo: deben fijarse antes de implementar entrada/salida (decisión M5). Afecta a la cantidad por HU, no a la ocupación del hueco.

### 4.3 Contrato del endpoint de estado

El endpoint de visor (`GET /api/warehouses/{id}/state`) debe devolver por hueco, al menos:

| Campo | Uso |
| --- | --- |
| `code`, `role`, `status` | Identificación y estado del hueco |
| `occupied` (HU presente) | Rectángulo discreto (vacío / ocupado) |
| `blocked` (hueco o pasillo) | Marcado bloqueado |
| Referencia y cantidad de la HU | Contenido informativo del hueco |

### 4.4 Escenarios de éxito y fracaso derivados

- Entrada en hueco vacío → **éxito** (HU ubicada, hueco `OCUPADO`).
- Entrada en hueco ocupado → **fracaso** (motivo: hueco ocupado).
- Entrada de una segunda referencia sobre la misma HU → **fracaso** (monoreferencia).
- Salida que desocupa el hueco → **éxito**; el hueco queda `VACÍO` en el visor.

Estos casos pasan a formar parte de la colección Bruno-contrato (ver `api-tests/bruno/families/`).

## 5. Relación con decisiones pendientes del dominio

| Pendiente | Impacto en este modelo |
| --- | --- |
| `RuleScope` AISLE/ZONE (M2) | No afecta al modelo de capacidad; define cuándo una entrada es o no compatible entre mercancías. **Pendiente** para los escenarios de coexistencia |
| Herencia de bloqueo de pasillo (M3) | El visor pinta `BLOQUEADO` si `location.status` lo es; queda pendiente si `aisle.is_blocked` debe heredarse a sus huecos |
| Precisión y consumo completo (M5) | Afecta a la cantidad por HU y a la semántica de salida. **Pendiente**, bloquea el contrato de entrada/salida |
| Semántica de `stock_movement` por tipo (M6) | Define `from_location_id`/`to_location_id` para OUTBOUND. **Pendiente**, bloquea el contrato de salida |

## 6. Opciones documentadas para producción (no implementadas)

Se registran como referencia oficial; ninguna se implementa en el ejercicio salvo instrucción expresa.

### Opción A: control por número máximo de unidades de carga

- **Aplica a:** estanterías convencionales de reserva, almacenes de profundidad doble (drive-in), transelevadores.
- **En BD:** `location.max_hu_count` (INT).
- **Invariante de dominio:** el caso de uso de traslado verifica `COUNT(HUs en location) < max_hu_count` antes de autorizar, consultando un puerto de repositorio.
- **Diferencia con el ejercicio:** generaliza "1 HU" a "N HU por hueco".

### Opción B: motor volumétrico y ponderal

- **Aplica a:** almacenes automáticos (AS/RS), estanterías ligeras, paquetería irregular o mercancía pesada.
- **En BD:** `location` → `capacity_weight_max`, `capacity_volume_max`, `max_height`, `max_width`, `max_depth`; `item`/`handling_unit` → `weight_gross`, `volume_per_unit`, dimensiones físicas.
- **Invariante de dominio:** `PutawayStrategy` valida estructura antes del movimiento:
  `Σ peso(HUs) ≤ capacity_weight_max` y `Σ volumen(HUs) ≤ capacity_volume_max`.
- **Diferencia con el ejercicio:** sustituye la capacidad discreta por restricciones físicas multidimensionales.

### Opción C: motor de reposición automática por mínimos/máximos

- **Aplica a:** gestión dinámica de picking en tiempo real.
- **En BD:** `replenishment_rule` (`item_id`, `source_zone_id`, `target_location_id`, `min_quantity`, `max_quantity`).
- **Flujo operativo propuesto:**
  1. La extracción reduce el stock por debajo de `min_quantity`; el dominio dispara el evento `ReplenishmentNeeded`.
  2. Un servicio de aplicación captura el evento y genera una **Tarea Operativa (`task`)**.
  3. El motor selecciona la HU más adecuada en reserva (criterio FEFO/FIFO por caducidad o lote) y ordena su traslado al destino de picking.
- **Aviso:** una tarea creada no equivale a mercancía movida; la confirmación de la reposición es un movimiento real.
- **Relación con pendiente:** se conecta con la decisión de "picking insuficiente con stock en reserva" documentada en `private/auditoria-2026-09-17/escenarios-api-y-visor-wms.md`.

### Opción D: reubicación dinámica y slotting ABC

- **Aplica a:** almacenes de alta rotación sin asignación fija de hueco.
- **Lógica:** análisis periódico del histórico de salidas (`stock_movement`) para clasificar artículos en A/B/C y reubicar los de mayor rotación en los niveles de acceso más rápidos (niveles bajos del pasillo).
- **Diferencia con el ejercicio:** requiere histórico significativo y una política de reasignación; fuera de alcance.

### Resumen comparativo

| Opción | Campo/estructura | Invariante clave | Complejidad | Uso previsto |
| --- | --- | --- | --- | --- |
| Ejercicio (adoptada) | 1 HU por hueco, monoreferencia | Hueco ocupado no admite más HU | Baja | Demo y aprendizaje |
| A | `location.max_hu_count` | `COUNT(HUs) < max_hu_count` | Baja-media | Estanterías de reserva |
| B | Peso/volumen/dimensiones | Sumatorios físicos ≤ capacidad | Alta | AS/RS y paquetería |
| C | `replenishment_rule`, `task`, eventos | Mínimos/máximos + FEFO/FIFO | Alta | Reposición dinámica |
| D | Análisis de `stock_movement` (ABC) | Reubicación periódica | Media-alta | Alta rotación |

## 7. Documentos relacionados

- `docs/domain/README.md` y `docs/architecture/README.md` (índices generales).
- `docs/architecture/item-family-api.md` (patrón de contrato API por endpoint).
- `database/migrations/001_create_wms_schema.sql` (esquema actual; `location` no tiene columna de capacidad).
- `private/auditoria-2026-09-17/informe.md` (decisiones pendientes M2, M3, M5, M6).
- `private/auditoria-2026-09-17/escenarios-api-y-visor-wms.md` (propuesta de escenarios y rutas para el visor).
- `private/04 Cliente de almacén - Propuesta y dudas.md` (análisis del cliente y dudas de implementación).