SET search_path TO wms_review_v2;

WITH expected (code, name, attributes) AS (
    VALUES
        ('CHILLED_FOOD', 'Alimentos refrigerados', 'CHILLED,FOOD'),
        ('FROZEN_FOOD', 'Alimentos congelados', 'FOOD,FROZEN'),
        ('DRY_FOOD', 'Alimentos secos', 'FOOD'),
        ('CHEMICAL', 'Productos químicos', 'CHEMICAL'),
        ('NEUTRAL', 'Productos neutros', '')
), actual AS (
    SELECT f.code, f.name,
           COALESCE(string_agg(sa.code, ',' ORDER BY sa.code), '') AS attributes
    FROM item_family f
    LEFT JOIN family_storage_attribute fsa ON fsa.family_id = f.id
    LEFT JOIN storage_attribute sa ON sa.id = fsa.attribute_id
    GROUP BY f.id, f.code, f.name
)
SELECT
    (SELECT count(*) FROM (SELECT * FROM actual EXCEPT SELECT * FROM expected) AS unexpected)
    +
    (SELECT count(*) FROM (SELECT * FROM expected EXCEPT SELECT * FROM actual) AS missing);