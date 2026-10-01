SET search_path TO wms_review_v2;

WITH expected (sku, name, family_code, uom_code, is_batch_managed, is_expirable) AS (
    VALUES
        ('BOLSA50', 'Bolsa de 50 kg', 'CHILLED_FOOD', 'BOX', TRUE, TRUE),
        ('CAJA24', 'Caja de 24 latas', 'CHILLED_FOOD', 'PAL', FALSE, FALSE)
), actual AS (
    SELECT i.sku, i.name, f.code, u.code, i.is_batch_managed, i.is_expirable
    FROM item i
    JOIN item_family f ON f.id = i.family_id
    JOIN uom u ON u.id = i.base_uom_id
), family_attributes AS (
    SELECT f.code, COALESCE(string_agg(sa.code, ',' ORDER BY sa.code), '') AS attributes
    FROM item_family f
    LEFT JOIN family_storage_attribute fsa ON fsa.family_id = f.id
    LEFT JOIN storage_attribute sa ON sa.id = fsa.attribute_id
    GROUP BY f.code
), expected_family_attributes AS (
    SELECT 'CHILLED_FOOD' AS code, 'CHILLED,FOOD' AS attributes
)
SELECT
    (SELECT count(*) FROM (SELECT * FROM actual EXCEPT SELECT * FROM expected) AS unexpected)
    + (SELECT count(*) FROM (SELECT * FROM expected EXCEPT SELECT * FROM actual) AS missing)
    + (SELECT count(*) FROM (SELECT * FROM family_attributes EXCEPT SELECT * FROM expected_family_attributes) AS unexpected_family)
    + (SELECT count(*) FROM (SELECT * FROM expected_family_attributes EXCEPT SELECT * FROM family_attributes) AS missing_family);
