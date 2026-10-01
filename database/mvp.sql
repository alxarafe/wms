-- database/mvp.sql-- Bootstrap reproducible del MVP WMS.-- Producción conserva las semillas; database_test se trunca después de aplicarlo.

-- ===== database/migrations/001_create_wms_schema.sql =====
-- ─────────────────────────────────────────────────────────────
-- 001_create_wms_schema.sql
-- Esquema maestro del dominio WMS (PostgreSQL)
--
-- Convenciones aplicadas:
--   - Nombres de tabla en singular, coincidiendo con las entidades del dominio.
--   - IDs de tipo VARCHAR(36) para almacenar UUID v7, tal y como los
--     genera el dominio (UuidV7Id) en PHP y Java.
--   - Los valores tipo enumerado se modelan con VARCHAR + CHECK IN (...),
--     con los mismos valores string que los enums de PHP y Java.
--   - El rol PICKING/RESERVE sigue siendo la columna nativa
--     location.role; los atributos de tipo LOCATION solo declaran
--     capacidades físicas/normativas (COLD, FOOD_SAFE...).
--   - target_type mantiene los valores LOCATION/FAMILY que usan
--     los enums del dominio (PHP/Java) y el CHECK de `attribute`.
--   - aisle_definition cuelga de la zona (zone_id), igual que la
--     entidad `aisle`; aisle_code es único dentro de la zona.
--   - picking_max_level se guarda como dato de plantilla; el rol de
--     cada hueco sigue asignándose manualmente en location.role.
-- ─────────────────────────────────────────────────────────────

BEGIN;

-- ── Topología física ─────────────────────────────────────────

CREATE TABLE IF NOT EXISTS warehouse (
    id      VARCHAR(36) PRIMARY KEY,
    code    VARCHAR(10) NOT NULL UNIQUE,
    name    VARCHAR(255) NOT NULL CHECK (name <> '')
);

CREATE TABLE IF NOT EXISTS zone_type (
    id               VARCHAR(36) PRIMARY KEY,
    code             VARCHAR(20) NOT NULL UNIQUE
                     CHECK (code ~ '^[A-Z][A-Z0-9_]{1,19}$'),
    description      TEXT,
    is_operative     BOOLEAN NOT NULL,
    allows_multi_sku BOOLEAN NOT NULL
);

-- También cubre instalaciones donde 002 ya creó zone_type sin descripción.
ALTER TABLE zone_type ADD COLUMN IF NOT EXISTS description TEXT;

CREATE TABLE IF NOT EXISTS zone (
    id                VARCHAR(36) PRIMARY KEY,
    warehouse_id      VARCHAR(36) NOT NULL REFERENCES warehouse (id),
    zone_type_id      VARCHAR(36) NOT NULL REFERENCES zone_type (id),
    code              VARCHAR(20) NOT NULL,
    code_separator    VARCHAR(3) NOT NULL CHECK (code_separator <> ''),
    warehouse_padding INTEGER NOT NULL CHECK (warehouse_padding BETWEEN 1 AND 10),
    zone_padding      INTEGER NOT NULL CHECK (zone_padding BETWEEN 1 AND 10),
    aisle_padding     INTEGER NOT NULL CHECK (aisle_padding BETWEEN 1 AND 10),
    bay_padding       INTEGER NOT NULL CHECK (bay_padding BETWEEN 1 AND 10),
    level_padding     INTEGER NOT NULL CHECK (level_padding BETWEEN 1 AND 10),
    UNIQUE (warehouse_id, code)
);

CREATE TABLE IF NOT EXISTS aisle (
    id         VARCHAR(36) PRIMARY KEY,
    zone_id    VARCHAR(36) NOT NULL REFERENCES zone (id),
    code       VARCHAR(20) NOT NULL CHECK (code <> ''),
    is_blocked BOOLEAN NOT NULL DEFAULT FALSE,
    UNIQUE (zone_id, code)
);

CREATE TABLE IF NOT EXISTS location (
    id       VARCHAR(36) PRIMARY KEY,
    aisle_id VARCHAR(36) NOT NULL REFERENCES aisle (id),
    bay      INTEGER NOT NULL CHECK (bay >= 1),
    level    INTEGER NOT NULL CHECK (level >= 1),
    code     VARCHAR(50) NOT NULL UNIQUE,
    role     VARCHAR(20) NOT NULL CHECK (role IN ('PICKING', 'RESERVE')),
    status   VARCHAR(20) NOT NULL CHECK (status IN ('ACTIVE', 'BLOCKED', 'DISABLED')),
    UNIQUE (aisle_id, bay, level)
);

