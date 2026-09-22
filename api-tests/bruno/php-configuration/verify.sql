\set ON_ERROR_STOP on
DO $$
DECLARE
    table_name text;
    total bigint;
BEGIN
    IF (SELECT count(*) FROM public.php_schema_migration) <> 2 THEN
        RAISE EXCEPTION 'No se ejecutó toda la línea de migraciones PHP';
    END IF;
    IF EXISTS (
        WITH expected(code, name, uses_zones, separator, include_zone_in_code, aisle_digits, bay_digits, level_digits) AS (
            VALUES ('A', 'Principal', false, '.', false, 2, 2, 1),
                   ('B', 'Secundario', true, '.', true, 1, 2, 1)
        ), actual AS (
            SELECT code, name, uses_zones, separator, include_zone_in_code, aisle_digits, bay_digits, level_digits
            FROM wms_review_v2.warehouse
        )
        (TABLE expected EXCEPT TABLE actual) UNION ALL (TABLE actual EXCEPT TABLE expected)
    ) THEN RAISE EXCEPTION 'Almacenes persistidos distintos de los enviados por HTTP'; END IF;

    IF EXISTS (
        WITH expected(code, name, is_active) AS (
            VALUES ('PALLET', 'Palé', true), ('BOX', 'Caja', true), ('CRATE', 'Contenedor configurable', false)
        ), actual AS (SELECT code, name, is_active FROM wms_review_v2.handling_unit_type)
        (TABLE expected EXCEPT TABLE actual) UNION ALL (TABLE actual EXCEPT TABLE expected)
    ) THEN RAISE EXCEPTION 'Tipos HU inesperados'; END IF;

    IF EXISTS (
        WITH expected(warehouse, code, name, max_locations_per_item, allows_multi_sku, allows_multi_batch) AS (
            VALUES ('A', 'PICKING', 'Preparación', 1, false, false),
                   ('A', 'RESERVE', 'Reserva', NULL, false, false),
                   ('A', 'RETURNS', 'Devoluciones', NULL, true, true),
                   ('A', 'CUSTOM', 'Configuración libre', NULL, false, false),
                   ('B', 'PICKING', 'Preparación B', 3, false, false)
        ), actual AS (
            SELECT w.code, t.code, t.name, t.max_locations_per_item, t.allows_multi_sku, t.allows_multi_batch
            FROM wms_review_v2.location_type t JOIN wms_review_v2.warehouse w ON w.id = t.warehouse_id
        )
        (TABLE expected EXCEPT TABLE actual) UNION ALL (TABLE actual EXCEPT TABLE expected)
    ) THEN RAISE EXCEPTION 'Tipos de hueco inesperados'; END IF;

    IF EXISTS (
        WITH expected(warehouse, location_type, hu_type, accepts_full, accepts_partial, allows_breakdown, allows_full_dispatch) AS (
            VALUES ('A', 'PICKING', 'PALLET', true, true, true, false),
                   ('A', 'RESERVE', 'PALLET', true, false, false, true),
                   ('A', 'RETURNS', 'BOX', false, true, false, false),
                   ('A', 'CUSTOM', 'CRATE', true, true, true, true)
        ), actual AS (
            SELECT w.code, l.code, h.code, p.accepts_full, p.accepts_partial, p.allows_breakdown, p.allows_full_dispatch
            FROM wms_review_v2.location_type_hu_policy p
            JOIN wms_review_v2.location_type l ON l.id = p.location_type_id
            JOIN wms_review_v2.warehouse w ON w.id = l.warehouse_id
            JOIN wms_review_v2.handling_unit_type h ON h.id = p.handling_unit_type_id
        )
        (TABLE expected EXCEPT TABLE actual) UNION ALL (TABLE actual EXCEPT TABLE expected)
    ) THEN RAISE EXCEPTION 'Políticas inesperadas'; END IF;

    IF EXISTS (
        SELECT id FROM wms_review_v2.warehouse UNION ALL
        SELECT id FROM wms_review_v2.handling_unit_type UNION ALL
        SELECT id FROM wms_review_v2.location_type
        EXCEPT SELECT id FROM (
            SELECT id FROM wms_review_v2.warehouse UNION ALL
            SELECT id FROM wms_review_v2.handling_unit_type UNION ALL
            SELECT id FROM wms_review_v2.location_type
        ) ids WHERE id::text ~ '^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$'
    ) THEN RAISE EXCEPTION 'Identificadores no generados como UUID v7'; END IF;

    FOR table_name IN SELECT tablename FROM pg_tables WHERE schemaname = 'wms_review_v2'
        AND tablename NOT IN ('warehouse', 'handling_unit_type', 'location_type', 'location_type_hu_policy', 'aisle')
    LOOP
        EXECUTE format('SELECT count(*) FROM wms_review_v2.%I', table_name) INTO total;
        IF total <> 0 THEN RAISE EXCEPTION 'Semillas o efectos ajenos en %', table_name; END IF;
    END LOOP;
    FOR table_name IN SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename <> 'php_schema_migration'
    LOOP
        EXECUTE format('SELECT count(*) FROM public.%I', table_name) INTO total;
        IF total <> 0 THEN RAISE EXCEPTION 'Semillas no autorizadas en public.%', table_name; END IF;
    END LOOP;
END $$;
SELECT 1 / ((SELECT count(*) FROM wms_review_v2.aisle) = :expected_aisles)::int AS aisle_fixture_verified;
