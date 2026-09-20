package com.alxarafe.app.application.movement;

/**
 * Stock operation error carrying the HTTP status it maps to, so the HTTP
 * adapter translates it directly (404 unknown, 400 input semantics,
 * 409 warehouse state conflict).
 */
public final class StockOperationException extends RuntimeException {

    private final int status;

    public StockOperationException(String message, int status) {
        super(message);
        this.status = status;
    }

    public int status() {
        return status;
    }
}