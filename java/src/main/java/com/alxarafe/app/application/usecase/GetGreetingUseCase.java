package com.alxarafe.app.application.usecase;

import com.alxarafe.app.domain.model.Greeting;
import com.alxarafe.app.domain.model.GreetingRepository;

import java.util.List;
import java.util.Optional;

public final class GetGreetingUseCase {
    private final GreetingRepository repository;

    public GetGreetingUseCase(GreetingRepository repository) {
        this.repository = repository;
    }

    public Optional<Greeting> execute(String id) {
        return repository.findById(id);
    }

    public List<Greeting> findAll() {
        return repository.findAll();
    }
}
