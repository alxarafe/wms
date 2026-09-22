package com.alxarafe.app.infrastructure.controller;

import com.alxarafe.app.application.movement.StockOperationException;
import com.alxarafe.app.application.movement.StockOperationsProvider;
import com.alxarafe.app.domain.inventory.valueobject.Quantity;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.http.converter.HttpMessageNotReadableException;
import org.springframework.web.bind.annotation.ExceptionHandler;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.Map;

/**
 * HTTP adapter for stock receipts (POST /api/receipts).
 */
@RestController
@RequestMapping("/api/receipts")
public class ReceiptsController {

    private final StockOperationsProvider provider;

    public ReceiptsController(StockOperationsProvider provider) {
        this.provider = provider;
    }

    @PostMapping
    public ResponseEntity<Map<String, Object>> receive(@RequestBody Map<String, Object> body) {
        String locationId = requireString(body, "locationId");
        String itemCode = requireString(body, "itemCode");
        String unit = requireString(body, "unit");
        double quantity = requireNumber(body, "quantity");
        String batchCode = optionalString(body.get("batchCode"), "batchCode");
        String expirationDate = optionalString(body.get("expirationDate"), "expirationDate");

        Map<String, Object> view = provider.receive(
                locationId, itemCode, Quantity.fromDecimal(quantity, unit), batchCode, expirationDate);
        return ResponseEntity.status(HttpStatus.CREATED).body(view);
    }

    @ExceptionHandler(HttpMessageNotReadableException.class)
    public ResponseEntity<Map<String, String>> invalidJson(HttpMessageNotReadableException error) {
        return ResponseEntity.badRequest().body(Map.of("error", "Invalid JSON body."));
    }

    @ExceptionHandler(IllegalArgumentException.class)
    public ResponseEntity<Map<String, String>> badRequest(IllegalArgumentException error) {
        return ResponseEntity.badRequest().body(Map.of("error", error.getMessage()));
    }

    @ExceptionHandler(StockOperationException.class)
    public ResponseEntity<Map<String, String>> stockOperation(StockOperationException error) {
        return ResponseEntity.status(error.status()).body(Map.of("error", error.getMessage()));
    }

    private static String requireString(Map<String, Object> body, String key) {
        Object value = body.get(key);
        if (!(value instanceof String string) || string.isBlank()) {
            throw new IllegalArgumentException("Expected a non-empty string for " + key + ".");
        }
        return string;
    }

    private static double requireNumber(Map<String, Object> body, String key) {
        Object value = body.get(key);
        if (!(value instanceof Number number) || !Double.isFinite(number.doubleValue())) {
            throw new IllegalArgumentException("Expected a finite number for " + key + ".");
        }
        return number.doubleValue();
    }

    private static String optionalString(Object value, String field) {
        if (value == null) {
            return null;
        }
        if (!(value instanceof String string) || string.isBlank()) {
            throw new IllegalArgumentException("Expected a non-empty string for " + field + ".");
        }
        return string;
    }
}