# Familias de artículo iniciales

## Requisito confirmado

Se crean estas cinco familias y se vinculan con los atributos de tipo `FAMILY` indicados:

| Código | Nombre | Atributos |
| --- | --- | --- |
| `REFRIGERATED_FOOD` | Alimentos refrigerados | `IS_FOOD`, `IS_REFRIGERATED` |
| `FROZEN_FOOD` | Alimentos congelados | `IS_FOOD`, `IS_FROZEN` |
| `DRY_FOOD` | Alimentos secos | `IS_FOOD` |
| `CHEMICAL` | Productos químicos | `IS_CHEMICAL` |
| `NEUTRAL` | Productos neutros | Ninguno |

Los atributos de familia se guardan como vínculos en `family_storage_attribute` hacia el
catálogo `storage_attribute` (esquema `wms_review_v2`). Los códigos de atributo deben
existir en ese catálogo para poder asignarse a una familia; un atributo desconocido
devuelve `400` con `{"error":"Unknown FAMILY attribute: <CODE>"}`.

Estas familias son datos de prueba creados mediante la API. Este documento no define
reglas adicionales de compatibilidad o movimiento de stock.

**Estado de los atributos en la implementación vigente**: el catálogo `storage_attribute`
se siembra con semillas (`005`) solo en entornos de desarrollo. En las bases de prueba
Bruno (montadas con la migración 004, sin semillas) el catálogo está vacío y las familias
se crean sin atributos (`attributes: []`). La asignación de atributos recuperará su
cobertura cuando exista un mecanismo para poblar `storage_attribute` sobre una base v2
limpia; los códigos `IS_*` de la tabla son el objetivo previsto para esas altas.
