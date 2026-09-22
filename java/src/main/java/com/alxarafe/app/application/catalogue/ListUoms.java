package com.alxarafe.app.application.catalogue;

import com.alxarafe.app.domain.catalogue.valueobject.Uom;

import java.util.List;

public final class ListUoms {
    private final UomRepository repository;

    public ListUoms(UomRepository repository) {
        this.repository = repository;
    }

    public List<Uom> execute() {
        return repository.findAll();
    }
}