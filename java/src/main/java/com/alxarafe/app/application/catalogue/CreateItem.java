package com.alxarafe.app.application.catalogue;

import com.alxarafe.app.domain.catalogue.entity.Item;
import com.alxarafe.app.domain.catalogue.valueobject.ItemFamilyCode;
import com.alxarafe.app.domain.catalogue.valueobject.ItemId;
import com.alxarafe.app.domain.catalogue.valueobject.Money;
import com.alxarafe.app.domain.catalogue.valueobject.Sku;
import com.alxarafe.app.domain.catalogue.valueobject.UomCode;

public final class CreateItem {
    private final ItemRepository items;
    private final ItemFamilyRepository families;
    private final UomRepository uoms;

    public CreateItem(ItemRepository items, ItemFamilyRepository families, UomRepository uoms) {
        this.items = items;
        this.families = families;
        this.uoms = uoms;
    }

    public Item execute(String sku, String name, String familyCode, String baseUomCode,
                        boolean isBatchManaged, boolean isExpirable,
                        double baseCost, String currency) {
        if (name.length() > 255) {
            throw new IllegalArgumentException("Item name cannot exceed 255 characters.");
        }

        var family = families.findByCode(new ItemFamilyCode(familyCode))
                .orElseThrow(() -> new IllegalArgumentException("Item family code does not exist."));

        var uom = uoms.findByCode(new UomCode(baseUomCode))
                .orElseThrow(() -> new IllegalArgumentException("Base unit of measure code does not exist."));

        Item item = new Item(ItemId.generate(), new Sku(sku), name, family.id(), uom.id(),
                isBatchManaged, isExpirable, new Money(baseCost, currency));

        if (items.findBySku(item.sku()).isPresent()) {
            throw new ItemConflict("Item sku already exists.");
        }

        items.save(item);
        return item;
    }
}