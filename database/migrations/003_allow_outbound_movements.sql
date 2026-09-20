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