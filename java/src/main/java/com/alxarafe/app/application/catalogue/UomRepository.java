package com.alxarafe.app.application.catalogue;

import com.alxarafe.app.domain.catalogue.valueobject.Uom;
import com.alxarafe.app.domain.catalogue.valueobject.UomCode;

import java.util.List;
import java.util.Optional;

public interface UomRepository {
    Optional<Uom> findByCode(UomCode code);

    List<Uom> findAll();

    void save(Uom uom);
}