-- Única fixture técnica: ejecutada DESPUÉS de las altas exclusivamente HTTP.
-- Se eliminará al poder crear la calle mediante la API del siguiente bloque.
INSERT INTO wms_review_v2.aisle (id, warehouse_id, number)
SELECT '01900000-0000-7000-8000-000000000001', id, 1
FROM wms_review_v2.warehouse WHERE code = 'A';
