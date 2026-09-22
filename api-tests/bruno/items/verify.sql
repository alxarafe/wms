SET search_path TO wms_review_v2;

WITH expected (sku, name, family_code, uom_code, is_batch_managed, is_expirable) AS (
    VALUES
        ('BOLSA50', 'Bolsa de 50 kg', 'FOOD', 'BOX', TRUE, TRUE),
        ('CAJA24', 'Caja de 24 latas', 'FOOD', 'PAL', FALSE, FALSE)
), actual AS (
    SELECT i.sku, i.name, f.code, u.code, i.is_batch_managed, i.is_expirable
    FROM item i
    JOIN item_family f ON f.id = i.family_id
    JOIN uom u ON u.id = i.base_uom_id
)
SELECT
    (SELECT count(*) FROM (SELECT * FROM actual EXCEPT SELECT * FROM expected) AS unexpected)
    +
    (SELECT count(*) FROM (SELECT * FROM expected EXCEPT SELECT * FROM actual) AS missing);