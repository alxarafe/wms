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
