-- ─────────────────────────────────────────────────────────────
-- 004_create_wms_review_v2_schema.sql
-- Esquema WMS revisado v2 (PostgreSQL), instalado como esquema
-- propio `wms_review_v2` en paralelo al `public` existente.
--
-- Fuente: volcado `private/wms_lab_export.sql` (pg_dump 16.15),
-- esquema `wms_review_v2` únicamente. No se instala `wms_preview`.
--
-- Decisiones adoptadas (aprobadas por el responsable):
--   - Esquema paralelo: no se tocan `public` ni los datos actuales.
--   - IDs de tipo `uuid` nativo, fieles al volcado. La reconciliación
--     con el `public` actual (VARCHAR(36)) se decide en la fase 2.
--   - Patrón idempotente como en 001-003: re-ejecución sin fallos.
--
-- Este archivo se divide en dos partes:
--   Parte 1: esquema + 27 tablas (columnas y CHECKs inline).
--   Parte 2: PK/UNIQUE/FK, índices, funciones, triggers y comentarios.
-- ─────────────────────────────────────────────────────────────

BEGIN;

CREATE SCHEMA IF NOT EXISTS wms_review_v2;

-- ── Topología física ─────────────────────────────────────────

CREATE TABLE IF NOT EXISTS wms_review_v2.warehouse (
    id                  uuid NOT NULL,
    code                character varying(10) NOT NULL,
    name                character varying(255) NOT NULL,
    uses_zones          boolean NOT NULL DEFAULT false,
    separator           character varying(1) NOT NULL DEFAULT '.'::character varying,
    include_zone_in_code boolean NOT NULL DEFAULT false,
    aisle_digits        integer NOT NULL,
    bay_digits          integer NOT NULL,
    level_digits        integer NOT NULL,
    CONSTRAINT ck_wh_aisle_digits CHECK ((aisle_digits >= 1) AND (aisle_digits <= 9)),
    CONSTRAINT ck_wh_bay_digits CHECK ((bay_digits >= 1) AND (bay_digits <= 9)),
    CONSTRAINT ck_wh_level_digits CHECK ((level_digits >= 1) AND (level_digits <= 9)),
    CONSTRAINT warehouse_check CHECK ((NOT include_zone_in_code) OR uses_zones),
    CONSTRAINT warehouse_code_check CHECK ((btrim((code)::text) <> ''::text)),
    CONSTRAINT warehouse_name_check CHECK ((btrim((name)::text) <> ''::text))
);

CREATE TABLE IF NOT EXISTS wms_review_v2.zone (
    id                uuid NOT NULL,
    warehouse_id      uuid NOT NULL,
    code              character varying(20) NOT NULL,
    name              character varying(255) NOT NULL,
    CONSTRAINT zone_code_check CHECK ((btrim((code)::text) <> ''::text)),
    CONSTRAINT zone_name_check CHECK ((btrim((name)::text) <> ''::text))
);

CREATE TABLE IF NOT EXISTS wms_review_v2.aisle (
    id                uuid NOT NULL,
    warehouse_id      uuid NOT NULL,
    number            integer NOT NULL,
    CONSTRAINT aisle_number_check CHECK ((number >= 1))
);

CREATE TABLE IF NOT EXISTS wms_review_v2.aisle_definition (
    aisle_id          uuid NOT NULL,
    bay_count         integer NOT NULL,
    level_count       integer NOT NULL,
    CONSTRAINT aisle_definition_bay_count_check CHECK ((bay_count > 0)),
    CONSTRAINT aisle_definition_level_count_check CHECK ((level_count > 0))
);

CREATE TABLE IF NOT EXISTS wms_review_v2.aisle_level_template (
    aisle_id          uuid NOT NULL,
    warehouse_id      uuid NOT NULL,
    level             integer NOT NULL,
    location_type_id  uuid NOT NULL,
    CONSTRAINT aisle_level_template_level_check CHECK ((level > 0))
);

CREATE TABLE IF NOT EXISTS wms_review_v2.aisle_void (
    id                uuid NOT NULL,
    aisle_id          uuid NOT NULL,
    bay_from          integer NOT NULL,
    bay_to            integer NOT NULL,
    level_from        integer NOT NULL,
    level_to          integer NOT NULL,
    reason            character varying(255),
    CONSTRAINT aisle_void_bay_from_check CHECK ((bay_from > 0)),
    CONSTRAINT aisle_void_check CHECK ((bay_to >= bay_from)),
    CONSTRAINT aisle_void_check1 CHECK ((level_to >= level_from)),
    CONSTRAINT aisle_void_level_from_check CHECK ((level_from > 0))
);

CREATE TABLE IF NOT EXISTS wms_review_v2.aisle_allowed_family (
    aisle_id          uuid NOT NULL,
    item_family_id    uuid NOT NULL
);

