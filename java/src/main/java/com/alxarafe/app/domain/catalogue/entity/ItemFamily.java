package com.alxarafe.app.domain.catalogue.entity;

import com.alxarafe.app.domain.catalogue.valueobject.ItemFamilyCode;
import com.alxarafe.app.domain.catalogue.valueobject.ItemFamilyId;

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

    public ItemFamily(ItemFamilyId id, ItemFamilyCode code, String name) {
        this.id = Objects.requireNonNull(id, "ItemFamily id cannot be null.");
        this.code = Objects.requireNonNull(code, "ItemFamily code cannot be null.");
        Objects.requireNonNull(name, "ItemFamily name cannot be null.");
        if (name.isBlank()) {
            throw new IllegalArgumentException("ItemFamily name cannot be empty.");
        }
        this.name = name;
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
}
