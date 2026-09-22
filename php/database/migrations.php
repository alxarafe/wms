<?php

declare(strict_types=1);

// Línea PHP v2: reutiliza la estructura revisada y aplica sus incrementos.
// 001/003 pertenecen a public legado (001 además inserta maestros).
// 002/005 son semillas: ninguna forma parte del bootstrap PHP desde cero.
return [
    '004_review_v2_schema' => __DIR__ . '/../../database/migrations/004_create_wms_review_v2_schema.sql',
    '006_php_configuration_guards' => __DIR__ . '/migrations/006_configuration_guards.sql',
];
