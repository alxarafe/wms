package com.alxarafe.app.infrastructure.controller;

import com.alxarafe.app.application.catalogue.CreateItemFamily;
import com.alxarafe.app.application.catalogue.ItemFamilyConflict;
import com.alxarafe.app.application.catalogue.ItemFamilyRepository;
import com.alxarafe.app.domain.catalogue.entity.ItemFamily;
import com.alxarafe.app.domain.rules.valueobject.AttributeCode;
import org.springframework.dao.DuplicateKeyException;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.ExceptionHandler;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.http.converter.HttpMessageNotReadableException;

import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/item-families")
public class ItemFamilyController {
    private final CreateItemFamily createItemFamily;

    public ItemFamilyController(ItemFamilyRepository repository) {
        this.createItemFamily = new CreateItemFamily(repository);
    }

    @PostMapping
    public ResponseEntity<Map<String, Object>> create(@RequestBody Map<String, Object> body) {
        Object code = body.get("code");
        Object name = body.get("name");
        Object attributes = body.get("attributes");
        if (!(code instanceof String) || !(name instanceof String) || !(attributes instanceof List<?> list)) {
            throw new IllegalArgumentException("Expected code, name and attributes.");
        }
        for (Object attribute : list) {
            if (!(attribute instanceof String)) {
                throw new IllegalArgumentException("Attribute codes must be strings.");
            }
        }
        @SuppressWarnings("unchecked")
        List<String> codes = (List<String>) list;
        ItemFamily family = createItemFamily.execute((String) code, (String) name, codes);
        Map<String, Object> response = new LinkedHashMap<>();
        response.put("id", family.id().value());
        response.put("code", family.code().value());
        response.put("name", family.name());
        response.put("attributes", family.attributes().stream().map(AttributeCode::value).toList());
        return ResponseEntity.status(HttpStatus.CREATED).body(response);
    }

    @ExceptionHandler(HttpMessageNotReadableException.class)
    public ResponseEntity<Map<String, String>> invalidJson(HttpMessageNotReadableException error) {
        return ResponseEntity.badRequest().body(Map.of("error", "Invalid JSON body."));
    }

    @ExceptionHandler(IllegalArgumentException.class)
    public ResponseEntity<Map<String, String>> badRequest(IllegalArgumentException error) {
        return ResponseEntity.badRequest().body(Map.of("error", error.getMessage()));
    }

    @ExceptionHandler({ItemFamilyConflict.class, DuplicateKeyException.class})
    public ResponseEntity<Map<String, String>> conflict(Exception error) {
        return ResponseEntity.status(HttpStatus.CONFLICT)
                .body(Map.of("error", "Item family code already exists."));
    }
}
