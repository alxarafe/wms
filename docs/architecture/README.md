# Documentación de Arquitectura

Contiene documentación técnica relacionada con la arquitectura del sistema.

Temas habituales:
- Arquitectura hexagonal
- Límites de dependencias
- Responsabilidades de las capas
- Puertos y adaptadores
- Decisiones de arquitectura
- Reglas de cumplimiento

Objetivo:
Proporcionar una referencia arquitectónica compartida para todas las implementaciones.

## Configuración inicial PHP

[Contrato, migraciones, límites y pruebas](php-configuration-api.md) de la primera
entrega: almacenes, tipos HU, tipos de hueco y políticas. Sin sincronización Java.

## Pruebas HTTP compartidas

[Decisión Bruno](bruno-tests.md): un escenario por contrato, ejecución actual solo
PHP y activación posterior de Java sin duplicar las peticiones.
