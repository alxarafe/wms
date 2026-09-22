package com.alxarafe.app.application.catalogue;

import com.alxarafe.app.domain.catalogue.entity.Item;

/**
 * Projection of an item with the codes of its family and base UoM,
 * used to shape the HTTP responses without leaking identifiers to clients.
 */
public record ItemView(Item item, String familyCode, String baseUomCode) {
}