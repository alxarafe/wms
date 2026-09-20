package com.alxarafe.app.application.catalogue;

import com.alxarafe.app.domain.catalogue.entity.ItemFamily;

import java.util.List;

public final class ListItemFamilies {
    private final ItemFamilyRepository repository;

    public ListItemFamilies(ItemFamilyRepository repository) {
        this.repository = repository;
    }

    public List<ItemFamily> execute() {
        return repository.findAll();
    }
}