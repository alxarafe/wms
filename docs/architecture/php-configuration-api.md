# Configuración inicial por API — primera entrega PHP

## Alcance y estado

Esta entrega permite configurar desde una base PostgreSQL vacía **almacenes,
tipos de HU, tipos de hueco por almacén y sus políticas** mediante HTTP en PHP.
Incluye consulta de esos recursos y sustitución del formato del almacén antes
de su primera calle. Es un bloque del laboratorio WMS, no un WMS operativo completo.

Antes, estas tablas ya existían en la migración `004`, pero no tenían API. Ahora
Flight traduce HTTP, `ConfigureWarehouse` coordina el puerto
`ConfigurationRepository`, los objetos de `Domain/Configuration` validan las
invariantes y PDO persiste en `wms_review_v2`. Domain y Application no importan
Flight, Laravel, PDO ni PostgreSQL. Los errores de unicidad y referencias de SQL
se traducen a excepciones del puerto; los detalles SQL no se devuelven al cliente.

**Solo PHP:** no se han sincronizado contratos, migraciones, código ni pruebas
Java. La paridad para este bloque queda pendiente por instrucción expresa.
El catálogo PHP previo se conserva. Estado y movimientos antiguos continúan
usando `public` en las instalaciones que lo tengan; no consumen esta configuración.

## Migraciones y conservación del trabajo previo

El manifiesto `php/database/migrations.php` define toda la **línea PHP v2**:

| Migración | Tratamiento |
| --- | --- |
| `database/migrations/004_create_wms_review_v2_schema.sql` | Reutilizada sin modificar: esquema, tablas, claves, índices y triggers existentes. |
| `php/database/migrations/006_configuration_guards.sql` | Incremento PHP: nombres de tipos y separador no blancos; error estable del bloqueo; serialización con altas de calle. |
| `001` y `003` | Línea antigua `public`, excluida del bootstrap v2. `001` mezcla estructura y maestros de zonas, atributos y unidades. |
| `002` y `005` | Semillas de demostración; excluidas. No se ejecutan ni se borran para simular un inicio vacío. |

`php bin/migrate.php`, desde `php/`, aplica el manifiesto usando la conexión
`POSTGRES_*`. Registra versión y SHA-256 en `public.php_schema_migration`, dentro
de la misma transacción que cada archivo; un bloqueo asesor evita dos ejecutores
simultáneos. Repetir el comando omite las versiones aplicadas; modificar un SQL
ya registrado provoca error, sin reaplicarlo silenciosamente. Las transacciones
externas `BEGIN/COMMIT` de los SQL históricos se retiran al ejecutarlos para incluir
el registro en la misma transacción. No es un parser de SQL general.

No se reescribe `004` ni se trasplanta literalmente el SQL privado. Si ya existe
el esquema creado por `004` y aún no hay registro PHP, la migración idempotente
se vuelve a ejecutar y se registra. Los nuevos CHECK validan los datos existentes;
un dato incompatible detiene el incremento y requiere revisión, no se corrige ni
elimina automáticamente. No se ha certificado la migración de cualquier esquema
externo o de cualquier instalación histórica. No hay rollback destructivo automático.

No ejecutar después el lanzador histórico `bin/migrate.sh` sobre estas bases:
incluye semillas y reescribe funciones de `004`. Para esta línea utilizar el
ejecutor PHP. El esquema de esta línea es `wms_review_v2`; el manifiesto no crea
esquemas arbitrarios aunque otros adaptadores permitan configurar su nombre.

## Contrato HTTP

Los cuerpos son objetos JSON. Se rechazan campos desconocidos, raíces array o
escalar, UUID mal formados y tipos JSON incorrectos; no se convierten `"false"`
a booleano ni `"1"` a entero. Campos opcionales omitidos toman los valores descritos.
`null` solo es válido para `max_locations_per_item`. Los códigos se conservan
literalmente, con distinción de mayúsculas; no se infiere comportamiento de ellos.
Las longitudes se cuentan en caracteres Unicode; cadenas vacías, solo espacios o
con NUL no son válidas. No se introduce una normalización de códigos nueva.

| Ruta | Métodos | Cuerpo de creación / respuesta de consulta |
| --- | --- | --- |
| `/api/warehouses` | POST, GET | Alta de almacén / `{"warehouses": [...]}` |
| `/api/handling-unit-types` | POST, GET | Alta de tipo HU / `{"handling_unit_types": [...]}` |
| `/api/warehouses/{warehouse_id}/location-types` | POST, GET | Alta de tipo del almacén / `{"location_types": [...]}` |
| `/api/warehouses/{warehouse_id}/location-types/{location_type_id}/hu-policies` | POST, GET | Alta de política / `{"hu_policies": [...]}` |
| `/api/warehouses/{warehouse_id}/format` | PUT | Sustituye todo el formato y devuelve el almacén |

