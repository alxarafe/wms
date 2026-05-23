package com.alxarafe.app.infrastructure.persistence;

import com.alxarafe.app.domain.model.Greeting;
import com.alxarafe.app.domain.model.GreetingRepository;

import java.util.*;
import java.util.concurrent.ConcurrentHashMap;

public final class InMemoryGreetingRepository implements GreetingRepository {

    private final Map<String, Greeting> items = new ConcurrentHashMap<>();

    @Override
    public void save(Greeting greeting) {
        items.put(greeting.getId(), greeting);
    }

    @Override
    public Optional<Greeting> findById(String id) {
        return Optional.ofNullable(items.get(id));
    }

    @Override
    public List<Greeting> findAll() {
        return List.copyOf(items.values());
    }
}
