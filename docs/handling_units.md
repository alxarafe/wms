# Handling Units, presentaciones logísticas y stock canónico

## 1. Estado, propósito y alcance

**Alcance respecto a PHP v2 (22/09/2026):** este documento conserva un modelo
objetivo operativo anterior. La nueva [configuración PHP v2](architecture/php-configuration-api.md)
usa tipos HU y tipos de hueco configurables: `PALLET`, `BOX`, `PICKING` y `RESERVE`
son datos, no tipos cerrados ni condiciones en el código. Las diferencias sobre
contenido, presentaciones y políticas deberán conciliarse antes de implementar
operaciones v2. Véanse [las reglas de esta entrega](domain/warehouse-configuration.md).

Este documento es la fuente de verdad funcional y conceptual del **modelo objetivo** de Handling Units (HU) del laboratorio WMS. La migración aún no se ha implementado: el esquema, las APIs, ambos dominios y las pruebas conservan por ahora el comportamiento anterior descrito en `docs/architecture/stock-operations-api.md` y `docs/architecture/location-capacity-and-replenishment.md`.

Las expresiones normativas «debe», «solo» y «no se permite» describen el comportamiento que tendrá que cumplir la futura implementación. Las secciones marcadas como **propuesta** o **cuestión pendiente** no son reglas aprobadas y no deben implementarse sin confirmación.

El objetivo es un modelo educativo sencillo y riguroso:

- stock contabilizado en la unidad base del artículo;
- palés y cajas físicamente separadas con identidad propia;
- reserva para palés y picking para cajas completas;
- desconsolidación conservativa;
- dos modalidades de expedición, sin unidades sueltas;
- ampliaciones profesionales documentadas, pero no implementadas.

## 2. Vocabulario

### 2.1 Handling Unit (HU)

Una HU es una unidad física manipulable y trazable como un todo dentro del WMS. Tiene identidad propia, tipo y estado. En este alcance solo existen dos tipos funcionales:

- **PALLET:** palé descargado y controlado individualmente;
- **BOX:** caja que ha sido separada físicamente de un palé.

Una HU no es una cantidad ni una unidad de medida. «Mover una HU» significa desplazar ese objeto físico completo sin cambiar su contenido.

### 2.2 Unidad base

Es la unidad canónica en la que se contabiliza el stock de un artículo. En el ejemplo discreto es `EA` (each/unidad individual). Todas las comparaciones, saldos, débitos y créditos de inventario se expresan en esa unidad.

La primera versión se centra en artículos discretos cuya base representa unidades individuales. El catálogo puede contener `KG`, `LTR` u otras unidades, pero su flujo de cajas/palés no queda definido hasta acordar factores fraccionarios, precisión y reglas de embalaje.

### 2.3 Unidad de medida (UoM)

Una UoM es un código de medida del catálogo, como `EA`, `BOX`, `PAL` o `KG`. Por sí sola no representa un objeto físico concreto ni tiene identidad.

`BOX` y `PAL` son presentaciones dependientes del artículo: una caja de un artículo puede contener 8 EA y la de otro 12 EA. El código global no basta; hace falta la conversión del artículo.

### 2.4 Presentación logística

Es una forma cuantificada de presentar un artículo. Por ejemplo:

- 1 BOX del artículo A = 8 EA;
- 1 PAL del artículo A = 192 EA.

Mientras una caja permanece dentro del palé, «BOX» es una presentación calculable del contenido; no implica que exista una HU BOX.

### 2.5 Embalaje

Es el soporte físico que contiene o protege mercancía. Un embalaje no tiene por qué estar identificado ni gestionado como HU. En este modelo:

- una caja interna es embalaje y presentación, pero no HU;
- al separarse físicamente para picking, esa caja pasa a ser además HU BOX;
- el palé descargado sí es HU PALLET.

### 2.6 Lote

Es la identidad de lote del artículo cuando este se gestiona por lotes. «Monolote» significa que una HU solo contiene un lote. Para un artículo no gestionado por lote, la ausencia de lote (`NULL`) representa la única combinación permitida; no se inventa un lote sintético salvo decisión futura.

### 2.7 Contenedor de transporte

El contenedor recibido no es HU en la primera versión. Si se necesita trazabilidad de transporte, podrá aparecer como referencia de una recepción o documento, sin participar en stock, ubicaciones ni jerarquía HU. Modelarlo como HU padre es una alternativa futura.

