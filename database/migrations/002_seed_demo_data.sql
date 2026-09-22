-- ─────────────────────────────────────────────────────────────
-- 002_seed_demo_data.sql
-- Datos de demostración para el cliente WMS (laboratorio).
--
-- Aplica el modelo discreto documentado en
-- docs/architecture/location-capacity-and-replenishment.md:
--   1 HU por hueco en cualquier rol, HU monoreferencia.
-- No introduce location.max_quantity.
--
-- La migración se re-ejecuta de forma idempotente (ON CONFLICT
-- DO NOTHING) porque bin/migrate.sh aplica todos los ficheros SQL.
-- ─────────────────────────────────────────────────────────────

BEGIN;

-- ── Almacén de demostración ─────────────────────────────────
INSERT INTO warehouse (id, code, name) VALUES
    ('01a0aca9-bc00-7010-8000-000000000001', 'WH1', 'Almacén de demostración')
ON CONFLICT (id) DO NOTHING;

-- ── Zonas ───────────────────────────────────────────────────
INSERT INTO zone (
    id, warehouse_id, zone_type_id, code, code_separator,
    warehouse_padding, zone_padding, aisle_padding, bay_padding, level_padding
) VALUES
    ('01a0aca9-bc00-7011-8000-000000000001', '01a0aca9-bc00-7010-8000-000000000001',
     '01a0aca9-bc00-7001-8000-000000000001', 'P', '-', 1, 1, 1, 2, 2),
    ('01a0aca9-bc00-7011-8000-000000000002', '01a0aca9-bc00-7010-8000-000000000001',
     '01a0aca9-bc00-7002-8000-000000000002', 'B', '-', 1, 1, 1, 2, 2)
ON CONFLICT (id) DO NOTHING;

-- ── Pasillos ────────────────────────────────────────────────
INSERT INTO aisle (id, zone_id, code, is_blocked) VALUES
    ('01a0aca9-bc00-7012-8000-000000000001', '01a0aca9-bc00-7011-8000-000000000001', 'A', FALSE),
    ('01a0aca9-bc00-7012-8000-000000000002', '01a0aca9-bc00-7011-8000-000000000002', 'B', FALSE)
ON CONFLICT (id) DO NOTHING;

INSERT INTO aisle_definition (id, zone_id, aisle_code, bays, levels, picking_max_level, enabled) VALUES
    ('01a0aca9-bc00-7013-8000-000000000001', '01a0aca9-bc00-7011-8000-000000000001', 'A', 4, 2, 2, TRUE),
    ('01a0aca9-bc00-7013-8000-000000000002', '01a0aca9-bc00-7011-8000-000000000002', 'B', 6, 3, 1, TRUE)
ON CONFLICT (id) DO NOTHING;

