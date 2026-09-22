# Documentación de Arquitectura

## Arquitectura Hexagonal (Puertos y Adaptadores)

Este proyecto sigue una estricta **arquitectura hexagonal** (también conocida como Puertos y Adaptadores). La idea central es aislar el dominio de negocio de las preocupaciones externas (bases de datos, frameworks web, APIs, etc.).

### ¿Por qué hexagonal?

- **Aislamiento del dominio**: la lógica de negocio no tiene dependencias de frameworks, bases de datos ni servicios externos
- **Capacidad de prueba**: la lógica de dominio puede probarse de forma aislada sin infraestructura
- **Independencia tecnológica**: puedes cambiar de base de datos, framework web o interfaz de usuario sin modificar las reglas de negocio
- **Consistencia multi-stack**: el mismo dominio puede implementarse en PHP y Java con un comportamiento idéntico

## Detalle de las capas

### Capa de Dominio (`src/Domain/`)

La capa más interna. Contiene lógica de negocio pura sin dependencias de frameworks ni de infraestructura.

**Contiene:**
- **Entidades**: objetos con identidad (p. ej., `User`, `Order`)
- **Objetos de valor**: objetos inmutables sin identidad (p. ej., `Email`, `Money`)
- **Agregados**: agrupación de entidades tratadas como unidad
- **Servicios de dominio**: lógica de negocio que no encaja naturalmente en una entidad
- **Eventos de dominio**: acontecimientos que ocurrieron en el dominio
- **Interfaces de repositorio**: contratos de acceso a datos (definidos aquí, implementados en Infrastructure)

**Reglas:**
- NO imports de frameworks
- NO acceso a base de datos
- NO preocupaciones HTTP
- NO dependencias de bibliotecas externas
- DEBE ser PHP/Java puro

### Capa de Aplicación (`src/Application/`)

Orquesta la lógica de dominio. Define los **casos de uso** de la aplicación.

**Contiene:**
- **Casos de uso / Servicios de aplicación**: coordinan objetos del dominio para realizar una tarea
- **Puertos de entrada**: interfaces que definen cómo el mundo exterior puede interactuar con la aplicación
- **Puertos de salida**: interfaces que definen cómo la aplicación se comunica con el mundo exterior
- **DTOs**: objetos de transferencia de datos de entrada/salida

**Reglas:**
- Depende SOLO de la capa de Dominio
- NO contiene lógica de negocio (la delega en el Dominio)
- NO contiene lógica de infraestructura
- Independiente de frameworks

### Capa de Infraestructura (`src/Infrastructure/`)

La capa más externa. Contiene todos los detalles de implementación técnica.

**Contiene:**
- **Controladores**: manejadores de peticiones HTTP
- **Persistencia**: implementaciones de repositorios (Doctrine, Eloquent, SQL puro, etc.)
- **Configuración del framework**: enrutado, middleware, proveedores de servicios
- **Clientes de servicios externos**: clientes de API, colas de mensajes, etc.

**Reglas:**
- Implementa las interfaces definidas en las capas de Aplicación y Dominio
- NO contiene lógica de negocio
- Puede usar frameworks, ORMs, clientes HTTP, etc.

## Patrón Puertos y Adaptadores

```
                      +----------+
                      |  Domain  |
                      | (Core)   |
                      +----+-----+
                           | Ports (interfaces)
                      +----v-----+
                      |Application|
                      | (Use Cases)|
                      +----+-----+
                           | Ports (interfaces)
              +------------+------------+
              |            |            |
         +----v---+  +----v---+  +----v---+
         |  HTTP  |  |   DB   |  |  Queue  |
         |Adapter |  |Adapter |  |Adapter  |
         +--------+  +--------+  +--------+
```

- **Puertos** = interfaces definidas en la capa de Aplicación
- **Adaptadores** = implementaciones en la capa de Infraestructura
- Los adaptadores se "enchufan" a los puertos mediante inyección de dependencias

## Cumplimiento arquitectónico

### PHP (Deptrac)

Configuración en `php/deptrac.yaml`:

- Capas: `Domain`, `Application`, `Infrastructure`
- `Domain` -> no se permiten dependencias
- `Application` -> puede depender de `Domain`
- `Infrastructure` -> puede depender de `Domain` y `Application`

