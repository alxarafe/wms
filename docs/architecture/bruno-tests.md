# Bruno: escenarios compartidos y ejecución PHP

## Decisión adoptada

Por instrucción del responsable, primero se valida PHP; cuando esté funcional se
trasladará su comportamiento a Java y se activarán allí las mismas pruebas Bruno.
Cada escenario se mantiene una sola vez, con `{{base_url}}`, organizado por
funcionalidad. Los entornos ejecutables actuales apuntan solo a PHP. Los scripts
preparan bases dedicadas, ejecutan HTTP y comprueban SQL; las peticiones compartidas
no conocen el lenguaje ni ejecutan PHPUnit o migraciones.

Antes se mantenían copias PHP y Java y los lanzadores de catálogo seleccionaban
archivos por su nombre. Ahora ejecutan la colección completa con un entorno PHP.
Se conservan todas las aserciones previas de PHP, incluidas las cantidades y
unidades de `state/` ausentes en Java. Salud pasa del formato antiguo al formato
HTTP ejecutable por la CLI 4.0.0. Configuración cambia de carpeta sin cambiar sus
59 escenarios ni su separación bootstrap/bloqueo.

En operaciones, la comparación PHP/Java se sustituye por un estado esperado
explícito, derivado de las semillas y las peticiones: tres entradas, una salida,
stock final, caducidades y una HU desvinculada. El SQL usa diferencias en ambas
direcciones, conserva multiplicidades y debe devolver cero. Conserva la
normalización de SSCC, representa el hueco vacío explícitamente y compara las
caducidades como instantes UTC. Detecta regresiones aunque Java no se ejecute.

## Alcance y limitaciones

- No se modifican contratos HTTP, reglas del dominio, código de las APIs ni
  migraciones. No se adapta Java ni se activan sus tests Bruno.
- Catálogo usa 004 sin semillas; configuración usa el manifiesto PHP v2 (004 y
  006) sobre dos bases distintas. Estado y operaciones siguen usando 001–003 en
  `public`, con semillas. Son regresiones del modelo anterior y no prueban todavía
  un recorrido completo desde configuración v2 hasta movimientos de stock.
- El lanzador conjunto ejecuta las suites secuencialmente porque catálogo y
  operaciones comparten `database_bruno_php`. Cada suite prepara el esquema que
  necesita. Las dos bases de configuración se recrean. No se alteran bases Java
  ni de desarrollo.
- Los escenarios son recorridos concretos; sus resultados no certifican todas
  las invariantes del WMS. La comprobación de operaciones verifica los seis
  huecos seleccionados, los dos lotes y los recuentos de movimientos/HU desligadas.
- PHPUnit, las migraciones y la fixture de calle siguen en el lanzador específico
  PHP de configuración. El pipeline general `ci_local.sh` queda fuera de este
  cambio y conserva sus comprobaciones de ambos stacks.

## Alternativas y evolución aplazadas

Mantener dos copias permitiría divergencias temporales, pero duplicaría el
mantenimiento y ya había producido diferencias de cobertura. Generar copias
introduciría herramientas y sincronización innecesarias. No se implementa ninguna
de estas alternativas; separar escenarios y entornos es suficiente y sencillo.

Cuando PHP esté funcional y se retome Java:

1. Adaptar su implementación al contrato y a los efectos persistidos aprobados en
   PHP; no copiar los archivos de escenarios.
2. Añadir entornos `java-local` y `java-docker` con la misma variable `base_url` y
   preparar bases independientes, sin reutilizar los datos de una ejecución PHP.
3. Ejecutar las mismas colecciones y comprobar sus efectos persistidos en ambos
   stacks, normalizando únicamente datos generados como UUID/SSCC. Cada ejecución
   debe capturar y usar sus propios identificadores.
4. Activar la matriz de paridad y registrar evidencia. Hasta entonces no se
   declara paridad verificada por esta suite.

Añadir entornos y otra invocación tiene un impacto pequeño en Bruno; adaptar
Java y su persistencia es un trabajo separado. Migrar estado y operaciones a v2
requiere revisar fixtures y expectativas cuando se autorice esa funcionalidad.
Si aparecen contratos intencionadamente distintos, habrá que documentarlos y
revisar qué escenarios siguen siendo comunes, sin relajar silenciosamente tests.

## Ejecución y evidencia

Los comandos, entornos y bases están descritos en
[las instrucciones de Bruno](../../api-tests/bruno/README.md).
Verificación del 22 de septiembre de 2026: 159 peticiones HTTP satisfactorias
(9 familias, 11 unidades, 9 artículos, 1 salud, 1 estado, 10 operaciones y
118 configuración en dos bases), con comprobaciones SQL de catálogo, operaciones
y configuración y PHPUnit del lanzador de configuración satisfactorios. Java
queda sin ejecutar por el alcance autorizado. La aplicación gráfica de Bruno
no se ha probado; las colecciones se han ejecutado con la CLI 4.0.0.

También se comprobó una desviación deliberada dentro de una transacción de la
base de pruebas: sumar una unidad al stock de P-A-04-01 produce dos diferencias
(una fila esperada ausente y una fila inesperada). Tras ROLLBACK, el verificador
vuelve a devolver cero. No se conservaron los datos alterados.
La comparación con Git confirmó que los 99 escenarios previos de catálogo,
estado, operaciones y configuración conservan su contenido funcional. Se
revisaron secuencias, uso de `base_url`, enlaces locales, sintaxis Bash y diff.
