package com.alxarafe.app.application.catalogue;

import com.alxarafe.app.domain.catalogue.entity.Item;
import com.alxarafe.app.domain.catalogue.valueobject.Sku;

import java.util.List;
import java.util.Optional;

public interface ItemRepository {
    Optional<Item> findBySku(Sku sku);

    List<ItemView> findAll();

    void save(Item item);
}