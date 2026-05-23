package com.alxarafe.app.domain.catalogue.valueobject;

import java.util.Objects;

/**
 * Value Object representing a conversion factor between two units of measure.
 */
public record ItemUomConversion(UomId fromUomId, UomId toUomId, double factor) {

    public ItemUomConversion {
        Objects.requireNonNull(fromUomId, "ItemUomConversion fromUomId cannot be null.");
        Objects.requireNonNull(toUomId, "ItemUomConversion toUomId cannot be null.");
        if (factor <= 0.0) {
            throw new IllegalArgumentException(
                    "ItemUomConversion factor must be positive. Got: " + factor);
        }
        if (fromUomId.equals(toUomId)) {
            throw new IllegalArgumentException(
                    "ItemUomConversion cannot convert a UoM to itself.");
        }
    }
}
