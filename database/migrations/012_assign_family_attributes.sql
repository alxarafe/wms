-- ─────────────────────────────────────────────────────────────
-- 012_assign_family_attributes.sql
-- Asigna atributos a familias: IS_FOOD a FOOD, IS_CHEMICAL a CHEMS
-- ─────────────────────────────────────────────────────────────

BEGIN;

-- FOOD family tiene atributo IS_FOOD
INSERT INTO item_family_attribute (item_family_id, attribute_id)
SELECT f.id, a.id
FROM item_family f, attribute a
WHERE f.code = 'FOOD' AND a.code = 'IS_FOOD'
ON CONFLICT DO NOTHING;

-- CHEMS family tiene atributo IS_CHEMICAL
INSERT INTO item_family_attribute (item_family_id, attribute_id)
SELECT f.id, a.id
FROM item_family f, attribute a
WHERE f.code = 'CHEMS' AND a.code = 'IS_CHEMICAL'
ON CONFLICT DO NOTHING;

COMMIT;