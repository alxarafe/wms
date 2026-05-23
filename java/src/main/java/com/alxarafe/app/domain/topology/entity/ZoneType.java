package com.alxarafe.app.domain.topology.entity;

import com.alxarafe.app.domain.topology.valueobject.ZoneTypeCode;
import com.alxarafe.app.domain.topology.valueobject.ZoneTypeId;

import java.util.Objects;

/**
 * Master entity representing the type of a warehouse zone.
 *
 * Defines operational characteristics like whether the zone
 * is operative and whether it allows multi-SKU storage.
 */
public final class ZoneType {

    private final ZoneTypeId id;
    private final ZoneTypeCode code;
    private final boolean isOperative;
    private final boolean allowsMultiSku;

    public ZoneType(ZoneTypeId id, ZoneTypeCode code, boolean isOperative, boolean allowsMultiSku) {
        this.id = Objects.requireNonNull(id, "ZoneType id cannot be null.");
        this.code = Objects.requireNonNull(code, "ZoneType code cannot be null.");
        this.isOperative = isOperative;
        this.allowsMultiSku = allowsMultiSku;
    }

    public ZoneTypeId id() {
        return id;
    }

    public ZoneTypeCode code() {
        return code;
    }

    public boolean isOperative() {
        return isOperative;
    }

    public boolean allowsMultiSku() {
        return allowsMultiSku;
    }
}
