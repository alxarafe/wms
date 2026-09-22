-- Incremento exclusivo de la línea PHP. No carga datos ni altera public.
ALTER TABLE wms_review_v2.handling_unit_type
    ADD CONSTRAINT hu_type_name_not_blank CHECK (btrim(name) <> '');
ALTER TABLE wms_review_v2.location_type
    ADD CONSTRAINT location_type_name_not_blank CHECK (btrim(name) <> '');
ALTER TABLE wms_review_v2.warehouse
    ADD CONSTRAINT warehouse_separator_not_blank CHECK (btrim(separator) <> '');

-- Código de error estable: no interpretar mensajes SQL localizados en HTTP.
CREATE OR REPLACE FUNCTION wms_review_v2.guard_warehouse_format() RETURNS trigger
LANGUAGE plpgsql AS $$
BEGIN
    IF (NEW.uses_zones, NEW.separator, NEW.aisle_digits, NEW.bay_digits, NEW.level_digits, NEW.include_zone_in_code)
        IS DISTINCT FROM
       (OLD.uses_zones, OLD.separator, OLD.aisle_digits, OLD.bay_digits, OLD.level_digits, OLD.include_zone_in_code)
       AND EXISTS (SELECT 1 FROM wms_review_v2.aisle WHERE warehouse_id = OLD.id) THEN
        RAISE EXCEPTION 'Warehouse format is locked after the first aisle.' USING ERRCODE = 'WMS01';
    END IF;
    RETURN NEW;
END;
$$;

-- Serializa altas de calle y cambios de formato sobre el mismo almacén.
CREATE FUNCTION wms_review_v2.lock_aisle_warehouse() RETURNS trigger
LANGUAGE plpgsql AS $$
BEGIN
    PERFORM id FROM wms_review_v2.warehouse WHERE id = NEW.warehouse_id FOR UPDATE;
    RETURN NEW;
END;
$$;
CREATE TRIGGER aisle_warehouse_lock BEFORE INSERT OR UPDATE OF warehouse_id ON wms_review_v2.aisle
    FOR EACH ROW EXECUTE FUNCTION wms_review_v2.lock_aisle_warehouse();
