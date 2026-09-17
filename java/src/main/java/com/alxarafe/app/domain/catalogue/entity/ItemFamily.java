package com.alxarafe.app.domain.catalogue.entity;

import com.alxarafe.app.domain.catalogue.valueobject.ItemFamilyCode;
import com.alxarafe.app.domain.catalogue.valueobject.ItemFamilyId;
import com.alxarafe.app.domain.rules.valueobject.AttributeCode;

import java.util.List;
import java.util.Objects;

/**
 * Entity representing a product family / category.
 *
 * ItemFamily is the anchor for risk attributes (COLD, HAZMAT, etc.)
 * and compatibility rules, avoiding per-SKU redundancy.
 */
public final class ItemFamily {

    private final ItemFamilyId id;
    private final ItemFamilyCode code;
    private final String name;
    private final List<AttributeCode> attributes;

    public ItemFamily(ItemFamilyId id, ItemFamilyCode code, String name) {
        this(id, code, name, List.of());
    }

    public ItemFamily(ItemFamilyId id, ItemFamilyCode code, String name, List<AttributeCode> attributes) {
        this.id = Objects.requireNonNull(id, "ItemFamily id cannot be null.");
        this.code = Objects.requireNonNull(code, "ItemFamily code cannot be null.");
        Objects.requireNonNull(name, "ItemFamily name cannot be null.");
        if (name.isBlank()) {
            throw new IllegalArgumentException("ItemFamily name cannot be empty.");
        }
        if (name.length() > 255) {
            throw new IllegalArgumentException("ItemFamily name cannot exceed 255 characters.");
        }
        this.name = name;
        Objects.requireNonNull(attributes, "ItemFamily attributes cannot be null.");
        this.attributes = List.copyOf(attributes);
        if (this.attributes.stream().distinct().count() != this.attributes.size()) {
            throw new IllegalArgumentException("ItemFamily attributes must be unique.");
        }
    }

    public ItemFamilyId id() {
        return id;
    }

    public ItemFamilyCode code() {
        return code;
    }

    public String name() {
        return name;
    }

    public List<AttributeCode> attributes() {
        return attributes;
    }
}
