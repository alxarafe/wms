package com.alxarafe.app.infrastructure.controller;

import com.alxarafe.app.application.state.WarehouseStateProvider;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.Map;

@RestController
@RequestMapping("/api/warehouses")
public class WarehouseStateController {

    private final WarehouseStateProvider provider;

    public WarehouseStateController(WarehouseStateProvider provider) {
        this.provider = provider;
    }

    @GetMapping("/{id}/state")
    public ResponseEntity<Object> state(@PathVariable String id) {
        Map<String, Object> state = provider.stateFor(id);
        if (state == null) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND).body(Map.of("error", "Warehouse not found."));
        }
        return ResponseEntity.ok(state);
    }
}