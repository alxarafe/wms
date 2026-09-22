# Monorepo de Sistema de Gestión de Almacenes (WMS)

![PHP Version](https://img.shields.io/badge/PHP-8.4+-blueviolet?style=flat-square)
![Java Version](https://img.shields.io/badge/Java-21+-orange?style=flat-square)
![PHP CI](https://github.com/alxarafe/wms/actions/workflows/php.yml/badge.svg)
![Java CI](https://github.com/alxarafe/wms/actions/workflows/java.yml/badge.svg)
![Static Analysis](https://img.shields.io/badge/static%20analysis-PHPStan%20%2B%20Deptrac-blue?style=flat-square)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](https://github.com/alxarafe/wms/issues?utf8=✓&q=is%3Aopen%20is%3Aissue)

> 📚 Also available in english: [README.md](README.md)

Sistema de gestión de almacén (WMS) diseñado como ejercicio de arquitectura hexagonal estricta y modelado de dominio avanzado.

El repositorio implementa en el mismo dominio dos stacks independientes:

- PHP 8.4 (Vanilla)
- Java 21 (Spring Boot adapters)

El dominio WMS está en desarrollo en ambos stacks. Las APIs exponen `GET /api/health`, `POST /api/item-families`, `GET /api/warehouses/{id}/state`, `POST /api/receipts` y `POST /api/issues`. La creación de familias, el visor de estado y las operaciones de stock comparten un único contrato por endpoint entre PHP y Java, verificado con Bruno contra bases de prueba independientes.

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
/client
/docker
/docs
/java
/php
```

---

## INICIO RÁPIDO

### Requisitos

- Docker & Docker Compose v2

### Iniciar el entorno

```bash
./bin/start.sh        # Construye e inicia todos los contenedores
```

### Configuración inicial solo PHP

La primera entrega de configuración crea almacenes, tipos de HU, tipos de hueco y
políticas por API. Ejecuta `./bin/php_configuration_test.sh` desde el anfitrión para
recrear dos bases exclusivas de prueba PHP y pasar migraciones, PHPUnit y Bruno.
La línea v2 se aplica con `php bin/migrate.php` desde `php/`; excluye las semillas
y el esquema operativo antiguo. Java queda sin sincronizar en este bloque.
Contrato, requisitos y limitación de la fixture de calle para probar el bloqueo
del formato: [configuración PHP](docs/architecture/php-configuration-api.md).

### Ejecutar tests

```bash
# Dentro de los contenedores:
./bin/php_test.sh     # PHPUnit (unitarios + integración + contrato)
./bin/java_test.sh    # Maven (unitarios + arquitectura)
./bin/bruno_uoms_test.sh     # API PHP + Bruno del catálogo de unidades (base aislada v2 limpia)
./bin/bruno_items_test.sh    # API PHP + Bruno del catálogo de artículos (base aislada v2 limpia)
./bin/bruno_families_test.sh # API PHP + Bruno del catálogo de familias (base aislada v2 limpia)
./bin/bruno_operations_test.sh  # Escenario de paridad de entradas/salidas en bases aisladas

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

Las migraciones `001`–`003` instalan el esquema `public` actual (operativo para
los adaptadores PHP/Java de estado y operaciones). Las migraciones `004` y `005`
instalan en **paralelo** el esquema `wms_review_v2`, fuente de verdad del dominio
WMS revisado (estructura y datos de demostración), sin tocar `public`. El catálogo
(`uom`, `item_family`, `item`) ya opera sobre `wms_review_v2` en PHP calificando sus
tablas al esquema (configurable con `WMS_REVIEW_V2_SCHEMA`); estado y operaciones
siguen leyendo `public` y se migran en fases posteriores. Solo queda aparcada la
adaptación del catálogo Java. Ver
[docs/architecture/wms-review-v2-schema.md](docs/architecture/wms-review-v2-schema.md).

### Acceder a las APIs

| Stack  | URL                        |
|--------|----------------------------|
| PHP    | http://localhost:28080/api/health |
| Java   | http://localhost:38080/api/health |
| DB     | postgresql://localhost:5432 |

El contrato de `POST /api/item-families` se describe en [docs/architecture/item-family-api.md](docs/architecture/item-family-api.md). El visor consume `GET /api/warehouses/{id}/state`, que devuelve la topología y el stock ubicado de un almacén con el mismo contrato en ambos stacks; la migración `002_seed_demo_data.sql` siembra el almacén de demostración con id `01a0aca9-bc00-7010-8000-000000000001`. Las entradas y salidas (`POST /api/receipts` y `POST /api/issues`) siguen el modelo discreto (1 HU por hueco, HU monoreferencia y consumo completo en la salida) descrito en [docs/architecture/stock-operations-api.md](docs/architecture/stock-operations-api.md); el mismo escenario se ejecuta contra ambas APIs en `api-tests/bruno/operations/`. Las APIs de prueba Bruno se consultan en `http://localhost:28081` (PHP) y `http://localhost:28082` (Java) tras ejecutar el script.

### Cliente de demostración

Un cliente de demostración vive en `client/` (Svelte + Vite): pinta una calle con su mercancía, realiza entradas y salidas y alterna entre modos simulado, PHP y Java. Solo consume las APIs HTTP; no accede a la base de datos.

```bash
cd client && npm install && npm run dev   # http://localhost:5173
```

### Detener

```bash
./bin/docker_stop.sh
```

---