-- ── Huecos ──────────────────────────────────────────────────
-- Pasillo P-A (PICKING): 4 bahías x 2 niveles
INSERT INTO location (id, aisle_id, bay, level, code, role, status) VALUES
    ('01a0aca9-bc00-7020-8000-000000000001', '01a0aca9-bc00-7012-8000-000000000001', 1, 1, 'P-A-01-01', 'PICKING', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000002', '01a0aca9-bc00-7012-8000-000000000001', 1, 2, 'P-A-01-02', 'PICKING', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000003', '01a0aca9-bc00-7012-8000-000000000001', 2, 1, 'P-A-02-01', 'PICKING', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000004', '01a0aca9-bc00-7012-8000-000000000001', 2, 2, 'P-A-02-02', 'PICKING', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000005', '01a0aca9-bc00-7012-8000-000000000001', 3, 1, 'P-A-03-01', 'PICKING', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000006', '01a0aca9-bc00-7012-8000-000000000001', 3, 2, 'P-A-03-02', 'PICKING', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000007', '01a0aca9-bc00-7012-8000-000000000001', 4, 1, 'P-A-04-01', 'PICKING', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000008', '01a0aca9-bc00-7012-8000-000000000001', 4, 2, 'P-A-04-02', 'PICKING', 'ACTIVE')
ON CONFLICT (id) DO NOTHING;

-- Pasillo B-B (RESERVE): 6 bahías x 3 niveles
INSERT INTO location (id, aisle_id, bay, level, code, role, status) VALUES
    ('01a0aca9-bc00-7020-8000-000000000009', '01a0aca9-bc00-7012-8000-000000000002', 1, 1, 'B-B-01-01', 'RESERVE', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000010', '01a0aca9-bc00-7012-8000-000000000002', 1, 2, 'B-B-01-02', 'RESERVE', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000011', '01a0aca9-bc00-7012-8000-000000000002', 1, 3, 'B-B-01-03', 'RESERVE', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000012', '01a0aca9-bc00-7012-8000-000000000002', 2, 1, 'B-B-02-01', 'RESERVE', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000013', '01a0aca9-bc00-7012-8000-000000000002', 2, 2, 'B-B-02-02', 'RESERVE', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000014', '01a0aca9-bc00-7012-8000-000000000002', 2, 3, 'B-B-02-03', 'RESERVE', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000015', '01a0aca9-bc00-7012-8000-000000000002', 3, 1, 'B-B-03-01', 'RESERVE', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000016', '01a0aca9-bc00-7012-8000-000000000002', 3, 2, 'B-B-03-02', 'RESERVE', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000017', '01a0aca9-bc00-7012-8000-000000000002', 3, 3, 'B-B-03-03', 'RESERVE', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000018', '01a0aca9-bc00-7012-8000-000000000002', 4, 1, 'B-B-04-01', 'RESERVE', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000019', '01a0aca9-bc00-7012-8000-000000000002', 4, 2, 'B-B-04-02', 'RESERVE', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000020', '01a0aca9-bc00-7012-8000-000000000002', 4, 3, 'B-B-04-03', 'RESERVE', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000021', '01a0aca9-bc00-7012-8000-000000000002', 5, 1, 'B-B-05-01', 'RESERVE', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000022', '01a0aca9-bc00-7012-8000-000000000002', 5, 2, 'B-B-05-02', 'RESERVE', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000023', '01a0aca9-bc00-7012-8000-000000000002', 5, 3, 'B-B-05-03', 'RESERVE', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000024', '01a0aca9-bc00-7012-8000-000000000002', 6, 1, 'B-B-06-01', 'RESERVE', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000025', '01a0aca9-bc00-7012-8000-000000000002', 6, 2, 'B-B-06-02', 'RESERVE', 'ACTIVE'),
    ('01a0aca9-bc00-7020-8000-000000000026', '01a0aca9-bc00-7012-8000-000000000002', 6, 3, 'B-B-06-03', 'RESERVE', 'ACTIVE')
ON CONFLICT (id) DO NOTHING;

-- ── Familias y artículos ────────────────────────────────────
INSERT INTO item_family (id, code, name) VALUES
    ('01a0aca9-bc00-7031-8000-000000000001', 'FOOD', 'Alimentos'),
    ('01a0aca9-bc00-7031-8000-000000000002', 'CHEMS', 'Químicos')
ON CONFLICT (id) DO NOTHING;

-- uom EA/PAL existen en 001 ('01a0aca9-bc00-700c-...' y '...700e-...').
INSERT INTO item (id, sku, name, family_id, base_uom_id, is_batch_managed, is_expirable, base_cost) VALUES
    ('01a0aca9-bc00-7030-8000-000000000001', 'YOGUR FRESA', 'Yogur de fresa refrigerado',
     '01a0aca9-bc00-7031-8000-000000000001', '01a0aca9-bc00-700c-8000-00000000000c', TRUE, TRUE, 0),
    ('01a0aca9-bc00-7030-8000-000000000002', 'PALITOS CANGREJO', 'Palitos de cangrejo congelados',
     '01a0aca9-bc00-7031-8000-000000000001', '01a0aca9-bc00-700c-8000-00000000000c', TRUE, TRUE, 0),
    ('01a0aca9-bc00-7030-8000-000000000003', 'ARROZ LARGO', 'Arroz de grano largo',
     '01a0aca9-bc00-7031-8000-000000000001', '01a0aca9-bc00-700c-8000-00000000000c', FALSE, FALSE, 0),
    ('01a0aca9-bc00-7030-8000-000000000004', 'LEJIA BLANCA', 'Lejía blanca',
     '01a0aca9-bc00-7031-8000-000000000002', '01a0aca9-bc00-700e-8000-00000000000e', FALSE, FALSE, 0),
    ('01a0aca9-bc00-7030-8000-000000000005', 'AGUA MINERAL', 'Agua mineral sin gas',
     '01a0aca9-bc00-7031-8000-000000000001', '01a0aca9-bc00-700c-8000-00000000000c', FALSE, FALSE, 0)
ON CONFLICT (id) DO NOTHING;

-- ── Lotes ───────────────────────────────────────────────────
INSERT INTO batch (id, item_id, batch_code, expiration_date) VALUES
    ('01a0aca9-bc00-7032-8000-000000000001', '01a0aca9-bc00-7030-8000-000000000001',
     'L-YOG-001', '2026-12-31T00:00:00Z'),
    ('01a0aca9-bc00-7032-8000-000000000002', '01a0aca9-bc00-7030-8000-000000000002',
     'L-PAL-001', NULL)
ON CONFLICT (id) DO NOTHING;

-- ── Unidades de carga y existencias ─────────────────────────
INSERT INTO handling_unit (id, code, location_id, parent_hu_id, status) VALUES
    ('01a0aca9-bc00-7040-8000-000000000001', '340000000000000001',
     '01a0aca9-bc00-7020-8000-000000000001', NULL, 'AVAILABLE'),
    ('01a0aca9-bc00-7040-8000-000000000002', '340000000000000002',
     '01a0aca9-bc00-7020-8000-000000000002', NULL, 'AVAILABLE'),
    ('01a0aca9-bc00-7040-8000-000000000003', '340000000000000003',
     '01a0aca9-bc00-7020-8000-000000000009', NULL, 'AVAILABLE')
ON CONFLICT (id) DO NOTHING;

INSERT INTO stock_quant (id, hu_id, item_id, batch_id, quantity, unit) VALUES
    ('01a0aca9-bc00-7041-8000-000000000001', '01a0aca9-bc00-7040-8000-000000000001',
     '01a0aca9-bc00-7030-8000-000000000001', '01a0aca9-bc00-7032-8000-000000000001', 30, 'EA'),
    ('01a0aca9-bc00-7041-8000-000000000002', '01a0aca9-bc00-7040-8000-000000000002',
     '01a0aca9-bc00-7030-8000-000000000003', NULL, 40, 'EA'),
    ('01a0aca9-bc00-7041-8000-000000000003', '01a0aca9-bc00-7040-8000-000000000003',
     '01a0aca9-bc00-7030-8000-000000000004', NULL, 12, 'PAL')
ON CONFLICT (id) DO NOTHING;

COMMIT;