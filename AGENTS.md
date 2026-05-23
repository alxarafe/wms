# AGENTS.md — Hexagonal Template

## Project Overview

This is a **monorepo template** for building applications with strict **Hexagonal Architecture** + **Domain-Driven Design (DDD)**. It provides a reusable structure implementing the **same business domain** across two independent technology stacks:

- **PHP 8.4+** (Vanilla PHP + Flight router)
- **Java 21** (Spring Boot adapters)

The repository serves as a **scaffolding template**: when generating a new application, developers keep the structure, tooling, and conventions, and replace the example domain with their own business logic.

## Tech Stack

| Layer | PHP | Java |
|-------|-----|------|
| Language | PHP 8.4+ | Java 21 |
| Build tool | Composer | Maven |
| Testing | PHPUnit 13 | JUnit 5 |
| Static Analysis | PHPStan level 8 | — |
| Code style | PHPCS (PSR-12) | — |
| Arch enforcement | Deptrac 4.x | ArchUnit |
| Database | PostgreSQL 16 | PostgreSQL 16 |
| Containerization | Docker / Docker Compose | Docker / Docker Compose |

## Architecture: Hexagonal Layers

```
+----------------------------------------------+
|                 Infrastructure                |
|  (HTTP controllers, DB repos, framework)     |
+----------------------------------------------+
|                 Application                   |
|  (use cases, input/output ports)             |
+----------------------------------------------+
|                  Domain                       |
|  (entities, value objects, domain services)  |
+----------------------------------------------+
```

### Layer Dependency Rules

| Layer | Can depend on | Cannot depend on |
|-------|---------------|------------------|
| **Domain** | Nothing | Application, Infrastructure |
| **Application** | Domain | Infrastructure |
| **Infrastructure** | Domain, Application | Nothing (no layer depends on it) |

These rules are **automatically enforced** by Deptrac (PHP) and ArchUnit (Java).

### Layer Responsibilities

- **Domain**: Pure business logic. Entities, Value Objects, Aggregates, Domain Events, Domain Services. Zero external dependencies.
- **Application**: Use cases / application services. Orchestrates domain logic. Defines **ports** (interfaces) that Infrastructure implements.
- **Infrastructure**: Adapters for external concerns. Database repositories, HTTP controllers, framework configuration, third-party integrations.

## Directory Structure

```
/
+-- php/                    # PHP implementation
|   +-- src/
|   |   +-- Domain/         # Business entities, value objects
|   |   +-- Application/    # Use cases, ports
|   |   +-- Infrastructure/ # Controllers, persistence, framework
|   +-- tests/
|   |   +-- Unit/           # Isolated domain/application tests
|   |   +-- Integration/    # Component collaboration tests
|   |   +-- Contract/       # API contract & PHP/Java parity tests
|   +-- composer.json
|   +-- deptrac.yaml        # Architecture layer enforcement
|   +-- phpstan.neon        # Static analysis config (level 8)
|   +-- phpcs.xml           # PHPCS PSR-12 config
+-- java/                   # Java implementation
|   +-- src/
|   |   +-- main/java/.../domain/
|   |   +-- main/java/.../application/
|   |   +-- main/java/.../infrastructure/
|   +-- pom.xml             # Maven (ArchUnit, JUnit 5)
+-- api-tests/              # Contract tests (Bruno collections)
+-- docker/                 # Dockerfiles (PHP, Java, Postgres)
+-- docs/                   # Architecture & domain documentation
|   +-- architecture/
|   +-- domain/
+-- bin/                    # Helper scripts
    +-- start.sh            # Start full environment
    +-- docker_start.sh
    +-- docker_stop.sh
    +-- docker_restart.sh
    +-- php_test.sh         # Run PHP tests in container
    +-- java_test.sh        # Run Java tests in container
    +-- php_shell.sh        # Shell into PHP container
    +-- java_shell.sh       # Shell into Java container
    +-- ci_local.sh         # Run full CI pipeline locally
```

## Coding Conventions

### PHP
- **Strict types**: `declare(strict_types=1)` on all files
- **PSR-12** coding standard (enforced by PHPCS)
- **PHPStan level 8** static analysis
- **No framework code in Domain or Application layers**
- **Namespace**: `Alxarafe\App\{Domain,Application,Infrastructure}\*`
- **Test namespace**: `Tests\{Unit,Integration,Contract}\*`

### Java
- **Package**: `com.alxarafe.app.{domain,application,infrastructure}`
- **ArchUnit** validates layer isolation at test time

## Available Commands

```bash
# Start environment
./bin/start.sh              # Start all Docker services
./bin/docker_start.sh       # Start Docker containers
./bin/docker_stop.sh        # Stop Docker containers
./bin/docker_restart.sh     # Restart Docker containers

# Run tests (inside containers)
./bin/php_test.sh           # Run PHPUnit tests
./bin/java_test.sh          # Run Maven/Java tests
./bin/ci_local.sh           # Run full CI (PHP + Java)

# Shell access (inside containers)
./bin/php_shell.sh          # Bash into PHP container
./bin/java_shell.sh         # Bash into Java container

# Direct commands (without Docker)
cd php && vendor/bin/phpunit
cd php && vendor/bin/phpcs
cd php && vendor/bin/phpstan analyse
cd php && vendor/bin/deptrac analyse
cd java && mvn test
```

## Testing Strategy

| Type | Location | Scope | Dependencies |
|------|----------|-------|-------------|
| **Unit** | `php/tests/Unit/` | Domain entities, value objects, domain services | None |
| **Integration** | `php/tests/Integration/` | Repositories, DB access, adapter collaboration | Database |
| **Contract** | `php/tests/Contract/` | HTTP APIs, request/response formats, PHP/Java parity | Running services |

- Tests follow **TDD** approach
- Contract tests ensure both stacks expose **identical behavior**
- API test collections (Bruno) live in `api-tests/`

## CI/CD (GitHub Actions)

- **PHP workflow**: PHPCS → PHPStan → PHPUnit → Deptrac
- **Java workflow**: Maven test (with ArchUnit)
- Triggered on `push` and `pull_request` to all branches

## Using This Template

To generate a new application:

1. Clone this repository (or use GitHub template)
2. Rename PHP namespace (`Alxarafe\App` → `YourApp\App`)
3. Rename Java package (`com.alxarafe.app` → `com.yourorg.app`)
4. Model your domain in `Domain/` (entities, value objects)
5. Define use cases in `Application/`
6. Implement adapters in `Infrastructure/`
7. Write tests following the existing patterns
8. Update API collections in `api-tests/`
