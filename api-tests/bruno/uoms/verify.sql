SET search_path TO wms_review_v2;

WITH expected (code, description) AS (
    VALUES
        ('BAG', 'Saco'),
        ('BOX', 'Standard Box'),
        ('EA', 'Unit / Each'),
        ('KG', 'Kilogram'),
        ('LTR', 'Litro'),
        ('PAL', 'Standard Pallet'),
        ('PZA', 'Unidad suelta')
), actual AS (
    SELECT code, description FROM uom
)
SELECT
    (SELECT count(*) FROM (SELECT * FROM actual EXCEPT SELECT * FROM expected) AS unexpected)
    +
    (SELECT count(*) FROM (SELECT * FROM expected EXCEPT SELECT * FROM actual) AS missing);