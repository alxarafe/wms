package com.alxarafe.app.application.usecase;

import com.alxarafe.app.domain.model.Greeting;
import com.alxarafe.app.domain.model.GreetingRepository;

import java.time.Instant;
import java.util.UUID;

public final class CreateGreetingUseCase {
    private final GreetingRepository repository;

    public CreateGreetingUseCase(GreetingRepository repository) {
        this.repository = repository;
    }

    public Greeting execute(String name) {
        String id = UUID.randomUUID().toString().replace("-", "");
        String message = "Hello, " + name + "!";
        Instant createdAt = Instant.now();

        Greeting greeting = new Greeting(id, message, createdAt);
        repository.save(greeting);

        return greeting;
    }
}
