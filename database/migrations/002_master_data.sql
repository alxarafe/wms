-- Master data needed by 002_seed_demo_data.sql
-- Extracted from database/mvp.sql

BEGIN;

-- zone_type master data
INSERT INTO zone_type (id, code, description, is_operative, allows_multi_sku) VALUES
    ('01a0aca9-bc00-7001-8000-000000000001', 'PICKING', 'Zona de extracción y preparación manual de pedidos', TRUE, FALSE),
    ('01a0aca9-bc00-7002-8000-000000000002', 'BULK', 'Zona de almacenaje masivo y pulmón de reposición', TRUE, TRUE),
    ('01a0aca9-bc00-7003-8000-000000000003', 'RECEPTION', 'Muelle de entrada y zona de verificación de mercancía', FALSE, TRUE),
    ('01a0aca9-bc00-7004-8000-000000000004', 'SHIPPING', 'Muelle de salida, consolidación y expedición', FALSE, TRUE),
    ('01a0aca9-bc00-7005-8000-000000000005', 'QUARANTINE', 'Zona de aislamiento para control de calidad o bloqueos', FALSE, TRUE)
ON CONFLICT (code) DO NOTHING;

-- attribute master data
INSERT INTO attribute (id, code, target_type) VALUES
    ('01a0aca9-bc00-7006-8000-000000000006', 'COLD', 'LOCATION'),
    ('01a0aca9-bc00-7007-8000-000000000007', 'FOOD_SAFE', 'LOCATION'),
    ('01a0aca9-bc00-7008-8000-000000000008', 'CHEMICAL_SAFE', 'LOCATION'),
    ('01a0aca9-bc00-7010-8000-000000000010', 'IS_CHILLED', 'FAMILY'),
    ('01a0aca9-bc00-7009-8000-000000000009', 'IS_FOOD', 'FAMILY'),
    ('01a0aca9-bc00-700a-8000-00000000000a', 'IS_CHEMICAL', 'FAMILY'),
    ('01a0aca9-bc00-700b-8000-00000000000b', 'IS_FROZEN', 'FAMILY')
ON CONFLICT (code) DO NOTHING;

-- uom master data
INSERT INTO uom (id, code, description) VALUES
    ('01a0aca9-bc00-700c-8000-00000000000c', 'EA', 'Unit / Each'),
    ('01a0aca9-bc00-700d-8000-00000000000d', 'BOX', 'Standard Box'),
    ('01a0aca9-bc00-700e-8000-00000000000e', 'PAL', 'Standard Pallet'),
    ('01a0aca9-bc00-700f-8000-00000000000f', 'KG', 'Kilogram')
ON CONFLICT (code) DO NOTHING;

COMMIT;