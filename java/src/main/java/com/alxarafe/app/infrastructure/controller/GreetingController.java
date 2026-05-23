package com.alxarafe.app.infrastructure.controller;

import com.alxarafe.app.application.usecase.CreateGreetingUseCase;
import com.alxarafe.app.application.usecase.GetGreetingUseCase;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

import java.time.Instant;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api")
public class GreetingController {

    private final CreateGreetingUseCase createUseCase;
    private final GetGreetingUseCase getUseCase;

    public GreetingController(CreateGreetingUseCase createUseCase, GetGreetingUseCase getUseCase) {
        this.createUseCase = createUseCase;
        this.getUseCase = getUseCase;
    }

    @GetMapping("/health")
    public ResponseEntity<Map<String, Object>> health() {
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("status", "ok");
        body.put("timestamp", Instant.now().toString());
        return ResponseEntity.ok(body);
    }

    @GetMapping("/greet")
    public ResponseEntity<Map<String, Object>> greet(@RequestParam(defaultValue = "World") String name) {
        var greeting = createUseCase.execute(name);
        Map<String, Object> body = new LinkedHashMap<>();
        body.put("id", greeting.getId());
        body.put("message", greeting.getMessage());
        body.put("createdAt", greeting.getCreatedAt().toString());
        return ResponseEntity.ok(body);
    }

    @GetMapping("/greetings")
    public ResponseEntity<List<Map<String, Object>>> greetings() {
        var greetings = getUseCase.findAll();
        var data = greetings.stream()
                .map(g -> {
                    Map<String, Object> item = new LinkedHashMap<>();
                    item.put("id", g.getId());
                    item.put("message", g.getMessage());
                    item.put("createdAt", g.getCreatedAt().toString());
                    return item;
                })
                .toList();
        return ResponseEntity.ok(data);
    }
}
