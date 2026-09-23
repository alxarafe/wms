# AGENTS.md — Laboratorio WMS en PHP y Java

## 1. Propósito y estado del proyecto

Este repositorio es un proyecto incipiente de aprendizaje y experimentación para un sistema de gestión de almacenes (WMS). Parte de una plantilla de arquitectura hexagonal. El ejemplo de saludos se retiró. PHP expone salud, catálogo, configuración inicial v2 y los endpoints antiguos de estado y movimientos sobre `public`. El dominio WMS sigue en desarrollo; Java queda aparcado hasta que PHP esté funcional. Los escenarios Bruno son compartidos y actualmente se ejecutan solo contra PHP; no se afirma paridad vigente.

Se mantienen dos implementaciones independientes, PHP y Java, para contrastar un mismo modelo conceptual y comportamiento observable. No debe presentarse el proyecto como un WMS terminado ni como una plataforma lista para producción sin evidencia que lo respalde.

El alcance actual es este laboratorio WMS. Una ampliación hacia un CORE de ERP, una plataforma SaaS u otros productos requiere una instrucción expresa del responsable del proyecto.

## 2. Idioma y comunicación

- Redactar documentación, decisiones, explicaciones e informes en español.
- Mantener identificadores de código, paquetes, namespaces y rutas API en inglés, siguiendo las convenciones existentes.
- Conservar términos técnicos habituales cuando aporten precisión y explicarlos si son necesarios para comprender el cambio.
- Explicar cada cambio con su propósito, comportamiento anterior y nuevo, y comprobaciones realizadas.
- No duplicar toda la documentación en inglés salvo petición expresa. Mantener coherentes las versiones del README que ya existan cuando el cambio las afecte.

## 3. Requisitos y decisiones pendientes

Las instrucciones expresas del usuario delimitan el trabajo. Este archivo guía la ejecución y no autoriza a ampliar el encargo.

Distinguir en los análisis y documentos:

- **Requisito confirmado:** comportamiento solicitado o aprobado expresamente.
- **Comportamiento existente:** lo que hace el código; no equivale por sí solo a una regla de negocio aprobada.
- **Propuesta:** alternativa razonada que todavía no es un requisito.
- **Cuestión pendiente:** decisión necesaria que aún no está resuelta.

No inventar reglas de almacén sobre stock negativo, reservas, lotes, ubicaciones, unidades, valoración o permisos. Cuando una decisión pendiente determine el comportamiento, exponer las alternativas y pedir la aclaración necesaria. Continuar las tareas independientes que ya estén autorizadas.

Resolver las elecciones técnicas rutinarias dentro del alcance aprobado, explicando las que tengan consecuencias relevantes. No introducir decisiones arquitectónicas de gran alcance bajo la apariencia de una corrección menor.

Registrar las decisiones relevantes en `docs/architecture/` y las reglas de negocio en `docs/domain/`. Si el encargo es exclusivamente de análisis, entregar propuestas y preguntas sin implementarlas.

## 4. Alcance educativo y documentación de decisiones

Este proyecto es un ejercicio serio de desarrollo de un WMS. No se exige implementar de inmediato toda la complejidad de un WMS industrial. Estas premisas se aplican a todas las tareas de análisis, diseño, implementación, migración, refactorización y corrección del WMS.

- Favorecer soluciones sencillas, coherentes, comprobables y adecuadas para aprender correctamente el dominio.
- No presentar una simplificación adoptada para el ejercicio como una regla universal de logística.
- No añadir complejidad especulativa ni funcionalidad sin un caso de uso que la justifique.
- No adoptar simplificaciones que creen deliberadamente un callejón sin salida cuando exista una alternativa sencilla y evolutiva.

### Separación entre implementación actual y evolución futura

Toda orden de trabajo debe distinguir expresamente:

- qué se implementa;
- qué no se implementa;
- qué comportamiento actual se modifica;
- qué simplificación se adopta;
- qué limitaciones conocidas conserva o introduce;
- qué alternativas relevantes se han considerado;
- qué opciones se aplazan;
- cómo podría evolucionar posteriormente la solución.

Documentar las alternativas aplazadas sin implementarlas salvo autorización expresa.

### Documentación obligatoria de cada cambio

Toda tarea que modifique comportamiento, dominio, arquitectura, contratos, esquema, API o persistencia debe actualizar también la documentación correspondiente. Explicar, según resulte aplicable:

