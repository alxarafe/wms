package com.alxarafe.app.application.catalogue;

import com.alxarafe.app.domain.catalogue.entity.ItemFamily;
import com.alxarafe.app.domain.catalogue.valueobject.ItemFamilyCode;
import com.alxarafe.app.domain.rules.valueobject.AttributeCode;

import java.util.List;
import java.util.Optional;

public interface ItemFamilyRepository {
    Optional<ItemFamily> findByCode(ItemFamilyCode code);

    List<ItemFamily> findAll();

    List<String> availableFamilyAttributes(List<AttributeCode> codes);

    void save(ItemFamily family);
}