## 3. Separación de conceptos

| Concepto | Tiene identidad física | Contiene stock canónico | Ejemplo |
| --- | --- | --- | --- |
| Unidad base | No | Es la unidad de contabilización | EA |
| UoM | No | No; define una medida | BOX |
| Presentación logística | No | Equivale a N unidades base | 1 BOX = 8 EA |
| Embalaje interno | No necesariamente | Su contenido pertenece al palé | Caja aún dentro del palé |
| HU PALLET | Sí | Sí, en unidad base | Palé P1 con 192 EA |
| HU BOX | Sí | Sí, en unidad base | Caja B1 con 8 EA |
| Contenedor de transporte | No en este alcance | No | Referencia de recepción |

## 4. Reglas confirmadas del modelo objetivo

### 4.1 Stock canónico

1. Toda cantidad de `stock_quant` representa unidades base del artículo.
2. La presentación recibida o expedida no cambia la unidad del saldo.
3. Una conversión solo interpreta una presentación; no crea ni destruye stock.
4. Toda operación debe usar aritmética exacta a la precisión acordada.
5. La suma global por artículo y lote antes y después de una desconsolidación debe ser idéntica.

La columna actual `stock_quant.unit` no satisface estas reglas porque es texto libre. Su transformación concreta se decidirá en la migración; funcionalmente, la unidad del quant siempre es la base del artículo.

### 4.2 Conversión de presentaciones

Ejemplo:

```text
Artículo A
Unidad base: EA
1 BOX = 8 EA
1 PAL = 192 EA
1 PAL = 192 / 8 = 24 BOX
```

**Propuesta mínima pendiente de confirmación:** guardar una única conversión directa por presentación hacia la base (`BOX -> EA`, `PAL -> EA`) y derivar la relación entre presentaciones. No se almacenarán simultáneamente reglas redundantes cuya coherencia haya que mantener.

Para la primera versión, cada artículo tendrá como máximo una presentación BOX operativa. Varias presentaciones de caja son una alternativa futura.

### 4.3 HU PALLET

Cada palé descargado:

- es una HU `PALLET` independiente;
- tiene identidad propia;
- contiene un solo artículo y un solo lote;
- guarda su cantidad en unidad base;
- se ubica en una ubicación con rol `RESERVE`;
- ocupa en exclusiva su ubicación de reserva;
- puede trasladarse completo entre ubicaciones de reserva;
- puede expedirse completo directamente desde reserva;
- puede desconsolidarse extrayendo exclusivamente cajas completas.

No se extraen unidades sueltas directamente de un palé. La expedición completa del palé sigue siendo válida aunque su contenido sea residual después de desconsolidaciones y no forme un palé estándar completo.

**Cuestión pendiente:** la orden no fija si una recepción de PALLET debe equivaler exactamente a la conversión `PAL -> base` o puede registrar cualquier cantidad base positiva. Debe resolverse antes de cerrar el contrato de recepción.

### 4.4 Caja dentro del palé

Una caja que todavía forma parte del palé:

- no tiene identidad HU;
- no ocupa una ubicación propia;
- no aparece como HU hija;
- es una presentación calculada a partir del stock base del palé y la conversión BOX del artículo.

Por tanto, un palé con 192 EA y conversión de 8 EA por BOX permite calcular 24 cajas completas, pero el sistema no crea 24 HU BOX mientras sigan dentro del palé.

### 4.5 HU BOX

Cuando una caja se separa físicamente del palé:

- se crea una HU `BOX` con identidad propia;
- conserva el artículo y lote del palé;
- contiene exactamente el factor BOX expresado en unidad base;
- se ubica en una ubicación con rol `PICKING`;
- se sirve siempre completa;
- no puede quedar abierta ni con contenido parcial.

Una BOX no puede contener 7 EA si la conversión es 8 EA, ni puede mezclar artículos o lotes.

### 4.6 Unidades individuales

Las unidades individuales:

- son la unidad base contable en el flujo discreto;
- no son HU;
- no se almacenan sueltas en picking;
- no se sirven individualmente en esta fase;
- no permanecen como sobrantes de cajas abiertas.

Una solicitud de salida por cantidad debe rechazarse si no corresponde a cajas completas, salvo cuando identifique la expedición completa de una HU PALLET.

