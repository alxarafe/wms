# Architecture Documentation

## Hexagonal Architecture (Ports & Adapters)

This project follows strict **Hexagonal Architecture** (also known as Ports & Adapters). The core idea is to isolate the business domain from external concerns (databases, web frameworks, APIs, etc.).

### Why Hexagonal?

- **Domain isolation**: Business logic has zero dependencies on frameworks, databases, or external services
- **Testability**: Domain logic can be tested in isolation without infrastructure
- **Technology independence**: You can swap databases, web frameworks, or UI without changing business rules
- **Multi-stack consistency**: The same domain can be implemented in PHP and Java with identical behavior

## Layer Details

### Domain Layer (`src/Domain/`)

The innermost layer. Contains pure business logic with absolutely no framework or infrastructure dependencies.

**Contains:**
- **Entities**: Objects with identity (e.g., `User`, `Order`)
- **Value Objects**: Immutable objects without identity (e.g., `Email`, `Money`)
- **Aggregates**: Cluster of entities treated as a unit
- **Domain Services**: Business logic that doesn't naturally fit in an entity
- **Domain Events**: Things that happened in the domain
- **Repository Interfaces**: Contracts for data access (defined here, implemented in Infrastructure)

**Rules:**
- NO framework imports
- NO database access
- NO HTTP concerns
- NO external library dependencies
- MUST be pure PHP/Java

### Application Layer (`src/Application/`)

Orchestrates domain logic. Defines the **use cases** of the application.

**Contains:**
- **Use Cases / Application Services**: Coordinate domain objects to perform a task
- **Input Ports**: Interfaces that define how the outside world can interact with the application
- **Output Ports**: Interfaces that define how the application communicates with the outside world
- **DTOs**: Data Transfer Objects for input/output

**Rules:**
- Depends ONLY on the Domain layer
- Contains NO business logic (delegates to Domain)
- Contains NO infrastructure logic
- Framework-agnostic

### Infrastructure Layer (`src/Infrastructure/`)

The outermost layer. Contains all technical implementation details.

**Contains:**
- **Controllers**: HTTP request handlers
- **Persistence**: Repository implementations (Doctrine, Eloquent, raw SQL, etc.)
- **Framework Configuration**: Routing, middleware, service providers
- **External Service Clients**: API clients, message queues, etc.

**Rules:**
- Implements interfaces defined in Application and Domain layers
- Contains NO business logic
- Can use frameworks, ORMs, HTTP clients, etc.

## Ports & Adapters Pattern

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

- **Ports** = interfaces defined in Application layer
- **Adapters** = implementations in Infrastructure layer
- Adapters are "plugged into" ports via dependency injection

## Architectural Enforcement

### PHP (Deptrac)

Configuration in `php/deptrac.yaml`:

- Layers: `Domain`, `Application`, `Infrastructure`
- `Domain` -> no dependencies allowed
- `Application` -> can depend on `Domain`
- `Infrastructure` -> can depend on `Domain` and `Application`

Run: `vendor/bin/deptrac analyse`

### Java (ArchUnit)

Defined in `java/src/test/java/com/alxarafe/ArchitectureTest.java`:

- Uses `layeredArchitecture()` from ArchUnit
- Validates same layer rules as Deptrac
- Run: `mvn test` (part of test suite)

### PHP (PHPStan)

- Level 8 (maximum strictness)
- Configured in `php/phpstan.neon`
- Analyzes both `src/` and `tests/`

### PHP (PHPCS)

- PSR-12 standard
- Configured in `php/phpcs.xml`

## API Contract Consistency

Both PHP and Java stacks expose identical API contracts. Consistency is validated through:

1. **Contract tests** (`php/tests/Contract/`): Shared behavioral tests run against both stacks
2. **Bruno collections** (`api-tests/bruno/`): API request/response definitions used across stacks

## Testing Pyramid

```
        /\
       /  \
      / UI \              <- Contract tests (Bruno collections)
     /------\
    /Service\             <- Integration tests (DB + adapters)
   /----------\
  /   Unit     \          <- Unit tests (pure domain logic)
 /--------------\
```

## Docker Environment

Services defined in `docker-compose.yml`:

| Service | Container | Port | Purpose |
|---------|-----------|------|---------|
| `php-app` | PHP 8.4 (Apache) | 8081 | PHP HTTP API |
| `java-app` | Java 21 (Temurin) | 8082 | Java HTTP API |
| `database` | PostgreSQL 16 | 5432 | Shared database |

## How to Add a New Feature

1. **Start with the Domain**: Define entities, value objects, and domain services
2. **Define use cases in Application**: Create application services and ports
3. **Implement adapters in Infrastructure**: Build controllers, repositories, etc.
4. **Write tests at each level**: Unit -> Integration -> Contract
5. **Add API tests**: Update Bruno collections in `api-tests/`
6. **Verify**: Run `./bin/ci_local.sh`

## Technology Decisions

| Decision | Rationale |
|----------|-----------|
| PHP Vanilla + Flight | Lightweight, no framework lock-in for the template; teams can swap in Symfony/Laravel adapters |
| Java + Spring Boot adapters | Industry standard for Java enterprise; adapters can be swapped for Quarkus/Micronaut |
| PostgreSQL | Widely used, strong DDD support (JSON, enums, UUID) |
| Deptrac + ArchUnit | Automate architecture governance; fail CI on layer violations |
| PHPStan level 8 | Maximum static analysis strictness |
| Bruno over Postman | Git-friendly API collections, no vendor lock-in |
