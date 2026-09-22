package com.alxarafe.app.application.catalogue;

import java.util.List;

public final class ListItems {
    private final ItemRepository repository;

    public ListItems(ItemRepository repository) {
        this.repository = repository;
    }

    public List<ItemView> execute() {
        return repository.findAll();
    }
}