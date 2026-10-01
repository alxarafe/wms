-- ─────────────────────────────────────────────────────────────
-- 011_add_compatibility_rules.sql
-- Añade reglas de compatibilidad (FORBIDS entre FOOD y CHEMICAL)
-- ─────────────────────────────────────────────────────────────

BEGIN;

-- FOOD y CHEMICAL no pueden estar en el mismo pasillo
INSERT INTO compatibility_rule (id, rule_type, source_attribute_id, target_attribute_id, scope)
SELECT 
    gen_random_uuid(),
    'FORBIDS',
    sa1.id,
    sa2.id,
    'AISLE'
FROM attribute sa1, attribute sa2
WHERE sa1.code = 'IS_FOOD' AND sa2.code = 'IS_CHEMICAL'
ON CONFLICT DO NOTHING;

-- También al revés para que sea bidireccional
INSERT INTO compatibility_rule (id, rule_type, source_attribute_id, target_attribute_id, scope)
SELECT 
    gen_random_uuid(),
    'FORBIDS',
    sa1.id,
    sa2.id,
    'AISLE'
FROM attribute sa1, attribute sa2
WHERE sa1.code = 'IS_CHEMICAL' AND sa2.code = 'IS_FOOD'
ON CONFLICT DO NOTHING;

COMMIT;