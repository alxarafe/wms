package com.alxarafe.application.usecase;

import com.alxarafe.app.application.usecase.CreateGreetingUseCase;
import com.alxarafe.app.domain.model.Greeting;
import com.alxarafe.app.infrastructure.persistence.InMemoryGreetingRepository;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class CreateGreetingUseCaseTest {

    @Test
    void executeCreatesGreeting() {
        var repository = new InMemoryGreetingRepository();
        var useCase = new CreateGreetingUseCase(repository);

        var greeting = useCase.execute("World");

        assertEquals("Hello, World!", greeting.getMessage());
        assertNotNull(greeting.getId());
        assertNotNull(greeting.getCreatedAt());
    }

    @Test
    void executePersistsGreeting() {
        var repository = new InMemoryGreetingRepository();
        var useCase = new CreateGreetingUseCase(repository);

        var greeting = useCase.execute("Test");

        var found = repository.findById(greeting.getId());
        assertTrue(found.isPresent());
        assertEquals("Hello, Test!", found.get().getMessage());
    }
}