- problema abordado;
- comportamiento implementado;
- decisión adoptada;
- justificación;
- invariantes afectadas;
- limitaciones;
- alternativas consideradas;
- ventajas e inconvenientes de las alternativas;
- motivo por el que no se implementan ahora;
- impacto aproximado de una futura evolución;
- condiciones que justificarían revisar la decisión;
- pruebas o evidencias que validan el comportamiento.

No considerar terminada una tarea si código, esquema, contratos, pruebas y documentación se contradicen.

### Criterio entre docs/ y private/

Guardar en `docs/` la documentación consolidada que forme parte de la fuente de verdad del proyecto:

- comportamiento realmente implementado;
- arquitectura vigente;
- modelo de dominio adoptado;
- invariantes;
- contratos de API;
- decisiones de diseño ya aceptadas;
- limitaciones conocidas de la solución vigente;
- extensiones futuras oficialmente previstas;
- ADR u otros documentos que gobiernen el código.

Guardar en `private/`:

- auditorías;
- análisis provisionales;
- conciliaciones entre propuestas y código real;
- investigaciones;
- deliberaciones;
- comparaciones de alternativas todavía no decididas;
- borradores;
- hipótesis;
- opciones descartadas que no deban formar parte de la documentación pública;
- informes internos de revisión.

`private/` no puede utilizarse como fuente de verdad del proyecto, especialmente si está excluida mediante `.gitignore`. Cuando una decisión estudiada en `private/` sea aceptada y pase a gobernar la implementación, trasladar o sintetizar su contenido normativo en `docs/`.

### Conservación y actualización documental

- Antes de crear un documento nuevo, buscar si ya existe uno que cubra el mismo concepto y actualizarlo cuando sea la fuente adecuada.
- Evitar documentos duplicados, contradictorios o con distinto vocabulario para el mismo concepto.
- No afirmar que una funcionalidad está implementada si solo existe en el esquema, en el dominio, en una propuesta o en una opción futura.
- Diferenciar expresamente entre: implementado y operativo; parcialmente implementado; modelado pero no utilizado; documentado como alternativa; pendiente de decisión; descartado.

### Decisiones simplificadas y alternativas

Cuando se elija una solución deliberadamente sencilla para este ejercicio, documentar:

1. la solución adoptada;
2. el motivo educativo o práctico;
3. la limitación que implica;
4. la alternativa o alternativas profesionales;
5. el impacto de adoptarlas;
6. el camino de migración posible.

Ejemplo conceptual, sin convertirlo en una decisión obligatoria del dominio: una única HU por ubicación como solución simplificada de aprendizaje; su limitación (no representa ubicaciones compartidas ni capacidades físicas complejas); alternativas profesionales (número máximo de HU, capacidad por peso o volumen y validación dimensional); y una evolución posible (incorporar una política de capacidad sin cambiar innecesariamente la identidad de ubicación y HU). Este ejemplo solo explica la regla documental; no autoriza a cambiar el modelo actual.

### Prohibición de sobrediseño

- No crear entidades, columnas, servicios, abstracciones o eventos únicamente «por si fueran necesarios».
- Documentar las posibilidades futuras antes de implementarlas.
- Conseguir la extensibilidad mediante separación correcta de conceptos, vocabulario coherente, invariantes explícitas, contratos claros y migraciones controladas.
- Una alternativa documentada no forma parte del alcance implementado.
- No ampliar unilateralmente el alcance porque una solución más completa parezca técnicamente superior.

## 5. Stack y fuentes verificables

La base actual utiliza:

| Elemento | PHP | Java |
| --- | --- | --- |
| Lenguaje | PHP 8.4 o superior según Composer | Java 21 según Maven |
| HTTP | Flight | Spring Boot |
| Dependencias y compilación | Composer | Maven |
| Pruebas | PHPUnit | JUnit y ArchUnit |
| Análisis | PHPStan y Deptrac | Reglas de ArchUnit |
| Estilo | PHPCS, PSR-12 | Convenciones existentes |
| Persistencia | PostgreSQL | PostgreSQL |

Consultar `php/composer.json`, sus archivos de bloqueo cuando existan, `java/pom.xml`, los Dockerfiles y los workflows para conocer versiones y compatibilidad efectivas. No actualizar lenguajes, frameworks o dependencias ajenos al encargo.

## 6. Arquitectura y dependencias