POST devuelve **201**, con el objeto completo y sus valores por defecto. Los UUID
se generan como v7 en PHP; se aceptan referencias v7/v8 por compatibilidad con el
dominio existente. Una política tiene identidad compuesta por los dos UUID, sin
un `id` artificial. GET y PUT devuelven **200**. Los listados se ordenan por código,
salvo políticas, ordenadas por UUID del tipo HU. No hay paginación en este bloque.

Errores JSON con un único campo `error` de tipo string:

| Código | Casos |
| --- | --- |
| 400 | JSON, campos, tipos, longitudes o invariantes inválidos. |
| 404 | Almacén, tipo de hueco o tipo HU referenciado inexistente. |
| 409 | Código o pareja de política duplicados; tipo de otro almacén; cambio de formato bloqueado. |

No hay endpoints para limpiar bases, borrar estos recursos, editar tipos o
desactivar tipos existentes. `is_active` puede fijarse al crear el tipo HU.
La configuración puede referenciar un tipo inactivo: activar/desactivar su uso
en operaciones será responsabilidad del bloque operativo, no se simula aquí.

### Almacén y formato

```json
{
  "code": "A",
  "name": "Principal",
  "aisle_digits": 1,
  "bay_digits": 2,
  "level_digits": 1,
  "uses_zones": false,
  "separator": ".",
  "include_zone_in_code": false
}
```

Código máximo 10 caracteres, nombre máximo 255. Las tres anchuras son obligatorias
y van de 1 a 9. `uses_zones` e `include_zone_in_code` valen `false` por defecto;
`separator` vale `.` y debe tener un carácter no blanco. Incluir zona exige usar
zonas. La identidad del almacén y las coordenadas futuras se separan del formato.

PUT recibe solo los seis campos del formato, con las mismas reglas y valores
por defecto; no es un parche parcial. Tras la primera calle no puede cambiar
ninguno; reenviar el formato idéntico sí está permitido. El caso de uso bloquea
el almacén, comprueba la existencia de calles y aplica la regla de dominio en
una transacción. El trigger refuerza la misma prohibición incluso en SQL directo.
El alta de calle bloquea ese mismo almacén antes de insertar, evitando que se
intercale con un cambio de formato. La renumeración excepcional queda aplazada.

### Tipos HU, tipos de hueco y políticas

Tipo HU: `code` (máximo 30), `name` (máximo 255), `is_active` (defecto `true`).
Código único global. No equivale a `uom`: un tipo HU `BOX` y una unidad `BOX`
son conceptos distintos aunque coincidan sus nombres.

Tipo de hueco: `code` (máximo 30), `name` (máximo 255),
`max_locations_per_item` (defecto `null`, entero positivo de 32 bits si se fija),
`allows_multi_sku` y `allows_multi_batch` (defecto `false`). Código único dentro
del almacén; otro almacén puede usar el mismo código y capacidades distintas.
El almacén se toma de la ruta; no se admite sobrescribirlo en el cuerpo.

Política: `handling_unit_type_id`, `accepts_full`, `accepts_partial`,
`allows_breakdown`, `allows_full_dispatch`. Todos los booleanos valen `false`
por defecto. Debe aceptar completas o parciales; permitir despacho íntegro exige
aceptar completas. La pareja tipo de hueco/tipo HU es única. El tipo de hueco
debe pertenecer al almacén de la ruta. Los nombres `PICKING`, `RESERVE`, `RETURNS`,
`PALLET`, `BOX`, `CUSTOM` y `CRATE` son únicamente datos de los escenarios Bruno.

## Pruebas reproducibles

Requisitos: Docker Compose, servicio `database` arrancado, imagen PHP local y
dependencias Composer existentes; imagen `usebruno/cli:4.0.0` local o descargable.

```bash
docker compose up -d database
docker compose build php-app
./bin/php_configuration_test.sh
```

El script recrea exclusivamente `wms_php_configuration_a_test` y
`wms_php_configuration_b_test` (lista fija), en el servicio PostgreSQL del proyecto.
Son **dos instancias PHP**, no una por stack. Inicia solo `php-config-a` y
`php-config-b`; no inicia ni detiene Java ni borra contenedores ajenos.
La recreación elimina el contenido anterior de esas dos bases de prueba; no
se conserva copia. Las bases de desarrollo y las otras bases Bruno se preservan.

Secuencia: recrear → ejecutar todas las migraciones de la línea PHP v2 dos veces
→ PHPUnit unitario e integración → recrear ambas bases otra vez → migrar
→ Bruno `bootstrap` → verificar SQL → fixture de bloqueo → Bruno `format-lock`
→ verificar SQL. La segunda recreación impide que fixtures PHPUnit alimenten
el recorrido HTTP. Los listados vacíos iniciales y los verificadores SQL detectan
semillas inadvertidas. Las altas solo usan UUID devueltos por HTTP.

