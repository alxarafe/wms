-- ─────────────────────────────────────────────────────────────
-- 005_seed_wms_review_v2_demo_data.sql
-- Datos de demostración para el esquema `wms_review_v2`.
--
-- Fuente: volcado `private/wms_lab_export.sql` (sección "Data for
-- Name"; Schema: wms_review_v2), convertida de `COPY ... FROM stdin`
-- a `INSERT ... ON CONFLICT DO NOTHING` (idempotente), respetando el
-- orden de dependencias de claves foráneas.
--
-- Convenciones del volcado mantenidas:
--   - \N -> NULL; t/f -> TRUE/FALSE.
--   - Timestamps y fechas conservados como literales.
--   - Tablas sin filas en el volcado (handling_unit_content_correction,
--     location_block, location_storage_attribute) no reciben INSERT.
-- ─────────────────────────────────────────────────────────────

BEGIN;

-- ―― Catálogo base: almacenes, medidas y familias ――――――

INSERT INTO wms_review_v2.warehouse
    (id, code, name, uses_zones, separator, include_zone_in_code, aisle_digits, bay_digits, level_digits) VALUES
    ('01a0aca9-bc00-8010-8000-000000000001', 'A', 'Demostración sin zonas', FALSE, '.', FALSE, 1, 2, 1),
    ('01a0aca9-bc00-8010-8000-000000000002', 'B', 'Demostración con zona', TRUE, '.', FALSE, 1, 2, 1)
ON CONFLICT (id) DO NOTHING;

INSERT INTO wms_review_v2.uom (id, code, description) VALUES
    ('01a0aca9-bc00-8060-8000-000000000001', 'EA', 'Unidad'),
    ('01a0aca9-bc00-8060-8000-000000000002', 'BOX', 'Caja, unidad de medida'),
    ('01a0aca9-bc00-8060-8000-000000000003', 'PAL', 'Palé, unidad de medida')
ON CONFLICT (id) DO NOTHING;

INSERT INTO wms_review_v2.item_family (id, parent_family_id, code, name) VALUES
    ('01a0aca9-bc00-8030-8000-000000000001', NULL, 'ALIMENTOS', 'Alimentos'),
    ('01a0aca9-bc00-8030-8000-000000000002', '01a0aca9-bc00-8030-8000-000000000001', 'YOGURES', 'Yogures'),
    ('01a0aca9-bc00-8030-8000-000000000003', '01a0aca9-bc00-8030-8000-000000000002', 'YOGURES_REFRIGERADOS', 'Yogures refrigerados'),
    ('01a0aca9-bc00-8030-8000-000000000004', '01a0aca9-bc00-8030-8000-000000000001', 'UHT', 'Productos UHT')
ON CONFLICT (id) DO NOTHING;

INSERT INTO wms_review_v2.storage_attribute (id, code, name, exclusive_group_code) VALUES
    ('01a0aca9-bc00-8040-8000-000000000001', 'CHILLED', 'Refrigerado', 'THERMAL'),
    ('01a0aca9-bc00-8040-8000-000000000002', 'FROZEN', 'Congelado', 'THERMAL'),
    ('01a0aca9-bc00-8040-8000-000000000003', 'FOOD', 'Alimento', NULL),
    ('01a0aca9-bc00-8040-8000-000000000004', 'CHEMICAL', 'Químico', NULL)
ON CONFLICT (id) DO NOTHING;

-- ―― Tipos de unidad de manejo y de hueco ―――――――――――

INSERT INTO wms_review_v2.handling_unit_type (id, code, name, is_active) VALUES
    ('01a0aca9-bc00-8100-8000-000000000001', 'PALLET', 'Palé', TRUE),
    ('01a0aca9-bc00-8100-8000-000000000002', 'BOX', 'Caja', TRUE)
ON CONFLICT (id) DO NOTHING;

INSERT INTO wms_review_v2.location_type
    (id, warehouse_id, code, name, max_locations_per_item, allows_multi_sku, allows_multi_batch) VALUES
    ('01a0aca9-bc00-8110-8000-000000000001', '01a0aca9-bc00-8010-8000-000000000001', 'PICKING', 'Preparación A', 1, TRUE, TRUE),
    ('01a0aca9-bc00-8110-8000-000000000002', '01a0aca9-bc00-8010-8000-000000000001', 'RESERVE', 'Reserva A', NULL, FALSE, FALSE),
    ('01a0aca9-bc00-8110-8000-000000000003', '01a0aca9-bc00-8010-8000-000000000001', 'RETURNS', 'Devoluciones A', NULL, TRUE, TRUE),
    ('01a0aca9-bc00-8110-8000-000000000004', '01a0aca9-bc00-8010-8000-000000000002', 'PICKING', 'Preparación B', 2, TRUE, TRUE),
    ('01a0aca9-bc00-8110-8000-000000000005', '01a0aca9-bc00-8010-8000-000000000002', 'RESERVE', 'Reserva B', NULL, FALSE, FALSE)
ON CONFLICT (id) DO NOTHING;

INSERT INTO wms_review_v2.location_type_hu_policy
    (location_type_id, handling_unit_type_id, accepts_full, accepts_partial, allows_breakdown, allows_full_dispatch) VALUES
    ('01a0aca9-bc00-8110-8000-000000000001', '01a0aca9-bc00-8100-8000-000000000001', TRUE, TRUE, TRUE, TRUE),
    ('01a0aca9-bc00-8110-8000-000000000001', '01a0aca9-bc00-8100-8000-000000000002', TRUE, FALSE, FALSE, TRUE),
    ('01a0aca9-bc00-8110-8000-000000000002', '01a0aca9-bc00-8100-8000-000000000001', TRUE, FALSE, FALSE, TRUE),
    ('01a0aca9-bc00-8110-8000-000000000003', '01a0aca9-bc00-8100-8000-000000000002', TRUE, TRUE, FALSE, FALSE),
    ('01a0aca9-bc00-8110-8000-000000000004', '01a0aca9-bc00-8100-8000-000000000001', TRUE, TRUE, TRUE, TRUE),
    ('01a0aca9-bc00-8110-8000-000000000005', '01a0aca9-bc00-8100-8000-000000000001', TRUE, FALSE, FALSE, TRUE)
ON CONFLICT (location_type_id, handling_unit_type_id) DO NOTHING;

-- ―― Zonas, calles, plantillas y huecos ――――――――――――

INSERT INTO wms_review_v2.zone (id, warehouse_id, code, name) VALUES
    ('01a0aca9-bc00-8011-8000-000000000001', '01a0aca9-bc00-8010-8000-000000000002', 'FRIO', 'Sector refrigerado')
ON CONFLICT (id) DO NOTHING;

INSERT INTO wms_review_v2.aisle (id, warehouse_id, number) VALUES
    ('01a0aca9-bc00-8012-8000-000000000001', '01a0aca9-bc00-8010-8000-000000000001', 1),
    ('01a0aca9-bc00-8012-8000-000000000002', '01a0aca9-bc00-8010-8000-000000000002', 1)
ON CONFLICT (id) DO NOTHING;

INSERT INTO wms_review_v2.aisle_definition (aisle_id, bay_count, level_count) VALUES
    ('01a0aca9-bc00-8012-8000-000000000001', 60, 6),
    ('01a0aca9-bc00-8012-8000-000000000002', 60, 6)
ON CONFLICT (aisle_id) DO NOTHING;

INSERT INTO wms_review_v2.aisle_level_template (aisle_id, warehouse_id, level, location_type_id) VALUES
    ('01a0aca9-bc00-8012-8000-000000000001', '01a0aca9-bc00-8010-8000-000000000001', 1, '01a0aca9-bc00-8110-8000-000000000001'),
    ('01a0aca9-bc00-8012-8000-000000000001', '01a0aca9-bc00-8010-8000-000000000001', 2, '01a0aca9-bc00-8110-8000-000000000002'),
    ('01a0aca9-bc00-8012-8000-000000000001', '01a0aca9-bc00-8010-8000-000000000001', 3, '01a0aca9-bc00-8110-8000-000000000002'),
    ('01a0aca9-bc00-8012-8000-000000000001', '01a0aca9-bc00-8010-8000-000000000001', 4, '01a0aca9-bc00-8110-8000-000000000002'),
    ('01a0aca9-bc00-8012-8000-000000000001', '01a0aca9-bc00-8010-8000-000000000001', 5, '01a0aca9-bc00-8110-8000-000000000002'),
    ('01a0aca9-bc00-8012-8000-000000000001', '01a0aca9-bc00-8010-8000-000000000001', 6, '01a0aca9-bc00-8110-8000-000000000003'),
    ('01a0aca9-bc00-8012-8000-000000000002', '01a0aca9-bc00-8010-8000-000000000002', 1, '01a0aca9-bc00-8110-8000-000000000004'),
    ('01a0aca9-bc00-8012-8000-000000000002', '01a0aca9-bc00-8010-8000-000000000002', 2, '01a0aca9-bc00-8110-8000-000000000005')
ON CONFLICT (aisle_id, level) DO NOTHING;

INSERT INTO wms_review_v2.aisle_allowed_family (aisle_id, item_family_id) VALUES
    ('01a0aca9-bc00-8012-8000-000000000001', '01a0aca9-bc00-8030-8000-000000000001')
ON CONFLICT (aisle_id, item_family_id) DO NOTHING;

INSERT INTO wms_review_v2.aisle_void (id, aisle_id, bay_from, bay_to, level_from, level_to, reason) VALUES
    ('01a0aca9-bc00-8013-8000-000000000001', '01a0aca9-bc00-8012-8000-000000000001', 30, 34, 1, 4, 'Paso transversal')
ON CONFLICT (id) DO NOTHING;

INSERT INTO wms_review_v2.location
    (id, warehouse_id, aisle_id, zone_id, location_type_id, bay, level, code, is_enabled) VALUES
    ('01a0aca9-bc00-8020-8000-000000000001', '01a0aca9-bc00-8010-8000-000000000001', '01a0aca9-bc00-8012-8000-000000000001', NULL, '01a0aca9-bc00-8110-8000-000000000001', 1, 1, 'A.1.01.1', TRUE),
    ('01a0aca9-bc00-8020-8000-000000000002', '01a0aca9-bc00-8010-8000-000000000001', '01a0aca9-bc00-8012-8000-000000000001', NULL, '01a0aca9-bc00-8110-8000-000000000002', 1, 2, 'A.1.01.2', TRUE),
    ('01a0aca9-bc00-8020-8000-000000000003', '01a0aca9-bc00-8010-8000-000000000001', '01a0aca9-bc00-8012-8000-000000000001', NULL, '01a0aca9-bc00-8110-8000-000000000002', 2, 2, 'A.1.02.2', TRUE),
    ('01a0aca9-bc00-8020-8000-000000000004', '01a0aca9-bc00-8010-8000-000000000002', '01a0aca9-bc00-8012-8000-000000000002', '01a0aca9-bc00-8011-8000-000000000001', '01a0aca9-bc00-8110-8000-000000000004', 1, 1, 'B.1.01.1', TRUE),
    ('01a0aca9-bc00-8020-8000-000000000005', '01a0aca9-bc00-8010-8000-000000000001', '01a0aca9-bc00-8012-8000-000000000001', NULL, '01a0aca9-bc00-8110-8000-000000000002', 30, 5, 'A.1.30.5', TRUE)
ON CONFLICT (id) DO NOTHING;

INSERT INTO wms_review_v2.zone_storage_attribute (zone_id, attribute_id) VALUES
    ('01a0aca9-bc00-8011-8000-000000000001', '01a0aca9-bc00-8040-8000-000000000001')
ON CONFLICT (zone_id, attribute_id) DO NOTHING;

INSERT INTO wms_review_v2.family_storage_attribute (family_id, attribute_id) VALUES
    ('01a0aca9-bc00-8030-8000-000000000001', '01a0aca9-bc00-8040-8000-000000000003'),
    ('01a0aca9-bc00-8030-8000-000000000003', '01a0aca9-bc00-8040-8000-000000000001')
ON CONFLICT (family_id, attribute_id) DO NOTHING;

-- ―― Artículos y reglas de almacenamiento ―――――――――――

INSERT INTO wms_review_v2.item (id, sku, name, family_id, base_uom_id, is_batch_managed, is_expirable) VALUES
    ('01a0aca9-bc00-8070-8000-000000000001', 'DEMO-UHT', 'Lácteo UHT ficticio', '01a0aca9-bc00-8030-8000-000000000004', '01a0aca9-bc00-8060-8000-000000000001', TRUE, TRUE),
    ('01a0aca9-bc00-8070-8000-000000000002', 'DEMO-YOG', 'Yogur refrigerado ficticio', '01a0aca9-bc00-8030-8000-000000000003', '01a0aca9-bc00-8060-8000-000000000001', TRUE, TRUE)
ON CONFLICT (id) DO NOTHING;

INSERT INTO wms_review_v2.storage_rule (id, rule_type, attribute_a_id, attribute_b_id, scope) VALUES
    ('01a0aca9-bc00-8050-8000-000000000001', 'REQUIRE_LOCATION', '01a0aca9-bc00-8040-8000-000000000001', '01a0aca9-bc00-8040-8000-000000000001', NULL),
    ('01a0aca9-bc00-8050-8000-000000000002', 'REQUIRE_LOCATION', '01a0aca9-bc00-8040-8000-000000000002', '01a0aca9-bc00-8040-8000-000000000002', NULL),
    ('01a0aca9-bc00-8050-8000-000000000003', 'SEPARATE', '01a0aca9-bc00-8040-8000-000000000003', '01a0aca9-bc00-8040-8000-000000000004', 'AISLE')
ON CONFLICT (id) DO NOTHING;

-- ―― Recepción de demostración ――――――――――――――――――

INSERT INTO wms_review_v2.receipt (id, supplier_name, supplier_document, received_at) VALUES
    ('01a0aca9-bc00-8080-8000-000000000001', 'Proveedor ficticio', 'ALB-DEMO-1', '2026-09-22 14:19:08.993224+00')
ON CONFLICT (id) DO NOTHING;

INSERT INTO wms_review_v2.receipt_line (id, receipt_id, item_id, declared_units) VALUES
    ('01a0aca9-bc00-8081-8000-000000000001', '01a0aca9-bc00-8080-8000-000000000001', '01a0aca9-bc00-8070-8000-000000000001', 288)
ON CONFLICT (id) DO NOTHING;

-- ―― Unidades de manejo, contenido y retención ―――――――

INSERT INTO wms_review_v2.handling_unit
    (id, code, sscc, handling_unit_type_id, warehouse_id, location_id, receipt_line_id, source_pallet_id, source_ordinal, fill_status, lifecycle_status) VALUES
    ('01a0aca9-bc00-8090-8000-000000000001', 'HU-DEMO-001', NULL, '01a0aca9-bc00-8100-8000-000000000001', '01a0aca9-bc00-8010-8000-000000000001', '01a0aca9-bc00-8020-8000-000000000002', '01a0aca9-bc00-8081-8000-000000000001', NULL, NULL, 'FULL', 'ACTIVE'),
    ('01a0aca9-bc00-8090-8000-000000000002', 'HU-DEMO-002', NULL, '01a0aca9-bc00-8100-8000-000000000001', '01a0aca9-bc00-8010-8000-000000000001', '01a0aca9-bc00-8020-8000-000000000003', '01a0aca9-bc00-8081-8000-000000000001', NULL, NULL, 'FULL', 'ACTIVE')
ON CONFLICT (id) DO NOTHING;

INSERT INTO wms_review_v2.handling_unit_content
    (handling_unit_id, item_id, batch_code, expires_on, initial_box_count, box_count, units_per_box) VALUES
    ('01a0aca9-bc00-8090-8000-000000000001', '01a0aca9-bc00-8070-8000-000000000001', 'L-DEMO-1', '2028-01-31', 12, 12, 12),
    ('01a0aca9-bc00-8090-8000-000000000002', '01a0aca9-bc00-8070-8000-000000000001', 'L-DEMO-1', '2028-01-31', 24, 24, 6)
ON CONFLICT (handling_unit_id) DO NOTHING;

INSERT INTO wms_review_v2.handling_unit_hold
    (id, handling_unit_id, reason_code, reason_detail, starts_at, released_at, created_by, released_by) VALUES
    ('01a0aca9-bc00-8092-8000-000000000001', '01a0aca9-bc00-8090-8000-000000000002', 'MANUAL', 'Revisión ficticia de calidad', '2026-09-22 14:19:08.993224+00', NULL, 'DEMO', NULL)
ON CONFLICT (id) DO NOTHING;

-- ―― Historial de movimientos ―――――――――――――――――――

INSERT INTO wms_review_v2.stock_movement
    (id, event_type, occurred_at, source_hu_id, destination_hu_id, source_location_id, destination_location_id, item_id, batch_code, box_count, units_per_box, receipt_line_id, actor) VALUES
    ('01a0aca9-bc00-8091-8000-000000000001', 'RECEIVE', '2026-09-22 14:19:08.993224+00', NULL, '01a0aca9-bc00-8090-8000-000000000001', NULL, '01a0aca9-bc00-8020-8000-000000000002', '01a0aca9-bc00-8070-8000-000000000001', 'L-DEMO-1', 12, 12, '01a0aca9-bc00-8081-8000-000000000001', 'DEMO'),
    ('01a0aca9-bc00-8091-8000-000000000002', 'RECEIVE', '2026-09-22 14:19:08.993224+00', NULL, '01a0aca9-bc00-8090-8000-000000000002', NULL, '01a0aca9-bc00-8020-8000-000000000003', '01a0aca9-bc00-8070-8000-000000000001', 'L-DEMO-1', 24, 6, '01a0aca9-bc00-8081-8000-000000000001', 'DEMO')
ON CONFLICT (id) DO NOTHING;

COMMIT;