-- ── Catálogo logístico ───────────────────────────────────────

CREATE TABLE IF NOT EXISTS wms_review_v2.item_family (
    id                uuid NOT NULL,
    parent_family_id  uuid,
    code              character varying(30) NOT NULL,
    name              character varying(255) NOT NULL,
    CONSTRAINT item_family_check CHECK ((parent_family_id IS NULL) OR (parent_family_id <> id))
);

CREATE TABLE IF NOT EXISTS wms_review_v2.uom (
    id                uuid NOT NULL,
    code              character varying(10) NOT NULL,
    description       character varying(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS wms_review_v2.item (
    id                uuid NOT NULL,
    sku               character varying(50) NOT NULL,
    name              character varying(255) NOT NULL,
    family_id         uuid NOT NULL,
    base_uom_id       uuid NOT NULL,
    is_batch_managed  boolean NOT NULL DEFAULT false,
    is_expirable      boolean NOT NULL DEFAULT false,
    CONSTRAINT item_check CHECK ((NOT is_expirable) OR is_batch_managed)
);

CREATE TABLE IF NOT EXISTS wms_review_v2.storage_attribute (
    id                    uuid NOT NULL,
    code                  character varying(30) NOT NULL,
    name                  character varying(255) NOT NULL,
    exclusive_group_code  character varying(30),
    CONSTRAINT storage_attribute_exclusive_group_code_check CHECK (((exclusive_group_code IS NULL) OR (btrim((exclusive_group_code)::text) <> ''::text)))
);

CREATE TABLE IF NOT EXISTS wms_review_v2.family_storage_attribute (
    family_id     uuid NOT NULL,
    attribute_id  uuid NOT NULL
);

-- ── Tipos de hueco y de unidad de manejo ─────────────────────

CREATE TABLE IF NOT EXISTS wms_review_v2.location_type (
    id                        uuid NOT NULL,
    warehouse_id              uuid NOT NULL,
    code                      character varying(30) NOT NULL,
    name                      character varying(255) NOT NULL,
    max_locations_per_item    integer,
    allows_multi_sku          boolean NOT NULL DEFAULT false,
    allows_multi_batch        boolean NOT NULL DEFAULT false,
    CONSTRAINT location_type_code_check CHECK ((btrim((code)::text) <> ''::text)),
    CONSTRAINT location_type_max_locations_per_item_check CHECK ((max_locations_per_item > 0))
);

CREATE TABLE IF NOT EXISTS wms_review_v2.handling_unit_type (
    id          uuid NOT NULL,
    code        character varying(30) NOT NULL,
    name        character varying(255) NOT NULL,
    is_active   boolean NOT NULL DEFAULT true,
    CONSTRAINT handling_unit_type_code_check CHECK ((btrim((code)::text) <> ''::text))
);

CREATE TABLE IF NOT EXISTS wms_review_v2.location_type_hu_policy (
    location_type_id      uuid NOT NULL,
    handling_unit_type_id uuid NOT NULL,
    accepts_full          boolean NOT NULL DEFAULT false,
    accepts_partial       boolean NOT NULL DEFAULT false,
    allows_breakdown      boolean NOT NULL DEFAULT false,
    allows_full_dispatch  boolean NOT NULL DEFAULT false,
    CONSTRAINT location_type_hu_policy_check CHECK ((accepts_full OR accepts_partial)),
    CONSTRAINT location_type_hu_policy_check1 CHECK (((NOT allows_breakdown) OR accepts_full OR accepts_partial)),
    CONSTRAINT location_type_hu_policy_check2 CHECK (((NOT allows_full_dispatch) OR accepts_full))
);

-- ── Huecos físicos ───────────────────────────────────────────

CREATE TABLE IF NOT EXISTS wms_review_v2.location (
    id                uuid NOT NULL,
    warehouse_id      uuid NOT NULL,
    aisle_id          uuid NOT NULL,
    zone_id           uuid,
    location_type_id  uuid NOT NULL,
    bay               integer NOT NULL,
    level             integer NOT NULL,
    code              character varying(80) NOT NULL,
    is_enabled        boolean NOT NULL DEFAULT true,
    CONSTRAINT location_bay_check CHECK ((bay > 0)),
    CONSTRAINT location_code_check CHECK ((btrim((code)::text) <> ''::text)),
    CONSTRAINT location_level_check CHECK ((level > 0))
);

CREATE TABLE IF NOT EXISTS wms_review_v2.location_block (
    id              uuid NOT NULL,
    location_id     uuid NOT NULL,
    reason          text NOT NULL,
    starts_at       timestamp with time zone NOT NULL,
    ends_at         timestamp with time zone,
    released_at     timestamp with time zone,
    blocks_putaway  boolean NOT NULL DEFAULT false,
    blocks_removal  boolean NOT NULL DEFAULT false,
    CONSTRAINT location_block_check CHECK ((blocks_putaway OR blocks_removal)),
    CONSTRAINT location_block_check1 CHECK (((ends_at IS NULL) OR (ends_at > starts_at))),
    CONSTRAINT location_block_check2 CHECK (((released_at IS NULL) OR (released_at >= starts_at))),
    CONSTRAINT location_block_reason_check CHECK ((btrim(reason) <> ''::text))
);

CREATE TABLE IF NOT EXISTS wms_review_v2.location_storage_attribute (
    location_id      uuid NOT NULL,
    attribute_id     uuid NOT NULL,
    remove_attribute boolean NOT NULL DEFAULT false
);

CREATE TABLE IF NOT EXISTS wms_review_v2.zone_storage_attribute (
    zone_id       uuid NOT NULL,
    attribute_id  uuid NOT NULL
);

-- ── Recepciones ──────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS wms_review_v2.receipt (
    id                 uuid NOT NULL,
    supplier_name      character varying(255) NOT NULL,
    supplier_document  character varying(100),
    received_at        timestamp with time zone DEFAULT now() NOT NULL
);

CREATE TABLE IF NOT EXISTS wms_review_v2.receipt_line (
    id              uuid NOT NULL,
    receipt_id      uuid NOT NULL,
    item_id         uuid NOT NULL,
    declared_units  integer,
    CONSTRAINT receipt_line_declared_units_check CHECK ((declared_units > 0))
);

-- ── Unidades de manejo e inventario ──────────────────────────

CREATE TABLE IF NOT EXISTS wms_review_v2.handling_unit (
    id                      uuid NOT NULL,
    code                    character varying(80) NOT NULL,
    sscc                    character varying(18),
    handling_unit_type_id   uuid NOT NULL,
    warehouse_id            uuid NOT NULL,
    location_id             uuid NOT NULL,
    receipt_line_id         uuid,
    source_pallet_id        uuid,
    source_ordinal          integer,
    fill_status             character varying(10) NOT NULL,
    lifecycle_status        character varying(10) NOT NULL,
    CONSTRAINT handling_unit_check CHECK (((source_pallet_id IS NULL) = (source_ordinal IS NULL))),
    CONSTRAINT handling_unit_check1 CHECK (((source_pallet_id IS NULL) OR (source_pallet_id <> id))),
    CONSTRAINT handling_unit_fill_status_check CHECK (((fill_status)::text = ANY ((ARRAY['FULL'::character varying, 'PARTIAL'::character varying])::text[]))),
    CONSTRAINT handling_unit_lifecycle_status_check CHECK (((lifecycle_status)::text = ANY ((ARRAY['ACTIVE'::character varying, 'SHIPPED'::character varying, 'CLOSED'::character varying])::text[]))),
    CONSTRAINT handling_unit_source_ordinal_check CHECK ((source_ordinal > 0)),
    CONSTRAINT handling_unit_sscc_check CHECK (((sscc IS NULL) OR ((sscc)::text ~ '^[0-9]{18}$'::text)))
);

CREATE TABLE IF NOT EXISTS wms_review_v2.handling_unit_content (
    handling_unit_id   uuid NOT NULL,
    item_id            uuid NOT NULL,
    batch_code         character varying(100),
    expires_on         date,
    initial_box_count  integer NOT NULL,
    box_count          integer NOT NULL,
    units_per_box      integer NOT NULL,
    CONSTRAINT handling_unit_content_batch_code_check CHECK (((batch_code IS NULL) OR (btrim((batch_code)::text) <> ''::text))),
    CONSTRAINT handling_unit_content_box_count_check CHECK ((box_count >= 0)),
    CONSTRAINT handling_unit_content_initial_box_count_check CHECK ((initial_box_count > 0)),
    CONSTRAINT handling_unit_content_units_per_box_check CHECK ((units_per_box > 0))
);

CREATE TABLE IF NOT EXISTS wms_review_v2.handling_unit_content_correction (
    id                  uuid NOT NULL,
    handling_unit_id    uuid NOT NULL,
    old_batch_code      character varying(100),
    new_batch_code      character varying(100),
    old_expires_on      date,
    new_expires_on      date,
    reason              text NOT NULL,
    corrected_by        character varying(100) NOT NULL,
    corrected_at        timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT handling_unit_content_correction_check CHECK ((((old_batch_code)::text IS DISTINCT FROM (new_batch_code)::text) OR (old_expires_on IS DISTINCT FROM new_expires_on)))
);

CREATE TABLE IF NOT EXISTS wms_review_v2.handling_unit_hold (
    id                uuid NOT NULL,
    handling_unit_id  uuid NOT NULL,
    reason_code       character varying(40) NOT NULL,
    reason_detail     text,
    starts_at         timestamp with time zone DEFAULT now() NOT NULL,
    released_at       timestamp with time zone,
    created_by        character varying(100) NOT NULL,
    released_by       character varying(100),
    CONSTRAINT handling_unit_hold_check CHECK (((released_at IS NULL) OR (released_at >= starts_at)))
);

-- ── Reglas y movimientos ─────────────────────────────────────

CREATE TABLE IF NOT EXISTS wms_review_v2.storage_rule (
    id                uuid NOT NULL,
    rule_type         character varying(30) NOT NULL,
    attribute_a_id    uuid NOT NULL,
    attribute_b_id    uuid NOT NULL,
    scope             character varying(20),
    CONSTRAINT storage_rule_check CHECK (((((rule_type)::text = 'REQUIRE_LOCATION'::text) AND (scope IS NULL)) OR (((rule_type)::text = 'SEPARATE'::text) AND ((scope)::text = 'AISLE'::text) AND (attribute_a_id < attribute_b_id)))),
    CONSTRAINT storage_rule_rule_type_check CHECK (((rule_type)::text = ANY ((ARRAY['REQUIRE_LOCATION'::character varying, 'SEPARATE'::character varying])::text[])))
);

CREATE TABLE IF NOT EXISTS wms_review_v2.stock_movement (
    id                       uuid NOT NULL,
    event_type               character varying(20) NOT NULL,
    occurred_at              timestamp with time zone DEFAULT now() NOT NULL,
    source_hu_id             uuid,
    destination_hu_id        uuid,
    source_location_id       uuid,
    destination_location_id  uuid,
    item_id                  uuid NOT NULL,
    batch_code               character varying(100),
    box_count                integer NOT NULL,
    units_per_box            integer NOT NULL,
    receipt_line_id          uuid,
    actor                    character varying(100) NOT NULL,
    CONSTRAINT stock_movement_box_count_check CHECK ((box_count > 0)),
    CONSTRAINT stock_movement_check CHECK (((source_hu_id IS NOT NULL) OR (destination_hu_id IS NOT NULL))),
    CONSTRAINT stock_movement_event_type_check CHECK (((event_type)::text = ANY ((ARRAY['RECEIVE'::character varying, 'TRANSFER'::character varying, 'SEPARATE'::character varying, 'DISPATCH'::character varying, 'CORRECT'::character varying])::text[]))),
    CONSTRAINT stock_movement_units_per_box_check CHECK ((units_per_box > 0))
);

-- ── Parte 2: claves, índices, funciones, triggers y comentarios ──

-- ―― Claves primarias ―――――――――――――――――――――――――――――――

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'aisle_allowed_family_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.aisle_allowed_family ADD CONSTRAINT aisle_allowed_family_pkey PRIMARY KEY (aisle_id, item_family_id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'aisle_definition_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.aisle_definition ADD CONSTRAINT aisle_definition_pkey PRIMARY KEY (aisle_id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'aisle_level_template_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.aisle_level_template ADD CONSTRAINT aisle_level_template_pkey PRIMARY KEY (aisle_id, level);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'aisle_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.aisle ADD CONSTRAINT aisle_pkey PRIMARY KEY (id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'aisle_void_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.aisle_void ADD CONSTRAINT aisle_void_pkey PRIMARY KEY (id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'family_storage_attribute_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.family_storage_attribute ADD CONSTRAINT family_storage_attribute_pkey PRIMARY KEY (family_id, attribute_id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'handling_unit_content_correction_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.handling_unit_content_correction ADD CONSTRAINT handling_unit_content_correction_pkey PRIMARY KEY (id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'handling_unit_content_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.handling_unit_content ADD CONSTRAINT handling_unit_content_pkey PRIMARY KEY (handling_unit_id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'handling_unit_hold_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.handling_unit_hold ADD CONSTRAINT handling_unit_hold_pkey PRIMARY KEY (id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'handling_unit_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.handling_unit ADD CONSTRAINT handling_unit_pkey PRIMARY KEY (id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'handling_unit_type_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.handling_unit_type ADD CONSTRAINT handling_unit_type_pkey PRIMARY KEY (id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'item_family_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.item_family ADD CONSTRAINT item_family_pkey PRIMARY KEY (id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'item_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.item ADD CONSTRAINT item_pkey PRIMARY KEY (id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_block_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location_block ADD CONSTRAINT location_block_pkey PRIMARY KEY (id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location ADD CONSTRAINT location_pkey PRIMARY KEY (id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_storage_attribute_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location_storage_attribute ADD CONSTRAINT location_storage_attribute_pkey PRIMARY KEY (location_id, attribute_id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_type_hu_policy_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location_type_hu_policy ADD CONSTRAINT location_type_hu_policy_pkey PRIMARY KEY (location_type_id, handling_unit_type_id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_type_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location_type ADD CONSTRAINT location_type_pkey PRIMARY KEY (id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'receipt_line_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.receipt_line ADD CONSTRAINT receipt_line_pkey PRIMARY KEY (id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'receipt_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.receipt ADD CONSTRAINT receipt_pkey PRIMARY KEY (id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'stock_movement_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.stock_movement ADD CONSTRAINT stock_movement_pkey PRIMARY KEY (id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'storage_attribute_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.storage_attribute ADD CONSTRAINT storage_attribute_pkey PRIMARY KEY (id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'storage_rule_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.storage_rule ADD CONSTRAINT storage_rule_pkey PRIMARY KEY (id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'uom_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.uom ADD CONSTRAINT uom_pkey PRIMARY KEY (id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'warehouse_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.warehouse ADD CONSTRAINT warehouse_pkey PRIMARY KEY (id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'zone_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.zone ADD CONSTRAINT zone_pkey PRIMARY KEY (id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'zone_storage_attribute_pkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.zone_storage_attribute ADD CONSTRAINT zone_storage_attribute_pkey PRIMARY KEY (zone_id, attribute_id);
    END IF;
END $$;

-- ―― Claves únicas ――――――――――――――――――――――――――――――

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'aisle_id_warehouse_id_key' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.aisle ADD CONSTRAINT aisle_id_warehouse_id_key UNIQUE (id, warehouse_id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'aisle_warehouse_id_number_key' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.aisle ADD CONSTRAINT aisle_warehouse_id_number_key UNIQUE (warehouse_id, number);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'handling_unit_code_key' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.handling_unit ADD CONSTRAINT handling_unit_code_key UNIQUE (code);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'handling_unit_source_pallet_id_source_ordinal_key' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.handling_unit ADD CONSTRAINT handling_unit_source_pallet_id_source_ordinal_key UNIQUE (source_pallet_id, source_ordinal);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'handling_unit_sscc_key' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.handling_unit ADD CONSTRAINT handling_unit_sscc_key UNIQUE (sscc);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'handling_unit_type_code_key' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.handling_unit_type ADD CONSTRAINT handling_unit_type_code_key UNIQUE (code);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'item_family_code_key' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.item_family ADD CONSTRAINT item_family_code_key UNIQUE (code);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'item_sku_key' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.item ADD CONSTRAINT item_sku_key UNIQUE (sku);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_aisle_id_bay_level_key' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location ADD CONSTRAINT location_aisle_id_bay_level_key UNIQUE (aisle_id, bay, level);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_id_warehouse_id_key' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location ADD CONSTRAINT location_id_warehouse_id_key UNIQUE (id, warehouse_id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_warehouse_id_code_key' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location ADD CONSTRAINT location_warehouse_id_code_key UNIQUE (warehouse_id, code);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_type_id_warehouse_id_key' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location_type ADD CONSTRAINT location_type_id_warehouse_id_key UNIQUE (id, warehouse_id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_type_warehouse_id_code_key' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location_type ADD CONSTRAINT location_type_warehouse_id_code_key UNIQUE (warehouse_id, code);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'storage_attribute_code_key' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.storage_attribute ADD CONSTRAINT storage_attribute_code_key UNIQUE (code);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'uom_code_key' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.uom ADD CONSTRAINT uom_code_key UNIQUE (code);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'warehouse_code_key' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.warehouse ADD CONSTRAINT warehouse_code_key UNIQUE (code);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'zone_id_warehouse_id_key' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.zone ADD CONSTRAINT zone_id_warehouse_id_key UNIQUE (id, warehouse_id);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'zone_warehouse_id_code_key' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.zone ADD CONSTRAINT zone_warehouse_id_code_key UNIQUE (warehouse_id, code);
    END IF;
END $$;

-- ―― Índices únicos parciales (reglas de almacenamiento) ―――――

CREATE UNIQUE INDEX IF NOT EXISTS uq_v2_rule_require
    ON wms_review_v2.storage_rule USING btree (attribute_a_id, attribute_b_id)
    WHERE ((rule_type)::text = 'REQUIRE_LOCATION'::text);

CREATE UNIQUE INDEX IF NOT EXISTS uq_v2_rule_separate
    ON wms_review_v2.storage_rule USING btree (attribute_a_id, attribute_b_id, scope)
    WHERE ((rule_type)::text = 'SEPARATE'::text);

-- ―― Funciones trigger ―――――――――――――――――――――――――

CREATE OR REPLACE FUNCTION wms_review_v2.check_zone_mode() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE enabled boolean;
BEGIN
 SELECT uses_zones INTO enabled FROM wms_review_v2.warehouse WHERE id = NEW.warehouse_id;
 IF enabled IS DISTINCT FROM (NEW.zone_id IS NOT NULL) THEN
   RAISE EXCEPTION 'zone_id no corresponde a uses_zones del almacén';
 END IF;
 RETURN NEW;
END; $$;

CREATE OR REPLACE FUNCTION wms_review_v2.guard_warehouse_format() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
 IF (NEW.uses_zones, NEW.separator, NEW.aisle_digits, NEW.bay_digits, NEW.level_digits, NEW.include_zone_in_code)
     IS DISTINCT FROM
    (OLD.uses_zones, OLD.separator, OLD.aisle_digits, OLD.bay_digits, OLD.level_digits, OLD.include_zone_in_code)
    AND EXISTS (SELECT 1 FROM wms_review_v2.aisle WHERE warehouse_id = OLD.id) THEN
   RAISE EXCEPTION 'Formato y modo de zonas bloqueados después de crear la primera calle';
 END IF;
 RETURN NEW;
END; $$;

-- ―― Triggers ―――――――――――――――――――――――――――――――

DROP TRIGGER IF EXISTS location_zone_mode ON wms_review_v2.location;
CREATE TRIGGER location_zone_mode
    BEFORE INSERT OR UPDATE OF warehouse_id, zone_id ON wms_review_v2.location
    FOR EACH ROW EXECUTE FUNCTION wms_review_v2.check_zone_mode();

DROP TRIGGER IF EXISTS warehouse_format_guard ON wms_review_v2.warehouse;
CREATE TRIGGER warehouse_format_guard
    BEFORE UPDATE ON wms_review_v2.warehouse
    FOR EACH ROW EXECUTE FUNCTION wms_review_v2.guard_warehouse_format();

-- ―― Claves foráneas (todas ON DELETE RESTRICT) ―――――――――――

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'aisle_allowed_family_aisle_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.aisle_allowed_family ADD CONSTRAINT aisle_allowed_family_aisle_id_fkey FOREIGN KEY (aisle_id) REFERENCES wms_review_v2.aisle(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'aisle_allowed_family_item_family_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.aisle_allowed_family ADD CONSTRAINT aisle_allowed_family_item_family_id_fkey FOREIGN KEY (item_family_id) REFERENCES wms_review_v2.item_family(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'aisle_definition_aisle_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.aisle_definition ADD CONSTRAINT aisle_definition_aisle_id_fkey FOREIGN KEY (aisle_id) REFERENCES wms_review_v2.aisle(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'aisle_level_template_aisle_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.aisle_level_template ADD CONSTRAINT aisle_level_template_aisle_id_fkey FOREIGN KEY (aisle_id) REFERENCES wms_review_v2.aisle_definition(aisle_id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'aisle_level_template_aisle_id_warehouse_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.aisle_level_template ADD CONSTRAINT aisle_level_template_aisle_id_warehouse_id_fkey FOREIGN KEY (aisle_id, warehouse_id) REFERENCES wms_review_v2.aisle(id, warehouse_id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'aisle_level_template_location_type_id_warehouse_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.aisle_level_template ADD CONSTRAINT aisle_level_template_location_type_id_warehouse_id_fkey FOREIGN KEY (location_type_id, warehouse_id) REFERENCES wms_review_v2.location_type(id, warehouse_id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'aisle_void_aisle_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.aisle_void ADD CONSTRAINT aisle_void_aisle_id_fkey FOREIGN KEY (aisle_id) REFERENCES wms_review_v2.aisle_definition(aisle_id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'aisle_warehouse_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.aisle ADD CONSTRAINT aisle_warehouse_id_fkey FOREIGN KEY (warehouse_id) REFERENCES wms_review_v2.warehouse(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'family_storage_attribute_attribute_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.family_storage_attribute ADD CONSTRAINT family_storage_attribute_attribute_id_fkey FOREIGN KEY (attribute_id) REFERENCES wms_review_v2.storage_attribute(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'family_storage_attribute_family_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.family_storage_attribute ADD CONSTRAINT family_storage_attribute_family_id_fkey FOREIGN KEY (family_id) REFERENCES wms_review_v2.item_family(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'handling_unit_content_correction_handling_unit_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.handling_unit_content_correction ADD CONSTRAINT handling_unit_content_correction_handling_unit_id_fkey FOREIGN KEY (handling_unit_id) REFERENCES wms_review_v2.handling_unit(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'handling_unit_content_handling_unit_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.handling_unit_content ADD CONSTRAINT handling_unit_content_handling_unit_id_fkey FOREIGN KEY (handling_unit_id) REFERENCES wms_review_v2.handling_unit(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'handling_unit_content_item_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.handling_unit_content ADD CONSTRAINT handling_unit_content_item_id_fkey FOREIGN KEY (item_id) REFERENCES wms_review_v2.item(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'handling_unit_handling_unit_type_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.handling_unit ADD CONSTRAINT handling_unit_handling_unit_type_id_fkey FOREIGN KEY (handling_unit_type_id) REFERENCES wms_review_v2.handling_unit_type(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'handling_unit_hold_handling_unit_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.handling_unit_hold ADD CONSTRAINT handling_unit_hold_handling_unit_id_fkey FOREIGN KEY (handling_unit_id) REFERENCES wms_review_v2.handling_unit(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'handling_unit_location_id_warehouse_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.handling_unit ADD CONSTRAINT handling_unit_location_id_warehouse_id_fkey FOREIGN KEY (location_id, warehouse_id) REFERENCES wms_review_v2.location(id, warehouse_id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'handling_unit_receipt_line_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.handling_unit ADD CONSTRAINT handling_unit_receipt_line_id_fkey FOREIGN KEY (receipt_line_id) REFERENCES wms_review_v2.receipt_line(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'handling_unit_source_pallet_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.handling_unit ADD CONSTRAINT handling_unit_source_pallet_id_fkey FOREIGN KEY (source_pallet_id) REFERENCES wms_review_v2.handling_unit(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'handling_unit_warehouse_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.handling_unit ADD CONSTRAINT handling_unit_warehouse_id_fkey FOREIGN KEY (warehouse_id) REFERENCES wms_review_v2.warehouse(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'item_base_uom_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.item ADD CONSTRAINT item_base_uom_id_fkey FOREIGN KEY (base_uom_id) REFERENCES wms_review_v2.uom(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'item_family_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.item ADD CONSTRAINT item_family_id_fkey FOREIGN KEY (family_id) REFERENCES wms_review_v2.item_family(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'item_family_parent_family_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.item_family ADD CONSTRAINT item_family_parent_family_id_fkey FOREIGN KEY (parent_family_id) REFERENCES wms_review_v2.item_family(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_aisle_id_warehouse_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location ADD CONSTRAINT location_aisle_id_warehouse_id_fkey FOREIGN KEY (aisle_id, warehouse_id) REFERENCES wms_review_v2.aisle(id, warehouse_id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_block_location_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location_block ADD CONSTRAINT location_block_location_id_fkey FOREIGN KEY (location_id) REFERENCES wms_review_v2.location(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_location_type_id_warehouse_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location ADD CONSTRAINT location_location_type_id_warehouse_id_fkey FOREIGN KEY (location_type_id, warehouse_id) REFERENCES wms_review_v2.location_type(id, warehouse_id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_storage_attribute_attribute_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location_storage_attribute ADD CONSTRAINT location_storage_attribute_attribute_id_fkey FOREIGN KEY (attribute_id) REFERENCES wms_review_v2.storage_attribute(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_storage_attribute_location_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location_storage_attribute ADD CONSTRAINT location_storage_attribute_location_id_fkey FOREIGN KEY (location_id) REFERENCES wms_review_v2.location(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_type_hu_policy_handling_unit_type_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location_type_hu_policy ADD CONSTRAINT location_type_hu_policy_handling_unit_type_id_fkey FOREIGN KEY (handling_unit_type_id) REFERENCES wms_review_v2.handling_unit_type(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_type_hu_policy_location_type_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location_type_hu_policy ADD CONSTRAINT location_type_hu_policy_location_type_id_fkey FOREIGN KEY (location_type_id) REFERENCES wms_review_v2.location_type(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_type_warehouse_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location_type ADD CONSTRAINT location_type_warehouse_id_fkey FOREIGN KEY (warehouse_id) REFERENCES wms_review_v2.warehouse(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_warehouse_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location ADD CONSTRAINT location_warehouse_id_fkey FOREIGN KEY (warehouse_id) REFERENCES wms_review_v2.warehouse(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'location_zone_id_warehouse_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.location ADD CONSTRAINT location_zone_id_warehouse_id_fkey FOREIGN KEY (zone_id, warehouse_id) REFERENCES wms_review_v2.zone(id, warehouse_id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'receipt_line_item_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.receipt_line ADD CONSTRAINT receipt_line_item_id_fkey FOREIGN KEY (item_id) REFERENCES wms_review_v2.item(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'receipt_line_receipt_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.receipt_line ADD CONSTRAINT receipt_line_receipt_id_fkey FOREIGN KEY (receipt_id) REFERENCES wms_review_v2.receipt(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'stock_movement_destination_hu_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.stock_movement ADD CONSTRAINT stock_movement_destination_hu_id_fkey FOREIGN KEY (destination_hu_id) REFERENCES wms_review_v2.handling_unit(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'stock_movement_destination_location_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.stock_movement ADD CONSTRAINT stock_movement_destination_location_id_fkey FOREIGN KEY (destination_location_id) REFERENCES wms_review_v2.location(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'stock_movement_item_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.stock_movement ADD CONSTRAINT stock_movement_item_id_fkey FOREIGN KEY (item_id) REFERENCES wms_review_v2.item(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'stock_movement_receipt_line_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.stock_movement ADD CONSTRAINT stock_movement_receipt_line_id_fkey FOREIGN KEY (receipt_line_id) REFERENCES wms_review_v2.receipt_line(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'stock_movement_source_hu_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.stock_movement ADD CONSTRAINT stock_movement_source_hu_id_fkey FOREIGN KEY (source_hu_id) REFERENCES wms_review_v2.handling_unit(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'stock_movement_source_location_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.stock_movement ADD CONSTRAINT stock_movement_source_location_id_fkey FOREIGN KEY (source_location_id) REFERENCES wms_review_v2.location(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'storage_rule_attribute_a_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.storage_rule ADD CONSTRAINT storage_rule_attribute_a_id_fkey FOREIGN KEY (attribute_a_id) REFERENCES wms_review_v2.storage_attribute(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'storage_rule_attribute_b_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.storage_rule ADD CONSTRAINT storage_rule_attribute_b_id_fkey FOREIGN KEY (attribute_b_id) REFERENCES wms_review_v2.storage_attribute(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'zone_storage_attribute_attribute_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.zone_storage_attribute ADD CONSTRAINT zone_storage_attribute_attribute_id_fkey FOREIGN KEY (attribute_id) REFERENCES wms_review_v2.storage_attribute(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'zone_storage_attribute_zone_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.zone_storage_attribute ADD CONSTRAINT zone_storage_attribute_zone_id_fkey FOREIGN KEY (zone_id) REFERENCES wms_review_v2.zone(id) ON DELETE RESTRICT;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                    WHERE c.conname = 'zone_warehouse_id_fkey' AND t.relnamespace = 'wms_review_v2'::regnamespace) THEN
        ALTER TABLE ONLY wms_review_v2.zone ADD CONSTRAINT zone_warehouse_id_fkey FOREIGN KEY (warehouse_id) REFERENCES wms_review_v2.warehouse(id) ON DELETE RESTRICT;
    END IF;
END $$;

-- ―― Comentarios (fieles al volcado) ―――――――――――――――

COMMENT ON TABLE wms_review_v2.aisle IS 'Número estructural, no código textual: los ceros a la izquierda son presentación.';
COMMENT ON TABLE wms_review_v2.aisle_allowed_family IS 'Sin filas: sin filtro familiar. Con filas: admite estas familias y descendientes; no sustituye otras reglas.';
COMMENT ON TABLE wms_review_v2.aisle_void IS 'Sin location en estos espacios; numeración del bay no se comprime. Validar límites/solapes en generador.';
COMMENT ON COLUMN wms_review_v2.handling_unit.sscc IS 'Único si existe. Se valida dígito GS1 en aplicación; code interno identifica HU sin SSCC.';
COMMENT ON TABLE wms_review_v2.handling_unit_content IS 'Unidades actuales = box_count * units_per_box; FULL/PARTIAL no se deriva solo de este número.';
COMMENT ON TABLE wms_review_v2.handling_unit_hold IS 'Existencia física retenida. Disponibilidad: datos exigidos completos y sin retenciones activas.';
COMMENT ON TABLE wms_review_v2.handling_unit_type IS 'PALLET/BOX son datos configurables, distintos de uom PAL/BOX.';
COMMENT ON TABLE wms_review_v2.location IS 'Solo huecos físicos; code representado según formato del almacén; sin validación del formato en SQL.';
COMMENT ON TABLE wms_review_v2.location_block IS 'Bloqueos históricos del hueco; retenciones de mercancía van en handling_unit_hold.';
COMMENT ON COLUMN wms_review_v2.location_storage_attribute.remove_attribute IS 'Excepción FUTURA; no se evalúa en MVP. true retira atributo heredado de zona.';
COMMENT ON TABLE wms_review_v2.location_type_hu_policy IS 'Las políticas de operación requieren un servicio; esta tabla define configuraciones.';
COMMENT ON TABLE wms_review_v2.stock_movement IS 'Historial de operaciones; aplicación actualiza HU/contenido + inserta movimiento en la misma transacción.';
COMMENT ON TABLE wms_review_v2.warehouse IS 'Dígitos de calle, posición y altura configurados; formato bloqueado tras la primera calle.';

COMMIT;