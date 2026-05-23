package com.alxarafe.app.domain.model;

import java.util.List;
import java.util.Optional;

public interface GreetingRepository {
    void save(Greeting greeting);
    Optional<Greeting> findById(String id);
    List<Greeting> findAll();
}
