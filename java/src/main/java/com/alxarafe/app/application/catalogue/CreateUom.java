package com.alxarafe.app.application.catalogue;

import com.alxarafe.app.domain.catalogue.valueobject.Uom;
import com.alxarafe.app.domain.catalogue.valueobject.UomCode;
import com.alxarafe.app.domain.catalogue.valueobject.UomId;

public final class CreateUom {
    private final UomRepository repository;

    public CreateUom(UomRepository repository) {
        this.repository = repository;
    }

    public Uom execute(String code, String description) {
        if (description.length() > 255) {
            throw new IllegalArgumentException("Uom description cannot exceed 255 characters.");
        }

        Uom uom = new Uom(UomId.generate(), new UomCode(code), description);
        if (repository.findByCode(uom.code()).isPresent()) {
            throw new UomConflict("Uom code already exists.");
        }

        repository.save(uom);
        return uom;
    }
}