## 5. Ubicaciones y ocupación

### 5.1 Reserva

Una ubicación `RESERVE`:

- admite como máximo una HU;
- esa HU debe ser `PALLET`;
- queda libre cuando el palé se traslada o expide;
- no admite BOX en el modelo inicial.

La regla se aplica por ubicación y no se deduce de `zone_type.allows_multi_sku`.

### 5.2 Picking

Una ubicación `PICKING`:

- contiene HU `BOX`;
- puede contener varias BOX;
- todas las BOX presentes deben compartir artículo y lote;
- no contiene PALLET ni stock suelto;
- no aplica todavía peso, volumen o dimensiones.

`allows_multi_sku=false` es compatible con esta regla: múltiples cajas del mismo SKU no son varios SKU. Ese campo no expresa número de HU ni homogeneidad de lote y no gobierna por sí solo la operación.

### 5.3 Capacidad por número de cajas

**Propuesta para la primera implementación:** aplazar `max_box_count`. No existe un requisito que permita elegir un límite correcto y el caso educativo puede probarse con homogeneidad artículo+lote.

La limitación debe quedar visible: «sin máximo configurado» no significa que el hueco tenga capacidad física infinita. Se añadirá una capacidad simple cuando un escenario real necesite impedir más de N cajas o cuando el visor deba representar ocupación finita. La capacidad física avanzada permanece fuera de alcance.

## 6. Operaciones y ciclo de vida

### 6.1 Recepción

El WMS empieza a controlar la mercancía en los palés descargados. La recepción inicial crea una HU PALLET por palé, con contenido canónico, y la coloca en RESERVE. No crea una HU para el contenedor ni todas las cajas internas.

**Propuesta:** no admitir recepción directa de BOX en la primera versión; una BOX nace de una desconsolidación. Casos como devoluciones de cajas requieren una decisión independiente.

### 6.2 Traslado completo de PALLET

El traslado:

- identifica una PALLET concreta;
- mueve la misma HU de una RESERVE origen a una RESERVE destino;
- no cambia artículo, lote ni cantidad;
- requiere destino libre y apto;
- registra el movimiento físico.

No equivale a desconsolidar ni a reponer picking.

### 6.3 Desconsolidación

La desconsolidación identifica:

- PALLET origen;
- número entero positivo de cajas;
- ubicación PICKING destino;
- conversión BOX vigente del artículo.

Para `n` cajas y factor `f` unidades base por caja:

```text
cantidad extraída = n × f
nueva cantidad PALLET = cantidad anterior PALLET - (n × f)
cantidad de cada BOX nueva = f
cambio global por artículo+lote = 0
```

La operación debe ser atómica: o se reduce el palé, se crean todas las BOX, se ubican y se registra el ledger, o no cambia nada.

Se rechaza cuando:

- la HU origen no es PALLET o no está en RESERVE;
- no existe conversión BOX directa y válida;
- no hay unidades base suficientes para todas las cajas;
- la cantidad solicitada no representa cajas completas;
- el destino no es PICKING;
- el destino contiene BOX de otro artículo o lote;
- alguna HU o ubicación no está disponible según la política de estados.

**Cuestión pendiente:** qué estado/histórico adopta la PALLET al extraer su última caja. No debe quedar una HU `AVAILABLE` sin contenido por accidente.

### 6.4 Expedición completa de PALLET

La expedición:

- identifica una PALLET concreta en RESERVE;
- retira la HU completa con todo su contenido actual;
- no exige que el contenido restante sea múltiplo de BOX;
- no extrae unidades ni cajas de ella durante la salida;
- reduce el stock interior por la cantidad base completa;
- conserva la trazabilidad histórica acordada.

### 6.5 Expedición de BOX

La expedición:

- identifica una o varias HU BOX en PICKING;
- consume cada HU completa;
- rechaza una fracción de caja;
- es todo-o-nada para el conjunto solicitado;
- reduce el stock interior por la suma exacta de las cajas expedidas.

Para evitar introducir asignación avanzada, la recomendación es que el contrato reciba las identidades de las BOX. Un contrato artículo+cantidad obligaría al WMS a seleccionar orígenes y queda pendiente.

## 7. Transiciones de estado funcional

La tabla describe estados conceptuales. Los nombres persistidos definitivos —por ejemplo `SHIPPED` o `EMPTY`— son una cuestión pendiente.

