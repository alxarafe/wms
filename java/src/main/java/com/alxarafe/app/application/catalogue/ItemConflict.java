package com.alxarafe.app.application.catalogue;

public final class ItemConflict extends RuntimeException {
    public ItemConflict(String message) {
        super(message);
    }
}