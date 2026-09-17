-- ─────────────────────────────────────────────────────────────
-- 002_create_wms_schema.sql
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
    is_operative     BOOLEAN NOT NULL,
    allows_multi_sku BOOLEAN NOT NULL
);

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
    id   VARCHAR(36) PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE CHECK (code <> ''),
    name VARCHAR(255) NOT NULL CHECK (name <> '')
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