-- ── Plantilla de generación de una calle (aisle_definition) ──

CREATE TABLE IF NOT EXISTS aisle_definition (
    id                VARCHAR(36) PRIMARY KEY,
    zone_id           VARCHAR(36) NOT NULL REFERENCES zone (id),
    aisle_code        VARCHAR(20) NOT NULL,
    bays              INTEGER NOT NULL CHECK (bays >= 1),
    levels            INTEGER NOT NULL CHECK (levels >= 1),
    picking_max_level INTEGER NOT NULL CHECK (picking_max_level >= 1),
    enabled           BOOLEAN NOT NULL DEFAULT TRUE,
    -- La plantilla corresponde a un pasillo real de la zona.
    UNIQUE (zone_id, aisle_code),
    FOREIGN KEY (zone_id, aisle_code) REFERENCES aisle (zone_id, code),
    CHECK (picking_max_level <= levels)
);

-- ── Catálogo logístico ───────────────────────────────────────

CREATE TABLE IF NOT EXISTS item_family (
    id                VARCHAR(36) PRIMARY KEY,
    parent_family_id  VARCHAR(36) REFERENCES item_family (id),
    code              VARCHAR(20) NOT NULL UNIQUE CHECK (code <> ''),
    name              VARCHAR(255) NOT NULL CHECK (name <> ''),
    CHECK (parent_family_id IS NULL OR parent_family_id <> id)
);