Ejecutar: `vendor/bin/deptrac analyse`

### Java (ArchUnit)

Definido en `java/src/test/java/com/alxarafe/ArchitectureTest.java`:

- Usa `layeredArchitecture()` de ArchUnit
- Valida las mismas reglas de capas que Deptrac
- La capa Application es opcional mientras no haya casos de uso WMS; las restricciones de acceso siguen configuradas.
- Ejecutar: `mvn test` (parte de la suite de pruebas)

### PHP (PHPStan)

- Nivel 8 (máxima estrictez)
- Configurado en `php/phpstan.neon`
- Analiza tanto `src/` como `tests/`

### PHP (PHPCS)

- Estándar PSR-12
- Configurado en `php/phpcs.xml`

## Consistencia del contrato de API

La entrega de [configuración inicial PHP](architecture/php-configuration-api.md)
añade almacenes, tipos HU, tipos de hueco, políticas y edición de formato únicamente
a PHP. El puerto está en Application, las invariantes en Domain y los adaptadores
Flight/PDO en Infrastructure. Este bloque no tiene paridad Java; las dos bases de
su suite son PHP. La configuración v2 tampoco alimenta aún el estado/movimientos
antiguos de `public`.

Ambos stacks exponen `GET /api/health`, `POST /api/item-families`, `GET /api/warehouses/{id}/state`, `POST /api/receipts` y `POST /api/issues`. Las pruebas de contrato en `php/tests/Contract/` comprueban el estado de salud; las colecciones Bruno mantienen un escenario por contrato con `base_url` y se ejecutan actualmente solo contra PHP. Java queda aplazado hasta que PHP esté funcional; la [decisión de pruebas compartidas](architecture/bruno-tests.md) describe su activación posterior. El contrato de creación y sus bases de prueba aisladas se detallan en [API de creación de familias](architecture/item-family-api.md); el contrato de entrada y salida de stock (estados HTTP, precisión de cantidades M5, semántica de `stock_movement` M6 y resolución provisional M3) en [API de operaciones de stock](architecture/stock-operations-api.md).

## Pirámide de pruebas

```
        /\
       /  \
      / UI \              <- Pruebas de contrato (colecciones Bruno)
     /------\
    /Service\             <- Pruebas de integración (BD + adaptadores)
   /----------\
  /   Unit     \          <- Pruebas unitarias (lógica de dominio pura)
 /--------------\
```

## Entorno Docker

Servicios definidos en `docker-compose.yml`:

| Servicio | Contenedor | Puerto | Propósito |
|----------|-----------|--------|-----------|
| `php-app` | PHP 8.4 (Apache) | 28080 | API HTTP PHP |
| `java-app` | Java 21 (Temurin) | 38080 | API HTTP Java |
| `database` | PostgreSQL 16 | 5432 | Base de datos compartida |

## Cómo añadir una nueva funcionalidad

1. **Empieza por el Dominio**: define entidades, objetos de valor y servicios de dominio
2. **Define los casos de uso en Application**: crea servicios de aplicación y puertos
3. **Implementa los adaptadores en Infrastructure**: crea controladores, repositorios, etc.
4. **Escribe pruebas en cada nivel**: Unidad -> Integración -> Contrato
5. **Añade pruebas de API**: actualiza las colecciones Bruno en `api-tests/`
6. **Verifica**: ejecuta `./bin/ci_local.sh`

## Decisiones tecnológicas

| Decisión | Justificación |
|----------|--------------|
| PHP Vanilla + Flight | Ligero, sin dependencia de un framework para la plantilla; los equipos pueden sustituir los adaptadores por Symfony/Laravel |
| Java + adaptadores Spring Boot | Estándar de la industria para Java empresarial; los adaptadores pueden sustituirse por Quarkus/Micronaut |
| PostgreSQL | Muy utilizado, gran soporte DDD (JSON, enums, UUID) |
| Deptrac + ArchUnit | Automatizan el gobierno de la arquitectura; fallan el CI ante violaciones de capas |
| PHPStan nivel 8 | Máxima estrictez del análisis estático |
| Bruno en lugar de Postman | Colecciones de API compatibles con Git, sin dependencia del proveedor |
