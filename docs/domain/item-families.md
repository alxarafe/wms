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

Los atributos se guardan como vínculos en `item_family_attribute`. `NEUTRAL` se crea sin vínculos. Los códigos de atributo deben existir en `attribute` con `target_type = 'FAMILY'`; por ejemplo, `COLD` es un atributo de ubicación y no puede asignarse a una familia.

Estas familias son datos de prueba creados mediante la API. Este documento no define reglas adicionales de compatibilidad o movimiento de stock.
