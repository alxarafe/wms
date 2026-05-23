package com.alxarafe.app.domain.catalogue.entity;

import com.alxarafe.app.domain.catalogue.valueobject.ItemFamilyId;
import com.alxarafe.app.domain.catalogue.valueobject.ItemId;
import com.alxarafe.app.domain.catalogue.valueobject.ItemUomConversion;
import com.alxarafe.app.domain.catalogue.valueobject.Money;
import com.alxarafe.app.domain.catalogue.valueobject.Sku;
import com.alxarafe.app.domain.catalogue.valueobject.UomId;

import java.util.ArrayList;
import java.util.Collections;
import java.util.List;
import java.util.Objects;

/**
 * Aggregate Root representing a product / merchandise item.
 */
public final class Item {

    private final ItemId id;
    private final Sku sku;
    private final String name;
    private final ItemFamilyId familyId;
    private final UomId baseUomId;
    private final boolean isBatchManaged;
    private final boolean isExpirable;
    private final Money baseCost;
    private final List<ItemUomConversion> uomConversions;

    public Item(ItemId id, Sku sku, String name, ItemFamilyId familyId,
                UomId baseUomId, boolean isBatchManaged, boolean isExpirable,
                Money baseCost) {
        this.id = Objects.requireNonNull(id, "Item id cannot be null.");
        this.sku = Objects.requireNonNull(sku, "Item sku cannot be null.");
        Objects.requireNonNull(name, "Item name cannot be null.");
        if (name.isBlank()) {
            throw new IllegalArgumentException("Item name cannot be empty.");
        }
        this.name = name;
        this.familyId = Objects.requireNonNull(familyId, "Item familyId cannot be null.");
        this.baseUomId = Objects.requireNonNull(baseUomId, "Item baseUomId cannot be null.");
        if (isExpirable && !isBatchManaged) {
            throw new IllegalArgumentException(
                    "An expirable item must also be batch-managed.");
        }
        this.isBatchManaged = isBatchManaged;
        this.isExpirable = isExpirable;
        this.baseCost = Objects.requireNonNull(baseCost, "Item baseCost cannot be null.");
        this.uomConversions = new ArrayList<>();
    }

    public ItemId id() {
        return id;
    }

    public Sku sku() {
        return sku;
    }

    public String name() {
        return name;
    }

    public ItemFamilyId familyId() {
        return familyId;
    }

    public UomId baseUomId() {
        return baseUomId;
    }

    public boolean isBatchManaged() {
        return isBatchManaged;
    }

    public boolean isExpirable() {
        return isExpirable;
    }

    public Money baseCost() {
        return baseCost;
    }

    public List<ItemUomConversion> uomConversions() {
        return Collections.unmodifiableList(uomConversions);
    }

    /**
     * Adds a UoM conversion to this item.
     *
     * @throws IllegalArgumentException if a duplicate conversion exists
     */
    public void addUomConversion(ItemUomConversion conversion) {
        Objects.requireNonNull(conversion, "UoM conversion cannot be null.");
        boolean duplicate = uomConversions.stream().anyMatch(existing ->
                existing.fromUomId().equals(conversion.fromUomId())
                        && existing.toUomId().equals(conversion.toUomId()));
        if (duplicate) {
            throw new IllegalArgumentException("Duplicate UoM conversion for this item.");
        }
        uomConversions.add(conversion);
    }
}
