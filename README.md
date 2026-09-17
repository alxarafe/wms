# Warehouse Management System (WMS) Monorepo

![PHP Version](https://img.shields.io/badge/PHP-8.4+-blueviolet?style=flat-square)
![Java Version](https://img.shields.io/badge/Java-21+-orange?style=flat-square)
![PHP CI](https://github.com/alxarafe/wms/actions/workflows/php.yml/badge.svg)
![Java CI](https://github.com/alxarafe/wms/actions/workflows/java.yml/badge.svg)
![Static Analysis](https://img.shields.io/badge/static%20analysis-PHPStan%20%2B%20Deptrac-blue?style=flat-square)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](https://github.com/alxarafe/wms/issues?utf8=✓&q=is%3Aopen%20is%3Aissue)

> 📚 También disponible en español: [README.es.md](README.es.md)

Warehouse Management System (WMS) designed as an exercise in strict hexagonal architecture and advanced domain modeling.

The repository implements the same domain using two independent stacks:

- PHP 8.4+ (Vanilla + Flight)
- Java 21 (Spring Boot adapters)

The WMS domain is under development in both stacks. Both APIs expose `GET /api/health` and `POST /api/item-families`; the latter creates families with `FAMILY` attributes in PostgreSQL. Bruno checks the shared contract against separate PHP and Java test databases.

---

## ARCHITECTURE LAYERS

This project distinguishes two identity layers:

### Conceptual layer (what the system provides)
- PHP API
- Java API
- Database

### Infrastructure layer (how it is implemented)
- php-app
- java-app
- database

Both layers represent the same system from different abstraction levels and must not be mixed in naming or documentation.

---

## OBJECTIVES

- Strict Hexagonal Architecture
- Domain-Driven Design (DDD)
- Test-Driven Development (TDD)
- Contract Testing
- Automated architectural enforcement
- PHP vs Java parity comparison on a shared domain

---

## REPOSITORY STRUCTURE

```text
/api-tests
/bin
/docker
/docs
/java
/php
```

---

## QUICK START

### Prerequisites

- Docker & Docker Compose v2

### Start the environment

```bash
./bin/start.sh        # Builds & starts all containers
```

### Run tests

```bash
# Inside containers:
./bin/php_test.sh     # PHPUnit (unit + integration + contract)
./bin/java_test.sh    # Maven (unit + architecture)
./bin/bruno_families_test.sh  # APIs + Bruno with isolated databases; requires PostgreSQL running

# Full pipeline:
./bin/ci_local.sh     # PHP tests + Java tests
```

### Direct commands (without Docker)

```bash
cd php && composer install && vendor/bin/phpunit
cd php && vendor/bin/phpcs && vendor/bin/phpstan analyse && vendor/bin/deptrac analyse
cd java && mvn test
```

### Apply database migrations

```bash
./bin/migrate.sh
```

### Access APIs

| Stack  | URL                        |
|--------|----------------------------|
| PHP    | http://localhost:8081/api/health |
| Java   | http://localhost:8082/api/health |
| DB     | postgresql://localhost:5432 |

The `POST /api/item-families` contract is documented in [docs/architecture/item-family-api.md](docs/architecture/item-family-api.md). After the Bruno script runs, its PHP and Java test APIs are available at `http://localhost:28081` and `http://localhost:28082`.

### Stop

```bash
./bin/docker_stop.sh
```

---