| Capa | Responsabilidad | Dependencias internas permitidas |
| --- | --- | --- |
| Domain | Entidades, objetos de valor y reglas de negocio | La propia capa |
| Application | Casos de uso y coordinación de operaciones | Domain y la propia capa |
| Infrastructure | HTTP, persistencia, configuración e integraciones | Domain, Application y la propia capa |

Domain y Application no deben depender de Infrastructure, Spring, Flight, ORM, acceso directo a base de datos ni objetos HTTP. La biblioteca estándar del lenguaje puede utilizarse cuando no introduzca efectos de infraestructura en el núcleo. Una biblioteca externa para el núcleo requiere justificar su función y compatibilidad con estos límites.

Application coordina las operaciones y puede contener políticas propias del caso de uso; las invariantes del negocio pertenecen a Domain. Infrastructure traduce protocolos y formatos y ejecuta operaciones técnicas, sin asumir las reglas de negocio.

No crear interfaces, servicios, eventos o agregados únicamente para completar una estructura teórica. Introducirlos cuando representen una responsabilidad o límite real.

### Ubicación de los puertos

- Los puertos de entrada que expresan casos de uso se ubican en Application.
- Los puertos de salida requeridos para coordinar un caso de uso se ubican en Application.
- Una interfaz de repositorio puede ubicarse en Domain cuando su contrato expresa una colección de agregados del dominio, sin detalles técnicos de persistencia.
- Los adaptadores concretos se ubican en Infrastructure.

Aplicar este criterio al código existente sin trasladar interfaces de manera masiva fuera del alcance del encargo. Documentar las excepciones justificadas.

### Alcance de las comprobaciones arquitectónicas

Deptrac y ArchUnit comprueban las reglas que estén configuradas. Su existencia o una ejecución satisfactoria no garantiza por sí sola la ausencia de dependencias externas ni la corrección del diseño.

Revisar qué paquetes y dependencias cubren las reglas antes de afirmar que un límite está protegido. Para cambios en esas reglas, comprobar que detectan una dependencia prohibida representativa. No presentar una intención documentada como una garantía automatizada ya demostrada.

## 7. Equivalencia PHP y Java

Ambas implementaciones deben conservar el mismo contrato y las mismas reglas para la funcionalidad incluida en el encargo: entradas, respuestas, estados HTTP, errores y efectos persistidos.

Equivalencia funcional no exige copiar la estructura de clases ni utilizar las mismas bibliotecas. Utilizar código idiomático de cada lenguaje respetando los límites comunes.

No cambiar silenciosamente una implementación y dejar la otra con un contrato incompatible. Si se autoriza trabajar únicamente en un stack, indicar el alcance y cualquier diferencia pendiente.

La paridad debe demostrarse con escenarios comunes ejecutados contra ambas APIs. Un test unitario o dos pipelines separados no bastan por sí solos para demostrarla.

## 8. Estructura y convenciones

| Ruta | Contenido |
| --- | --- |
| `php/src/Domain/` | Dominio PHP |
| `php/src/Application/` | Casos de uso y puertos PHP |
| `php/src/Infrastructure/` | Adaptadores PHP |
| `php/tests/` | Pruebas PHP |
| `java/src/main/java/com/alxarafe/app/` | Implementación Java por capas |
| `java/src/test/java/` | Pruebas Java |
| `api-tests/` | Colecciones y escenarios HTTP |
| `database/migrations/` | Migraciones de datos |
| `docker/` | Configuración de imágenes |
| `bin/` | Scripts de desarrollo y comprobación |
| `docs/domain/` | Vocabulario, reglas y casos de uso |
| `docs/architecture/` | Decisiones y límites técnicos |
| `private/` | Documentos provisionales y privados; no es fuente de verdad |

En PHP, utilizar `declare(strict_types=1)`, PSR-12 y los namespaces existentes `Alxarafe\App` y `Tests`. En Java, mantener el paquete base `com.alxarafe.app` y las convenciones existentes. No renombrar el proyecto ni sus paquetes salvo petición expresa.

## 9. Procedimiento de trabajo

1. Revisar el estado de Git y preservar los cambios preexistentes.
2. Leer los archivos e instrucciones pertinentes al encargo.
3. Contrastar la documentación con el código antes de asumir que coincide.
4. Reproducir el fallo cuando se trate de una corrección y sea viable.
5. Implementar el cambio mínimo que resuelva el objetivo completo.
6. Ejecutar las comprobaciones pertinentes y revisar el diff final.
7. Actualizar la documentación afectada e informar del resultado.

