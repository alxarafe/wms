package com.alxarafe.app.domain.model;

import java.time.Instant;

public final class Greeting {
    private final String id;
    private final String message;
    private final Instant createdAt;

    public Greeting(String id, String message, Instant createdAt) {
        this.id = id;
        this.message = message;
        this.createdAt = createdAt;
    }

    public String getId() {
        return id;
    }

    public String getMessage() {
        return message;
    }

    public Instant getCreatedAt() {
        return createdAt;
    }
}
