WITH expected (code, name, attributes) AS (
    VALUES
        ('REFRIGERATED_FOOD', 'Alimentos refrigerados', 'IS_FOOD,IS_REFRIGERATED'),
        ('FROZEN_FOOD', 'Alimentos congelados', 'IS_FOOD,IS_FROZEN'),
        ('DRY_FOOD', 'Alimentos secos', 'IS_FOOD'),
        ('CHEMICAL', 'Productos químicos', 'IS_CHEMICAL'),
        ('NEUTRAL', 'Productos neutros', '')
), actual AS (
    SELECT f.code, f.name,
           COALESCE(string_agg(a.code, ',' ORDER BY a.code), '') AS attributes
    FROM item_family f
    LEFT JOIN item_family_attribute fa ON fa.item_family_id = f.id
    LEFT JOIN attribute a ON a.id = fa.attribute_id
    GROUP BY f.id, f.code, f.name
)
SELECT
    (SELECT count(*) FROM (SELECT * FROM actual EXCEPT SELECT * FROM expected) AS unexpected)
    +
    (SELECT count(*) FROM (SELECT * FROM expected EXCEPT SELECT * FROM actual) AS missing);