Bruno compara las respuestas completas (campos, tipos, valores e identificadores)
y los listados posteriores con las altas. SQL contrasta las filas esperadas,
las referencias y los UUID v7, y comprueba que las tablas ajenas al bloque están
vacías. Negativos: duplicados de los cuatro recursos, referencias inexistentes,
almacén incompatible, políticas incoherentes, JSON y tipos inválidos, límites,
formatos inválidos y cambios de cada componente del formato bloqueado.

**Limitación explícita de esta entrega:** falta el endpoint de calles. Por ello
`format-lock-fixture.sql` inserta una única calle en A **después** del bootstrap
HTTP y exclusivamente para probar el rechazo del cambio de formato. No crea
almacenes, tipos ni políticas ni alimenta las altas de configuración. Sustituir
esta fixture por un POST de calle en el bloque 3; entonces el recorrido completo,
incluido este caso negativo, se podrá preparar únicamente mediante HTTP.

Informes JSON en `/tmp/wms-php-configuration.*/`. Las APIs y bases quedan disponibles
para inspección en `http://localhost:28181` y `http://localhost:28182`; en Bruno
abrir `api-tests/bruno/configuration` y usar `php-local-a` o `php-local-b`.
El lanzador es el punto de entrada repetible: ejecutar la colección sobre datos
ya creados debe producir conflictos, no limpiar la base por HTTP.

## Simplificaciones, alternativas y siguientes entregas

- Se conserva `public` como implementación operativa anterior y `wms_review_v2`
  como modelo revisado. La configuración usa `WarehouseConfiguration` porque el
  `Warehouse` antiguo aún pertenece a otra topología. Unificarlos ahora exigiría
  adaptar estado/movimientos fuera del encargo. Al migrarlos se podrá retirar la
  representación antigua y conservar estos UUID y contratos.
- Un puerto agrupa las cuatro colecciones de configuración y el bloqueo necesario
  para el formato. Separarlo en repositorios pequeños se aplaza hasta que haya
  casos de uso que necesiten límites independientes; no se añade un framework CRUD.
- Reutilizar `004` evita duplicar un esquema ya trabajado. Un baseline nuevo más
  pequeño reduciría tablas sin uso, pero perdería continuidad y exigiría mantener
  dos DDL; se aplaza hasta una migración controlada del legado.
- El límite por artículo, la mezcla y las políticas quedan **configurados, no
  evaluados contra mercancía**. Autorizaciones, ocupación, transferencias,
  recepción, expedición y motor de reglas no forman parte de esta entrega.
- La herencia y excepciones de atributos, capacidad física avanzada, jerarquías
  HU, reservas de destino y renumeración de etiquetas siguen aplazadas. Véanse las
  limitaciones del modelo en [configuración de dominio](../domain/warehouse-configuration.md)
  y `wms-review-v2-schema.md`. El modelo objetivo anterior de `../handling_units.md`
  necesita conciliación antes de abordar operaciones; no gobierna esta API.
- La autenticación/autorización y paginación no se añaden en este laboratorio;
  no se declara preparación para producción.

Secuencia prevista, sin ejecutar aquí los bloques siguientes:

1. **Esta entrega:** migraciones, almacenes, tipos HU, tipos de hueco y políticas.
2. Zonas, familias jerárquicas, atributos, asignaciones y reglas de almacenamiento.
3. Calles, cuadrículas, alturas, vacíos, familias admitidas y generación de huecos.
4. Completar la integración de artículos/unidades existentes y el escenario
   funcional del MVP que recorra la configuración completa.

El motor operativo y sus decisiones pendientes necesitan su propio alcance;
crear sus tablas o políticas no equivale a implementar las operaciones.

## Evidencia de esta entrega (22/09/2026)

- Línea base previa: 174 pruebas unitarias PHP, 268 aserciones.
- Suite final: 188 pruebas unitarias/smoke, 321 aserciones.
- Integración de configuración: 1 escenario con 19 aserciones en cada base;
  incluye persistencia, rollback, trigger directo y bloqueo con dos conexiones.
- Bruno: 50 peticiones de bootstrap + 9 de bloqueo por base (118 en total),
  todas correctas, con comprobación SQL antes y después de la fixture.
- El recorrido completo terminó correctamente dos veces consecutivas; la segunda
  recreó bases que contenían los datos y la fixture de la primera.
- PHPStan nivel 8 sobre src/tests: sin errores. PHPCS PSR-12 sobre archivos de
  esta entrega: correcto. Deptrac: cero violaciones; conserva dependencias
  externas no cubiertas por sus reglas, por lo que no certifica todo el diseño.
- Shell validado con `bash -n` y diff revisado con `git diff --check`.

No se ejecutaron pruebas Java ni la suite de paridad, por el alcance autorizado.
No se validaron operaciones del MVP completo, migración de datos de producción
ni reconciliación con el modelo operativo anterior. Las ampliaciones aplazadas
siguen sin implementar.
