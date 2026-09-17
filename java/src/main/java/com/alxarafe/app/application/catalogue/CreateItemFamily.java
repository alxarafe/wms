package com.alxarafe.app.application.catalogue;

import com.alxarafe.app.domain.catalogue.entity.ItemFamily;
import com.alxarafe.app.domain.catalogue.valueobject.ItemFamilyCode;
import com.alxarafe.app.domain.catalogue.valueobject.ItemFamilyId;
import com.alxarafe.app.domain.rules.valueobject.AttributeCode;

import java.util.List;

public final class CreateItemFamily {
    private final ItemFamilyRepository repository;

    public CreateItemFamily(ItemFamilyRepository repository) {
        this.repository = repository;
    }

    public ItemFamily execute(String code, String name, List<String> attributeCodes) {
        List<AttributeCode> attributes = attributeCodes.stream().map(AttributeCode::new).toList();
        ItemFamily family = new ItemFamily(ItemFamilyId.generate(), new ItemFamilyCode(code), name, attributes);
        List<String> available = repository.availableFamilyAttributes(attributes);
        for (AttributeCode attribute : attributes) {
            if (!available.contains(attribute.value())) {
                throw new IllegalArgumentException("Unknown FAMILY attribute: " + attribute.value());
            }
        }

        if (repository.findByCode(family.code()).isPresent()) {
            throw new ItemFamilyConflict("Item family code already exists.");
        }

        repository.save(family);
        return family;
    }
}
