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

    Map<String, Object> receive(String locationId, String itemCode, Quantity quantity, String batchCode);

    Map<String, Object> issue(String locationId, String itemCode, Quantity quantity);
}