| Operación | Estado previo | Estado posterior | Efecto sobre stock interior |
| --- | --- | --- | ---: |
| Recepción | Palé no controlado | PALLET disponible en RESERVE | Aumenta en la cantidad base recibida |
| Traslado | PALLET disponible en RESERVE A | Misma PALLET disponible en RESERVE B | 0 |
| Desconsolidar N BOX | PALLET con cantidad suficiente | PALLET reducida + N BOX disponibles en PICKING | 0 |
| Expedir PALLET | PALLET disponible en RESERVE | PALLET fuera del almacén/histórica | Disminuye todo su contenido |
| Expedir BOX | BOX disponible en PICKING | BOX fuera del almacén/histórica | Disminuye exactamente su contenido |

Una HU expedida no forma parte del stock interior aunque se conserve su registro histórico. No debe aparecer como disponible para nuevas operaciones.

La semántica operativa de los estados actuales `AVAILABLE`, `IN_TRANSIT` y `BLOCKED` no queda redefinida por esta orden y debe confirmarse al diseñar cada caso de uso.

## 8. Relación entre ubicación, HU y stock

```text
RESERVE location
└── 0..1 HU PALLET
    └── exactamente 1 combinación item + batch
        └── quantity en unidad base

PICKING location
└── 0..N HU BOX
    ├── todas con el mismo item + batch
    └── cada BOX con quantity = factor BOX → unidad base
```

La ubicación contiene HU; la HU contiene stock. Una presentación calculada que sigue dentro del palé no ocupa ubicación ni tiene identidad.

El stock total interior de un artículo/lote es:

```text
Σ quantity de los quants pertenecientes a HU ubicadas y operativamente incluidas
```

El cálculo no multiplica por BOX o PAL, porque cada quant ya está en unidad base.

## 9. Invariantes

### 9.1 De stock y conversión

1. Toda cantidad persistida se interpreta en la unidad base del artículo.
2. La unidad textual de una petición no puede cambiar la unidad canónica.
3. Una conversión de presentación pertenece a un artículo concreto.
4. Una BOX contiene exactamente un factor BOX completo.
5. Una desconsolidación conserva la suma global del artículo y lote.
6. No se redondea una caja parcial para aceptarla.

### 9.2 De HU

1. Toda PALLET y toda BOX materializada tiene identidad propia.
2. Toda HU del alcance es monorreferencia y monolote.
3. Una HU tiene un único tipo: PALLET o BOX.
4. Una caja interna no es HU ni hija de la PALLET.
5. Una BOX nunca se consume parcialmente.
6. Una PALLET se traslada y expide completa; solo la desconsolidación reduce su contenido.

### 9.3 De ubicación

1. RESERVE contiene como máximo una PALLET.
2. PICKING contiene solo BOX.
3. Todas las BOX de una misma ubicación PICKING comparten artículo y lote.
4. La ubicación destino debe ser operativamente válida.
5. `allows_multi_sku` no sustituye estas invariantes.

### 9.4 De operación y ledger

1. Cada operación es atómica.
2. Una operación rechazada no deja efectos parciales.
3. El ledger es inmutable.
4. La desconsolidación registra débito y crédito cuantitativos correlacionados.
5. La expedición registra la cantidad base retirada y la HU expedida.
6. PHP y Java deben producir el mismo contrato, errores y estado persistido para escenarios comunes.

## 10. Ejemplos numéricos

### 10.1 Desconsolidación de una caja

Datos:

- base: EA;
- 1 BOX = 8 EA;
- PALLET P1 = 192 EA.

Resultado de extraer una caja:

| HU | Antes | Después |
| --- | ---: | ---: |
| PALLET P1 | 192 EA | 184 EA |
| BOX B1 | No existe | 8 EA |
| Total interior | 192 EA | 192 EA |

### 10.2 Desconsolidación de tres cajas

```text
extraído = 3 × 8 = 24 EA
PALLET = 192 - 24 = 168 EA
BOX = 3 × 8 = 24 EA
total = 168 + 24 = 192 EA
```

Las tres BOX pueden compartir una ubicación PICKING porque tienen el mismo artículo y lote.

### 10.3 Salida de cajas completas

Si cada BOX contiene 8 EA:

