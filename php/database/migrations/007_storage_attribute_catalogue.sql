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