No borrar cambios ajenos ni ejecutar operaciones destructivas para limpiar el entorno. Commit, push y despliegue se realizan cuando formen parte de la autorización del usuario; no son un paso obligatorio de cualquier tarea.

## 10. Comandos y pruebas

Los scripts siguientes se ejecutan desde la raíz del repositorio, en el **anfitrión**. Docker es el entorno obligatorio para PHP en desarrollo y pruebas: no ejecutar `php`, Composer, PHPUnit, PHPCS, PHPStan ni Deptrac directamente con herramientas instaladas en el anfitrión. Los scripts PHP delegan en el servicio `php-app`.

```bash
# Arrancar o reconstruir los servicios necesarios.
./bin/start.sh

# Pruebas PHP: PHPUnit se ejecuta dentro de php-app.
./bin/php_test.sh

# Preparar dependencias y comprobar las herramientas PHP dentro de php-app.
./bin/bootstrap_php.sh

# Herramientas PHP, si se necesitan de forma individual.
docker compose exec -T php-app composer install --no-interaction
docker compose exec -T php-app vendor/bin/phpcs
docker compose exec -T php-app vendor/bin/phpstan analyse
docker compose exec -T php-app vendor/bin/deptrac analyse

# Otras comprobaciones del proyecto.
./bin/java_test.sh
./bin/ci_local.sh
./bin/migrate.sh
./bin/docker_stop.sh
```

`php-app` debe estar arrancado antes de usar `./bin/php_test.sh`, `./bin/bootstrap_php.sh` o los comandos `docker compose exec`. Si aparece `PHP container is not running`, ejecutar `./bin/start.sh`. Puede comprobarse el estado con `docker compose ps`. Los comandos `docker compose exec` deben ejecutarse desde la raíz del repositorio para usar el Compose correcto.

La excepción es el entorno de CI: los workflows pueden instalar y ejecutar PHP directamente dentro de su propio runner o contenedor; esa configuración no cambia el flujo local documentado aquí.

Revisar cada script antes de ejecutarlo si puede modificar o eliminar datos. Aplicar migraciones únicamente al entorno de desarrollo o pruebas pertinente al encargo. No se mantienen instrucciones de ejecución PHP sin Docker en este repositorio.

Para Java, si las dependencias están disponibles localmente o en su contenedor, la comprobación equivalente es:

```bash
# Desde java/ cuando se trabaje directamente con el entorno Java autorizado.
mvn test
```

Las pruebas de integración necesitan la base de datos y configuración correspondientes. Las pruebas HTTP necesitan las APIs en ejecución. Consultar los archivos de configuración para determinar qué ejecuta realmente cada suite.

- Probar reglas y casos de uso sin infraestructura cuando sea posible.
- Probar adaptadores y persistencia con integración cuando el cambio lo requiera.
- Probar contratos y paridad con escenarios HTTP comunes.
- Para una corrección funcional, añadir una regresión que capture el fallo cuando resulte pertinente.
- No añadir pruebas que solo reproduzcan la implementación ni pruebas de código para cambios exclusivamente documentales.

Los workflows existentes son una referencia de comprobación, no una certificación de producción. No afirmar que se ejecutaron contratos, servicios o pruebas que no se hayan ejecutado realmente.

## 11. Documentación y entrega

Mantener separados el estado implementado, los objetivos y las propuestas. Documentar reglas concretas, ejemplos y restricciones; un índice de temas no sustituye la definición del dominio.

Evitar contradicciones entre este archivo, README y `docs/ARCHITECTURE.md`. Ante una discrepancia, identificarla, contrastarla con el código y corregirla dentro del alcance autorizado. Revisar enlaces, rutas, comandos y cierre de bloques Markdown.

Al finalizar, indicar en español:

- archivos modificados;
- comportamiento incorporado o corregido y el objetivo que cumple;
- decisiones adoptadas;
- simplificaciones y limitaciones;
- alternativas documentadas;
- ubicación de la documentación creada o actualizada;
- comprobaciones y pruebas ejecutadas y sus resultados;
- lo que quedó sin verificar y la razón;
- cuestiones pendientes o bloqueos, si existen;
- confirmación de que no se implementaron alternativas aplazadas ni se amplió el alcance sin autorización.

No declarar que el proyecto es seguro, profesional o listo para producción únicamente porque compila o pasan las pruebas. Sustentar cada conclusión en evidencia concreta.
