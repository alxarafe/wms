package com.alxarafe.app.application.state;

import java.util.Map;

/**
 * Output port providing the warehouse state projection for the viewer
 * (GET /api/warehouses/{id}/state).
 *
 * <p>Returns a plain map that follows the viewer contract (camelCase keys,
 * nested zones/aisles/locations). It returns {@code null} when the warehouse
 * does not exist.
 */
public interface WarehouseStateProvider {

    /**
     * @param warehouseId warehouse identifier
     * @return the nested state view or {@code null} if the warehouse is unknown
     */
    Map<String, Object> stateFor(String warehouseId);
}