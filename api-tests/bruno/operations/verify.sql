-- Comparación de estado persistido tras el escenario de operaciones.
-- Devuelve una huella canónica con el stock ubicado y el ledger por tipo;
-- PHP y Java deben producir la misma salida exacta (misma semántica M5/M6).

-- el SSCC se normaliza: la huella compara ocupación (hu present) y no el valor
-- aleatorio generado en cada entrada.
SELECT 'location:' || l.code || ' hu:' || (hu.code IS NOT NULL)::text || ' item:' || i.sku || ' qty:' || sq.quantity::text || ' unit:' || sq.unit AS line
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

SELECT 'batch:' || b.batch_code || ' exp:' || COALESCE(b.expiration_date::text, 'NULL') AS line
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

ORDER BY line;

-- La salida completa (07) desvincula la HU creada en la entrada (04): ante un
-- comportamiento equivalente, ambas bases deben devolver exactamente las mismas
-- líneas, en el mismo orden, con el mismo número (formato canónico NUMERIC).