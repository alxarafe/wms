# Plantilla Hexagonal Monorepo

![PHP Version](https://img.shields.io/badge/PHP-8.4+-blueviolet?style=flat-square)
![Java Version](https://img.shields.io/badge/Java-21+-orange?style=flat-square)
![PHP CI](https://github.com/alxarafe/hexagonal-template/actions/workflows/php.yml/badge.svg)
![Java CI](https://github.com/alxarafe/hexagonal-template/actions/workflows/java.yml/badge.svg)
![Static Analysis](https://img.shields.io/badge/static%20analysis-PHPStan%20%2B%20Deptrac-blue?style=flat-square)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](https://github.com/alxarafe/hexagonal-template/issues?utf8=✓&q=is%3Aopen%20is%3Aissue)

> 📚 Also available in english: [README.md](README.md)

Esta es una plantilla diseñada para aplicaciones basadas en arquitectura hexagonal estricta y modelado de dominio avanzado.

El repositorio proporciona una estructura reutilizable para implementar el mismo dominio en dos stacks independientes:

- PHP 8.x (Vanilla + Flight)
- Java 21 (Spring Boot adapters)

Este repositorio incluye una implementación de referencia basada en un dominio de ejemplo para demostrar:

- modelo conceptual compartido
- contratos API consistentes
- tests funcionales equivalentes
- reglas arquitectónicas comunes

---

## CAPAS DE ARQUITECTURA

Este proyecto distingue dos niveles de identidad:

### Capa conceptual (lo que ofrece el sistema)
- API PHP
- API Java
- Base de datos

### Capa de infraestructura (cómo está implementado)
- php-app
- java-app
- database

Ambas capas representan el mismo sistema desde distintos niveles de abstracción y no deben mezclarse en el diseño ni en la documentación.

---

## OBJETIVOS

- Arquitectura Hexagonal estricta
- DDD (Domain-Driven Design)
- TDD
- Contract Testing
- Enforcement arquitectónico automatizado
- Comparativa PHP vs Java sobre un mismo modelo de dominio

---

## ESTRUCTURA DEL REPOSITORIO

```text
/api-tests
/bin
/docker
/docs
/java
/php

---

## INICIO RÁPIDO

### Requisitos

- Docker & Docker Compose v2

### Iniciar el entorno

```bash
./bin/start.sh        # Construye e inicia todos los contenedores
```

### Ejecutar tests

```bash
# Dentro de los contenedores:
./bin/php_test.sh     # PHPUnit (unitarios + integración + contrato)
./bin/java_test.sh    # Maven (unitarios + arquitectura)

# Pipeline completo:
./bin/ci_local.sh     # Tests PHP + tests Java
```

### Comandos directos (sin Docker)

```bash
cd php && composer install && vendor/bin/phpunit
cd php && vendor/bin/phpcs && vendor/bin/phpstan analyse && vendor/bin/deptrac analyse
cd java && mvn test
```

### Aplicar migraciones de base de datos

```bash
./bin/migrate.sh
```

### Acceder a las APIs

| Stack  | URL                        |
|--------|----------------------------|
| PHP    | http://localhost:8081       |
| Java   | http://localhost:8082       |
| DB     | postgresql://localhost:5432 |

### Detener

```bash
./bin/docker_stop.sh
```

---