package com.alxarafe.app.application.catalogue;

public final class UomConflict extends RuntimeException {
    public UomConflict(String message) {
        super(message);
    }
}