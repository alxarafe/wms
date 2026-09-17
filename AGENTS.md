# AGENTS.md — Laboratorio WMS en PHP y Java

## 1. Propósito y estado del proyecto

Este repositorio es un proyecto incipiente de aprendizaje y experimentación para un sistema de gestión de almacenes (WMS). Parte de una plantilla de arquitectura hexagonal. El ejemplo de saludos se retiró; las APIs exponen actualmente una comprobación de salud, mientras que el dominio WMS sigue en desarrollo y aún no cuenta con casos de uso HTTP.

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

## 4. Stack y fuentes verificables

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

## 5. Arquitectura y dependencias

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

## 6. Equivalencia PHP y Java

Ambas implementaciones deben conservar el mismo contrato y las mismas reglas para la funcionalidad incluida en el encargo: entradas, respuestas, estados HTTP, errores y efectos persistidos.

Equivalencia funcional no exige copiar la estructura de clases ni utilizar las mismas bibliotecas. Utilizar código idiomático de cada lenguaje respetando los límites comunes.

No cambiar silenciosamente una implementación y dejar la otra con un contrato incompatible. Si se autoriza trabajar únicamente en un stack, indicar el alcance y cualquier diferencia pendiente.

La paridad debe demostrarse con escenarios comunes ejecutados contra ambas APIs. Un test unitario o dos pipelines separados no bastan por sí solos para demostrarla.

## 7. Estructura y convenciones

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

En PHP, utilizar `declare(strict_types=1)`, PSR-12 y los namespaces existentes `Alxarafe\App` y `Tests`. En Java, mantener el paquete base `com.alxarafe.app` y las convenciones existentes. No renombrar el proyecto ni sus paquetes salvo petición expresa.

## 8. Procedimiento de trabajo

1. Revisar el estado de Git y preservar los cambios preexistentes.
2. Leer los archivos e instrucciones pertinentes al encargo.
3. Contrastar la documentación con el código antes de asumir que coincide.
4. Reproducir el fallo cuando se trate de una corrección y sea viable.
5. Implementar el cambio mínimo que resuelva el objetivo completo.
6. Ejecutar las comprobaciones pertinentes y revisar el diff final.
7. Actualizar la documentación afectada e informar del resultado.

No borrar cambios ajenos ni ejecutar operaciones destructivas para limpiar el entorno. Commit, push y despliegue se realizan cuando formen parte de la autorización del usuario; no son un paso obligatorio de cualquier tarea.

## 9. Comandos y pruebas

Los scripts siguientes se ejecutan desde la raíz del repositorio, en el **anfitrión**. Los scripts de tests utilizan Docker para ejecutar comandos en los contenedores; requieren que estos estén arrancados.

```bash
./bin/start.sh
./bin/php_test.sh
./bin/java_test.sh
./bin/ci_local.sh
./bin/migrate.sh
./bin/docker_stop.sh
```

Revisar cada script antes de ejecutarlo si puede modificar o eliminar datos. Aplicar migraciones únicamente al entorno de desarrollo o pruebas pertinente al encargo.

Alternativas sin Docker, desde el directorio correspondiente y con dependencias instaladas:

```bash
# Desde php/
vendor/bin/phpunit
vendor/bin/phpcs
vendor/bin/phpstan analyse
vendor/bin/deptrac analyse

# Desde java/
mvn test
```

Las pruebas de integración necesitan la base de datos y configuración correspondientes. Las pruebas HTTP necesitan las APIs en ejecución. Consultar los archivos de configuración para determinar qué ejecuta realmente cada suite.

- Probar reglas y casos de uso sin infraestructura cuando sea posible.
- Probar adaptadores y persistencia con integración cuando el cambio lo requiera.
- Probar contratos y paridad con escenarios HTTP comunes.
- Para una corrección funcional, añadir una regresión que capture el fallo cuando resulte pertinente.
- No añadir pruebas que solo reproduzcan la implementación ni pruebas de código para cambios exclusivamente documentales.

Los workflows existentes son una referencia de comprobación, no una certificación de producción. No afirmar que se ejecutaron contratos, servicios o pruebas que no se hayan ejecutado realmente.

## 10. Documentación y entrega

Mantener separados el estado implementado, los objetivos y las propuestas. Documentar reglas concretas, ejemplos y restricciones; un índice de temas no sustituye la definición del dominio.

Evitar contradicciones entre este archivo, README y `docs/ARCHITECTURE.md`. Ante una discrepancia, identificarla, contrastarla con el código y corregirla dentro del alcance autorizado. Revisar enlaces, rutas, comandos y cierre de bloques Markdown.

Al finalizar, indicar en español:

- Qué cambió y qué objetivo cumple.
- Qué comprobaciones se ejecutaron y sus resultados.
- Qué quedó sin verificar y la razón.
- Qué decisiones o bloqueos siguen pendientes, si existen.

No declarar que el proyecto es seguro, profesional o listo para producción únicamente porque compila o pasan las pruebas. Sustentar cada conclusión en evidencia concreta.
