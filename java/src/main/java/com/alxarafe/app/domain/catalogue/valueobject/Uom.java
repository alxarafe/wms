package com.alxarafe.app.domain.catalogue.valueobject;

import java.util.Objects;

/**
 * Value Object representing a Unit of Measure.
 */
public record Uom(UomId id, UomCode code, String description) {

    public Uom {
        Objects.requireNonNull(id, "Uom id cannot be null.");
        Objects.requireNonNull(code, "Uom code cannot be null.");
        Objects.requireNonNull(description, "Uom description cannot be null.");
    }
}
