# Configuración de almacenes y capacidades — PHP v2

## Reglas implementadas en la primera entrega

El almacén tiene un código único y un formato de representación independiente de
su UUID. El formato define si utiliza zonas, si las incluye en el código, el
separador y las anchuras de calle, posición y altura. Incluir zona requiere usar
zonas. Después de crear la primera calle no se permite cambiar el formato por
edición ordinaria; reenviar el mismo formato no constituye un cambio.

Un **tipo de HU** clasifica unidades físicas de manipulación; una **unidad de
medida** cuantifica mercancía. Sus catálogos son distintos. Los códigos de tipo
HU son globales. Los **tipos de hueco** pertenecen a un almacén y sus códigos solo
son únicos dentro de él. Dos almacenes pueden configurar de forma diferente
un tipo con el mismo nombre. Ningún código activa reglas por sí mismo.

La **política tipo de hueco/tipo HU** es única para esa pareja. Declara admisión de
HU completas y/o parciales, permiso de extracción de contenido y permiso de
despacho íntegro. Debe admitir al menos uno de los dos estados; el despacho íntegro
exige admisión de completas. El tipo de hueco de una política debe corresponder
al almacén que se está configurando. No se inventa una pertenencia a almacén para
los tipos HU, que son globales.

`max_locations_per_item = null` declara ausencia de límite por ese tipo;
un entero positivo declara el límite. Mezcla de artículos y lotes se expresa
mediante capacidades configurables. **Esta entrega persiste la configuración;
no aplica estas capacidades a mercancía ni implementa el motor operativo.**

El catálogo puede incluir tipos HU inactivos y sus políticas. Aún no existe
una operación nueva de recepción/ubicación que utilice o compruebe `is_active`.
No se ofrece borrado de tipos ni se pierde su identidad por una futura desactivación.

Contrato, ejemplos, validaciones de formato JSON y pruebas:
[configuración inicial PHP](../architecture/php-configuration-api.md).

## Límites aceptados del modelo revisado, aún sin operaciones v2

Estas decisiones proceden del diseño revisado que sirve de partida; no son
funcionalidades realizadas por crear las tablas:

- Una HU palé del MVP contendrá un artículo, un lote y un tamaño de caja; el número
  de cajas y unidades por caja serán los reales de esa HU y pueden variar entre
  palés del mismo artículo. No se impone una presentación estándar global.
- Una extracción para salida inmediata registrará la HU origen sin crear una HU
  caja intermedia. Si la caja debe almacenarse o moverse por separado, tendrá HU
  propia y el descuento del origen conservará la cantidad total.
- FULL/PARTIAL describe composición y es independiente de disponibilidad. La
  mercancía recibida con lote/caducidad exigibles ausentes seguirá siendo física,
  pero no se liberará para servir hasta completar requisitos y resolver retenciones.
- Los movimientos explicarán el contenido actual; los cambios de contenido y su
  historial deberán confirmarse juntos y protegerse frente a operaciones simultáneas.
- Los vacíos estructurales no generarán huecos ni comprimirán su numeración.
  Cualquier tipo de hueco podrá configurarse en cualquier altura.
- Una calle sin familias asignadas no añadirá restricción familiar; con familias,
  admitirá esas ramas y descendientes, además de las restantes compatibilidades.

## Evolución aplazada

Excepciones de atributos de hueco (`remove_attribute`), jerarquías HU multinivel,
cajas heterogéneas dentro de un palé, HU multirreferencia/multilote, capacidad por
peso/volumen/dimensiones, reservas de destino para tareas y renumeración excepcional
requieren casos de uso propios. No se implementan en este bloque.

Conservar UUID y separar tipos, capacidades y formato permite incorporarlas
mediante incrementos del modelo y migraciones. Jerarquías/contenido heterogéneo
afectarían contabilización y movimientos (impacto alto); reservas y capacidad
añadirían políticas y coordinación operativa (medio/alto); renumerar mantendría
identidades pero afectaría etiquetas e integraciones (alto). Se revisarán cuando
un caso de uso confirmado justifique ese coste. Mientras tanto, se evita anticipar
tablas o servicios adicionales más allá del esquema v2 ya existente.

El documento anterior `docs/handling_units.md` recoge otro modelo objetivo con
restricciones nominales. No gobierna esta API configurable. Su conciliación
operativa con v2 queda pendiente antes de implementar movimientos; no se mezclan
silenciosamente ambos modelos. La primera entrega se detiene en configuración.