- solicitud de dos BOX concretas → 16 EA, válida;
- solicitud de 16 EA sin identificar BOX → funcionalmente múltiplo exacto, pero el contrato de selección sigue pendiente;
- solicitud de 10 EA desde picking → rechazada;
- solicitud de media BOX → rechazada.

### 10.4 Palé residual

Después de extraer cinco cajas de P1:

```text
P1 = 192 - (5 × 8) = 152 EA
```

No se pueden extraer unidades sueltas. P1 puede expedirse completa con sus 152 EA; esa salida es la excepción a exigir una cantidad múltiplo de BOX.

## 11. Identificación y SSCC

### 11.1 Estado actual que debe migrarse

El modelo actual ya tiene:

- `handling_unit.id`: UUID interno;
- `handling_unit.code`: obligatorio, único y de 18 dígitos en SQL;
- `Sscc`: VO que además valida el dígito de control;
- respuestas que exponen `huCode`.

Así, toda HU creada por las APIs obtiene un SSCC, pero SQL puede contener códigos de 18 dígitos que no son SSCC válidos. Las semillas actuales son un ejemplo.

### 11.2 Decisión pendiente y recomendación

No se presume que toda BOX necesite SSCC. Las alternativas son:

1. **SSCC obligatorio para todas las HU.** Es simple y conserva el contrato, pero impone una etiqueta GS1 a cada caja sin requisito que lo justifique.
2. **SSCC obligatorio solo para PALLET.** Las BOX usan identidad/código interno; distingue mejor trazabilidad externa e interna.
3. **Identidad interna obligatoria y SSCC opcional para cualquier HU.** Es la opción más flexible y mantiene separadas ambas semánticas.

**Recomendación mínima:** conservar el UUID como identidad técnica, añadir o definir un identificador operativo interno estable y tratar SSCC como atributo opcional validado. La forma exacta del contrato y las columnas necesita confirmación antes de implementar.

## 12. Operaciones prohibidas en esta fase

No se permite:

- servicio de unidades sueltas;
- extracción de unidades sueltas desde PALLET;
- consumo parcial de BOX;
- cajas abiertas;
- sobrantes o stock suelto en PICKING;
- PALLET en PICKING;
- BOX en RESERVE;
- mezcla de artículo o lote dentro de una HU;
- mezcla de artículo o lote en una ubicación PICKING;
- creación anticipada de todas las cajas como HU;
- usar el contenedor de transporte como HU;
- packing, cartonización o HU outbound;
- reservas/asignación avanzada;
- selección automática de stock;
- reposición automática;
- capacidad por peso, volumen o dimensiones.

## 13. Simplificaciones y limitaciones conocidas

- Solo se contemplan PALLET y BOX.
- Una caja tiene un único tamaño operativo por artículo.
- El flujo inicial de cajas/palés se limita a artículos discretos.
- No hay cajas abiertas ni unidad suelta.
- No hay HU hijas ni jerarquía multinivel operativa.
- La ubicación PICKING no tiene todavía máximo de cajas propuesto.
- No se modelan peso, volumen, dimensiones ni compatibilidad de embalaje.
- No existe asignación de pedidos, olas, tareas ni FEFO en este alcance.
- El contrato API definitivo y los estados persistidos de cierre/expedición siguen pendientes.
- La admisibilidad de `AVAILABLE`, `IN_TRANSIT` y `BLOCKED` por operación sigue pendiente.
- La identificación SSCC todavía no está decidida.
- El modelo no se debe presentar como solución completa de un WMS industrial.

## 14. Alternativas futuras no implementadas

