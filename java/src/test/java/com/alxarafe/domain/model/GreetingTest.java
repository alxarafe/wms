package com.alxarafe.domain.model;

import com.alxarafe.app.domain.model.Greeting;
import org.junit.jupiter.api.Test;

import java.time.Instant;

import static org.junit.jupiter.api.Assertions.*;

class GreetingTest {

    @Test
    void createGreeting() {
        var id = "test-id";
        var message = "Hello, World!";
        var createdAt = Instant.parse("2026-01-01T00:00:00Z");

        var greeting = new Greeting(id, message, createdAt);

        assertEquals(id, greeting.getId());
        assertEquals(message, greeting.getMessage());
        assertEquals(createdAt, greeting.getCreatedAt());
    }
}