CREATE TABLE IF NOT EXISTS uom (
    id          VARCHAR(36) PRIMARY KEY,
    code        VARCHAR(10) NOT NULL UNIQUE
                CHECK (code ~ '^[A-Z][A-Z0-9]{1,9}$'),
    description VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS item (
    id               VARCHAR(36) PRIMARY KEY,
    sku              VARCHAR(50) NOT NULL UNIQUE CHECK (sku <> ''),
    name             VARCHAR(255) NOT NULL CHECK (name <> ''),
    family_id        VARCHAR(36) NOT NULL REFERENCES item_family (id),
    base_uom_id      VARCHAR(36) NOT NULL REFERENCES uom (id),
    is_batch_managed BOOLEAN NOT NULL,
    is_expirable     BOOLEAN NOT NULL,
    base_cost        NUMERIC(18, 6) NOT NULL DEFAULT 0 CHECK (base_cost >= 0),
    base_cost_currency CHAR(3) NOT NULL DEFAULT 'EUR'
                      CHECK (base_cost_currency ~ '^[A-Z]{3}$'),
    CHECK (NOT is_expirable OR is_batch_managed)
);

CREATE TABLE IF NOT EXISTS item_uom_conversion (
    item_id    VARCHAR(36) NOT NULL REFERENCES item (id),
    from_uom_id VARCHAR(36) NOT NULL REFERENCES uom (id),
    to_uom_id   VARCHAR(36) NOT NULL REFERENCES uom (id),
    factor      NUMERIC(18, 6) NOT NULL CHECK (factor > 0),
    PRIMARY KEY (item_id, from_uom_id, to_uom_id),
    CHECK (from_uom_id <> to_uom_id)
);

-- ── Inventario y carga ───────────────────────────────────────

CREATE TABLE IF NOT EXISTS batch (
    id              VARCHAR(36) PRIMARY KEY,
    item_id         VARCHAR(36) NOT NULL REFERENCES item (id),
    batch_code      VARCHAR(30) NOT NULL CHECK (batch_code <> ''),
    expiration_date TIMESTAMPTZ,
    UNIQUE (item_id, batch_code)
);

CREATE TABLE IF NOT EXISTS handling_unit (
    id           VARCHAR(36) PRIMARY KEY,
    code         VARCHAR(18) NOT NULL UNIQUE CHECK (code ~ '^\d{18}$'),
    location_id  VARCHAR(36) REFERENCES location (id),
    parent_hu_id VARCHAR(36) REFERENCES handling_unit (id),
    status       VARCHAR(20) NOT NULL CHECK (status IN ('AVAILABLE', 'IN_TRANSIT', 'BLOCKED')),
    CHECK (parent_hu_id IS NULL OR parent_hu_id <> id),
    CHECK (location_id IS NULL OR parent_hu_id IS NULL)
);

CREATE TABLE IF NOT EXISTS stock_quant (
    id       VARCHAR(36) PRIMARY KEY,
    hu_id    VARCHAR(36) NOT NULL REFERENCES handling_unit (id),
    item_id  VARCHAR(36) NOT NULL REFERENCES item (id),
    batch_id VARCHAR(36) REFERENCES batch (id),
    quantity NUMERIC(18, 6) NOT NULL CHECK (quantity > 0),
    unit     VARCHAR(10) NOT NULL CHECK (unit <> '')
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_stock_quant_hu_item_batch
    ON stock_quant (hu_id, item_id, batch_id)
    WHERE batch_id IS NOT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS uq_stock_quant_hu_item_null_batch
    ON stock_quant (hu_id, item_id)
    WHERE batch_id IS NULL;

-- ── Reglas y movimientos ─────────────────────────────────────

CREATE TABLE IF NOT EXISTS attribute (
    id          VARCHAR(36) PRIMARY KEY,
    code        VARCHAR(20) NOT NULL UNIQUE
                CHECK (code ~ '^[A-Z][A-Z0-9_]{1,19}$'),
    target_type VARCHAR(20) NOT NULL CHECK (target_type IN ('LOCATION', 'FAMILY'))
);

-- ── Atributos vinculados a huecos (capacidades) ─────────────

CREATE TABLE IF NOT EXISTS location_attribute (
    location_id  VARCHAR(36) NOT NULL REFERENCES location (id),
    attribute_id VARCHAR(36) NOT NULL REFERENCES attribute (id),
    PRIMARY KEY (location_id, attribute_id)
);

-- ── Atributos vinculados a familias de artículo (requisitos) ─

CREATE TABLE IF NOT EXISTS item_family_attribute (
    item_family_id VARCHAR(36) NOT NULL REFERENCES item_family (id),
    attribute_id   VARCHAR(36) NOT NULL REFERENCES attribute (id),
    PRIMARY KEY (item_family_id, attribute_id)
);

CREATE TABLE IF NOT EXISTS compatibility_rule (
    id                  VARCHAR(36) PRIMARY KEY,
    rule_type           VARCHAR(20) NOT NULL CHECK (rule_type IN ('REQUIRES', 'FORBIDS')),
    source_attribute_id VARCHAR(36) NOT NULL REFERENCES attribute (id),
    target_attribute_id VARCHAR(36) NOT NULL REFERENCES attribute (id),
    scope               VARCHAR(20) NOT NULL CHECK (scope IN ('LOCATION', 'AISLE', 'ZONE')),
    CHECK (source_attribute_id <> target_attribute_id)
);

-- Registro tipo ledger inmutable: no se permite modificar ni borrar movimientos.
CREATE TABLE IF NOT EXISTS stock_movement (
    id               VARCHAR(36) PRIMARY KEY,
    type             VARCHAR(20) NOT NULL CHECK (type IN ('INBOUND', 'OUTBOUND', 'TRANSFER', 'ADJUSTMENT')),
    hu_id            VARCHAR(36) NOT NULL REFERENCES handling_unit (id),
    from_location_id VARCHAR(36) REFERENCES location (id),
    to_location_id   VARCHAR(36) NOT NULL REFERENCES location (id),
    performed_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE OR REPLACE FUNCTION prevent_stock_movement_change() RETURNS trigger AS $$
BEGIN
    RAISE EXCEPTION 'stock_movement is an immutable ledger; row modification is not allowed.';
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_stock_movement_no_change ON stock_movement;
CREATE TRIGGER trg_stock_movement_no_change
    BEFORE UPDATE OR DELETE ON stock_movement
    FOR EACH ROW EXECUTE FUNCTION prevent_stock_movement_change();

-- ── Índices de claves foráneas ───────────────────────────────

CREATE INDEX IF NOT EXISTS idx_zone_warehouse_id   ON zone (warehouse_id);
CREATE INDEX IF NOT EXISTS idx_zone_zone_type_id   ON zone (zone_type_id);
CREATE INDEX IF NOT EXISTS idx_aisle_zone_id       ON aisle (zone_id);
CREATE INDEX IF NOT EXISTS idx_location_aisle_id   ON location (aisle_id);

CREATE INDEX IF NOT EXISTS idx_item_family_id      ON item (family_id);
CREATE INDEX IF NOT EXISTS idx_item_base_uom_id    ON item (base_uom_id);
CREATE INDEX IF NOT EXISTS idx_item_uom_conversion_item_id ON item_uom_conversion (item_id);
CREATE INDEX IF NOT EXISTS idx_batch_item_id       ON batch (item_id);

CREATE INDEX IF NOT EXISTS idx_handling_unit_location_id ON handling_unit (location_id);
CREATE INDEX IF NOT EXISTS idx_handling_unit_parent_hu_id ON handling_unit (parent_hu_id);
CREATE INDEX IF NOT EXISTS idx_stock_quant_hu_id   ON stock_quant (hu_id);
CREATE INDEX IF NOT EXISTS idx_stock_quant_item_id ON stock_quant (item_id);
CREATE INDEX IF NOT EXISTS idx_stock_quant_batch_id ON stock_quant (batch_id);

CREATE INDEX IF NOT EXISTS idx_compatibility_rule_source_attribute_id ON compatibility_rule (source_attribute_id);
CREATE INDEX IF NOT EXISTS idx_compatibility_rule_target_attribute_id ON compatibility_rule (target_attribute_id);

CREATE INDEX IF NOT EXISTS idx_location_attribute_attribute_id
    ON location_attribute (attribute_id);

CREATE INDEX IF NOT EXISTS idx_item_family_attribute_attribute_id
    ON item_family_attribute (attribute_id);

CREATE INDEX IF NOT EXISTS idx_stock_movement_hu_id ON stock_movement (hu_id);
CREATE INDEX IF NOT EXISTS idx_stock_movement_from_location_id ON stock_movement (from_location_id);
CREATE INDEX IF NOT EXISTS idx_stock_movement_to_location_id ON stock_movement (to_location_id);

-- ── Datos maestros iniciales ─────────────────────────────────
-- IDs UUID v7 fijos para que las referencias sean estables entre instalaciones.
-- Si ya existe un código, se conserva su configuración actual.
INSERT INTO zone_type (id, code, description, is_operative, allows_multi_sku) VALUES
    ('01a0aca9-bc00-7001-8000-000000000001', 'PICKING', 'Zona de extracción y preparación manual de pedidos', TRUE, FALSE),
    ('01a0aca9-bc00-7002-8000-000000000002', 'BULK', 'Zona de almacenaje masivo y pulmón de reposición', TRUE, TRUE),
    ('01a0aca9-bc00-7003-8000-000000000003', 'RECEPTION', 'Muelle de entrada y zona de verificación de mercancía', FALSE, TRUE),
    ('01a0aca9-bc00-7004-8000-000000000004', 'SHIPPING', 'Muelle de salida, consolidación y expedición', FALSE, TRUE),
    ('01a0aca9-bc00-7005-8000-000000000005', 'QUARANTINE', 'Zona de aislamiento para control de calidad o bloqueos', FALSE, TRUE)
ON CONFLICT (code) DO NOTHING;

-- ARTICLE en 001 designaba atributos de familia; el dominio actual usa FAMILY.
INSERT INTO attribute (id, code, target_type) VALUES
    ('01a0aca9-bc00-7006-8000-000000000006', 'COLD', 'LOCATION'),
    ('01a0aca9-bc00-7007-8000-000000000007', 'FOOD_SAFE', 'LOCATION'),
    ('01a0aca9-bc00-7008-8000-000000000008', 'CHEMICAL_SAFE', 'LOCATION'),
    ('01a0aca9-bc00-7010-8000-000000000010', 'IS_CHILLED', 'FAMILY'),
    ('01a0aca9-bc00-7009-8000-000000000009', 'IS_FOOD', 'FAMILY'),
    ('01a0aca9-bc00-700a-8000-00000000000a', 'IS_CHEMICAL', 'FAMILY'),
    ('01a0aca9-bc00-700b-8000-00000000000b', 'IS_FROZEN', 'FAMILY')
ON CONFLICT (code) DO NOTHING;

INSERT INTO uom (id, code, description) VALUES
    ('01a0aca9-bc00-700c-8000-00000000000c', 'EA', 'Unit / Each'),
    ('01a0aca9-bc00-700d-8000-00000000000d', 'BOX', 'Standard Box'),
    ('01a0aca9-bc00-700e-8000-00000000000e', 'PAL', 'Standard Pallet'),
    ('01a0aca9-bc00-700f-8000-00000000000f', 'KG', 'Kilogram')
ON CONFLICT (code) DO NOTHING;

COMMIT;

-- ===== database/migrations/002_seed_demo_data.sql =====
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
-- ===== database/migrations/003_allow_outbound_movements.sql =====
-- ─────────────────────────────────────────────────────────────
-- 003_allow_outbound_movements.sql
-- Completa la semántica de stock_movement por tipo (decisión M6).
--
-- Antes, to_location_id era NOT NULL para todos los tipos, lo que
-- impedía registrar salidas (OUTBOUND), donde la HU sale del hueco
-- y no tiene ubicación de destino.
--
-- Nueva semántica, exigida por el CHECK ck_stock_movement_directions:
--   INBOUND    : from NULL, to NOT NULL.
--   OUTBOUND   : from NOT NULL, to NULL.
--   TRANSFER   : both NOT NULL y distintas.
--   ADJUSTMENT : both NULL (ajuste de cantidades sin HU en una ubicación).
--
-- La salida desvincula la HU (location_id = NULL) pero conserva la
-- fila de handling_unit como registro histórico, porque stock_movement
-- referencia a la HU y el ledger es inmutable (ver docs/architecture).
--
-- Re-ejecutable de forma idempotente porque bin/migrate.sh aplica
-- todos los ficheros SQL.
-- ─────────────────────────────────────────────────────────────

BEGIN;

ALTER TABLE stock_movement ALTER COLUMN to_location_id DROP NOT NULL;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'ck_stock_movement_directions'
    ) THEN
        ALTER TABLE stock_movement ADD CONSTRAINT ck_stock_movement_directions CHECK (
            (type = 'INBOUND'  AND from_location_id IS NULL     AND to_location_id IS NOT NULL)
            OR (type = 'OUTBOUND' AND from_location_id IS NOT NULL AND to_location_id IS NULL)
            OR (type = 'TRANSFER'  AND from_location_id IS NOT NULL AND to_location_id IS NOT NULL
                AND from_location_id <> to_location_id)
            OR (type = 'ADJUSTMENT' AND from_location_id IS NULL AND to_location_id IS NULL)
        );
    END IF;
END $$;

COMMIT;
-- ===== database/migrations/004_create_wms_review_v2_schema.sql =====
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
-- ===== database/migrations/005_seed_wms_review_v2_demo_data.sql =====
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
-- ===== database/migrations/006_align_legacy_demo_codes.sql =====
-- Alineación incremental de la demo legacy con el formato visible del cliente.
-- No modifica Java ni crea un endpoint destructivo. Es idempotente para la
-- instalación de demostración identificada por sus UUID estables.

BEGIN;

UPDATE warehouse
SET code = 'A'
WHERE id = '01a0aca9-bc00-7010-8000-000000000001'
  AND code = 'WH1';

UPDATE zone
SET code_separator = '.',
    warehouse_padding = 1,
    zone_padding = 1,
    aisle_padding = 1,
    bay_padding = 2,
    level_padding = 1
WHERE warehouse_id = '01a0aca9-bc00-7010-8000-000000000001';

UPDATE location
SET code = 'A.1.' || lpad(bay::text, 2, '0') || '.' || level::text
WHERE aisle_id = '01a0aca9-bc00-7012-8000-000000000001';

UPDATE location
SET code = 'A.2.' || lpad(bay::text, 2, '0') || '.' || level::text
WHERE aisle_id = '01a0aca9-bc00-7012-8000-000000000002';

COMMIT;

-- ===== php/database/migrations/007_storage_attribute_catalogue.sql =====
-- Incremento PHP v2. Conserva UUID y vínculos; no altera public ni siembra datos.
-- Los cambios quedan en la transacción del ejecutor php/bin/migrate.php.
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM wms_review_v2.storage_attribute
        GROUP BY CASE upper(btrim(code))
            WHEN 'IS_FOOD' THEN 'FOOD'
            WHEN 'IS_CHEMICAL' THEN 'CHEMICAL'
            WHEN 'IS_CHILLED' THEN 'CHILLED'
            WHEN 'IS_FROZEN' THEN 'FROZEN'
            ELSE upper(btrim(code)) END
        HAVING count(*) > 1
    ) THEN
        RAISE EXCEPTION 'Storage attribute codes collide after normalization; resolve them explicitly before migrating.';
    END IF;
END $$;
UPDATE wms_review_v2.storage_attribute
SET code = CASE upper(btrim(code))
        WHEN 'IS_FOOD' THEN 'FOOD'
        WHEN 'IS_CHEMICAL' THEN 'CHEMICAL'
        WHEN 'IS_CHILLED' THEN 'CHILLED'
        WHEN 'IS_FROZEN' THEN 'FROZEN'
        ELSE upper(btrim(code)) END,
    exclusive_group_code = upper(btrim(exclusive_group_code));
ALTER TABLE wms_review_v2.storage_attribute
    ADD CONSTRAINT storage_attribute_neutral_code CHECK (
        code ~ '^[A-Z][A-Z0-9_]{0,29}$'
        AND code NOT IN ('IS_FOOD', 'IS_CHEMICAL', 'IS_CHILLED', 'IS_FROZEN')
    ),
    ADD CONSTRAINT storage_attribute_name_not_blank CHECK (name ~ '[^[:space:]]'),
    ADD CONSTRAINT storage_attribute_group_code CHECK (
        exclusive_group_code IS NULL OR exclusive_group_code ~ '^[A-Z][A-Z0-9_]{0,29}$'
    );