| Alternativa | Utilidad | Complejidad | Impacto conceptual | Componentes probables | Condición para adoptarla |
| --- | --- | --- | --- | --- | --- |
| Servicio de unidades sueltas | Preparar cantidades menores que una caja | Alta | La unidad base pasa a ser unidad física servible | HU/stock, picking, salida, ledger, API, tests | Pedidos reales exigen EA y se define dónde reside el stock suelto |
| Cajas abiertas | Conservar una BOX parcialmente consumida | Alta | BOX deja de ser indivisible y necesita cantidad residual/estado | HU BOX, quants, salida, visor, inventario | Se aprueba consumo parcial con trazabilidad del sobrante |
| Stock suelto en picking | Mantener EA sin HU BOX | Alta | Ubicación podría contener stock con y sin HU | Modelo de stock, ubicación, picking, visor | Se diseña un contenedor lógico/físico para unidades sueltas |
| Contenedor como HU padre | Trazar el transporte completo y su descarga | Media-alta | Aparece jerarquía CONTAINER→PALLET | HU, recepción, jerarquía, movimientos, visor | Se necesita trazabilidad física del contenedor dentro del WMS |
| Creación anticipada de todas las HU BOX | Trazabilidad individual antes de separar | Media-alta | Las cajas internas ya son entidades hijas | HU, persistencia, capacidad, etiquetas, visor | Se etiquetan/controlan cajas desde recepción y compensa el volumen de datos |
| HU multinivel completas | Modelar container→pallet→box→otros bultos | Muy alta | Árbol de HU, ubicación heredada y operaciones recursivas | Dominio HU, SQL, movimiento, consultas, API | Un caso real exige mover/romper jerarquías de varios niveles |
| HU multirreferencia o multilote | Consolidar mercancía heterogénea | Alta | Desaparece la invariante de un quant por HU | HU, stock, picking, compatibilidad, salida | Operaciones reales necesitan palés/cajas mixtos y se define su selección |
| Varias presentaciones BOX por artículo | Gestionar cajas de 6, 8, 12, etc. | Media | BOX necesita identificar presentación concreta | Catálogo, conversiones, HU BOX, recepción, API | Un artículo se recibe/opera regularmente en más de un embalaje de caja |
| Capacidad por peso, volumen y dimensiones | Prevenir sobrecarga e incompatibilidad física | Muy alta | Capacidad deja de ser discreta | Item, HU, location, estrategia, persistencia, tests | Hay datos físicos fiables y decisiones de colocación que los necesitan |
| SSCC opcional frente a identificador interno | Separar trazabilidad interna de estándar GS1 | Media | Dos identidades con unicidades y exposición distintas | HU, esquema, API, etiquetas, migración | Se confirma qué tipos reciben SSCC y qué código escanea el operario |
| Reposición automática | Mantener picking por mínimos/máximos | Muy alta | Aparecen política, selección, tareas y confirmación | Replenishment, reservas, movimientos, scheduler, API | Existe demanda estable y reglas explícitas de origen/destino/prioridad |
| Packing y HU outbound | Consolidar cajas/unidades para expedición | Muy alta | Nacen HU de salida separadas del stock de almacenamiento | Pedidos, packing, HU, estaciones, etiquetas, ledger | El alcance incluye preparación y expedición consolidada |

Documentar una alternativa aquí no autoriza su implementación.

## 15. Cuestiones pendientes antes de implementar

1. Política de identidad: identificador interno y SSCC obligatorio/opcional.
2. Mantener `PAL` como UoM y `PALLET` como tipo de HU, o cambiar nomenclatura.
3. Confirmar conversiones directas a base y factores enteros para la primera versión.
4. Definir si toda recepción PALLET debe equivaler a una presentación PAL completa.
5. Definir estado e histórico de una PALLET vaciada y de toda HU expedida.
6. Elegir el modelo de ledger cuantitativo para desconsolidación.
7. Elegir contratos/endpoints y si las salidas identifican HU explícitas.
8. Confirmar el aplazamiento de capacidad máxima de BOX.
9. Decidir cómo tratar datos existentes no clasificables: reinicio controlado o mapeo manual.
10. Confirmar que `parent_hu_id` se conserva temporalmente sin uso en este flujo.
11. Definir qué estados HU permiten recepción, traslado, desconsolidación y expedición.

## 16. Criterios de aceptación de la futura migración

La migración no estará completa hasta que:

- esquema, dominio PHP, dominio Java, persistencia y API apliquen las mismas invariantes;
- los datos existentes hayan pasado un preflight o una estrategia de reinicio aprobada;
- una recepción persista siempre cantidad base;
- una reserva rechace el segundo PALLET;
- picking acepte varias BOX del mismo artículo/lote y rechace mezclas;
- la desconsolidación sea atómica y su ledger sume cero;
- la salida rechace unidades sueltas y BOX parciales;
- PALLET y BOX completas puedan expedirse según su rol;
- la proyección preserve la identidad de cada HU;
- escenarios HTTP comunes demuestren paridad PHP/Java;
- la documentación anterior incompatible haya sido actualizada.

Hasta entonces, este documento describe el objetivo aceptado, no una capacidad ya disponible.
