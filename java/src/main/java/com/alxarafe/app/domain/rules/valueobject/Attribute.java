package com.alxarafe.app.domain.rules.valueobject;

import java.util.Objects;

/**
 * Value Object representing a dynamic domain attribute.
 *
 * Can be linked to Location (e.g., HAS_REFRIGERATION) or
 * ItemFamily (e.g., COLD, HAZMAT).
 */
public record Attribute(AttributeId id, AttributeCode code, TargetType targetType) {

    public Attribute {
        Objects.requireNonNull(id, "Attribute id cannot be null.");
        Objects.requireNonNull(code, "Attribute code cannot be null.");
        Objects.requireNonNull(targetType, "Attribute targetType cannot be null.");
    }
}
