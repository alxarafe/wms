package com.alxarafe.app.application.movement;

import com.alxarafe.app.domain.inventory.valueobject.Quantity;

import java.util.Map;

/**
 * Output port for stock operations (receipt and issue) backing
 * POST /api/receipts and POST /api/issues.
 *
 * <p>Applies the discrete model: one HU per location, mono-reference HU and
 * full deoccupation on issue. Methods return the projection of the affected
 * location (same contract as the warehouse viewer) and throw
 * {@link StockOperationException} for validation/conflict errors.
 */
public interface StockOperationsProvider {

    /**
     * Receipt with an optional ISO-8601 expiration date captured for the
     * batch (stored when the batch has none; a differing one is rejected).
     */
    Map<String, Object> receive(
            String locationId, String itemCode, Quantity quantity, String batchCode, String expirationDate);

    Map<String, Object> issue(String locationId, String itemCode, Quantity quantity);
}