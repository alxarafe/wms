-- Estado esperado de las semillas 002 más las tres entradas y la salida completa.
-- Comprobación independiente de Java. Devuelve 0 si no hay diferencias.
-- Normaliza SSCC aleatorios y expresa las caducidades como instantes UTC.
WITH actual(line) AS (
SELECT 'location:' || l.code || ' hu:' || (hu.code IS NOT NULL)::text || ' item:' || COALESCE(i.sku, 'NULL') || ' qty:' || COALESCE(sq.quantity::text, 'NULL') || ' unit:' || COALESCE(sq.unit, 'NULL') AS line
FROM location l
LEFT JOIN handling_unit hu ON hu.location_id = l.id AND hu.parent_hu_id IS NULL
LEFT JOIN stock_quant sq ON sq.hu_id = hu.id
LEFT JOIN item i ON i.id = sq.item_id
WHERE l.id IN (
    '01a0aca9-bc00-7020-8000-000000000001',
    '01a0aca9-bc00-7020-8000-000000000002',
    '01a0aca9-bc00-7020-8000-000000000003',
    '01a0aca9-bc00-7020-8000-000000000009',
    '01a0aca9-bc00-7020-8000-000000000007',
    '01a0aca9-bc00-7020-8000-000000000008'
)

UNION ALL

SELECT 'batch:' || b.batch_code || ' exp:' || COALESCE((b.expiration_date AT TIME ZONE 'UTC')::text, 'NULL') AS line
FROM batch b
WHERE b.batch_code IN ('L-YOG-001', 'L-PAL-001')

UNION ALL

SELECT 'movement:' || type || ' count:' || COUNT(*)::text AS line
FROM stock_movement
GROUP BY type

UNION ALL

SELECT 'detached_hu_total: ' || COUNT(*)::text AS line
FROM handling_unit
WHERE location_id IS NULL

), expected(line) AS (
    VALUES
        ('batch:L-PAL-001 exp:2027-03-15 00:00:00'),
        ('batch:L-YOG-001 exp:2026-12-31 00:00:00'),
        ('location:B-B-01-01 hu:true item:LEJIA BLANCA qty:12.000000 unit:PAL'),
        ('location:P-A-01-01 hu:true item:YOGUR FRESA qty:30.000000 unit:EA'),
        ('location:P-A-01-02 hu:true item:ARROZ LARGO qty:40.000000 unit:EA'),
        ('location:P-A-02-01 hu:true item:PALITOS CANGREJO qty:20.000000 unit:EA'),
        ('location:P-A-04-01 hu:true item:ARROZ LARGO qty:40.000000 unit:EA'),
        ('location:P-A-04-02 hu:false item:NULL qty:NULL unit:NULL'),
        ('movement:INBOUND count:3'),
        ('movement:OUTBOUND count:1'),
        ('detached_hu_total: 1')
), differences AS (
    (SELECT line FROM actual EXCEPT ALL SELECT line FROM expected)
    UNION ALL
    (SELECT line FROM expected EXCEPT ALL SELECT line FROM actual)
)
SELECT COUNT(*) FROM differences;
