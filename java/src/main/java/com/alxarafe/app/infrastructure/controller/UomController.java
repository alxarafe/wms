package com.alxarafe.app.infrastructure.controller;

import com.alxarafe.app.application.catalogue.CreateUom;
import com.alxarafe.app.application.catalogue.UomConflict;
import com.alxarafe.app.application.catalogue.UomRepository;
import com.alxarafe.app.application.catalogue.ListUoms;
import com.alxarafe.app.domain.catalogue.valueobject.Uom;
import org.springframework.dao.DuplicateKeyException;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.http.converter.HttpMessageNotReadableException;
import org.springframework.web.bind.annotation.ExceptionHandler;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/uoms")
public class UomController {
    private final CreateUom createUom;
    private final ListUoms listUoms;

    public UomController(UomRepository repository) {
        this.createUom = new CreateUom(repository);
        this.listUoms = new ListUoms(repository);
    }

    @PostMapping
    public ResponseEntity<Map<String, Object>> create(@RequestBody Map<String, Object> body) {
        Object code = body.get("code");
        Object description = body.get("description");
        if (!(code instanceof String) || !(description instanceof String)) {
            throw new IllegalArgumentException("Expected code and description.");
        }
        Uom uom = createUom.execute((String) code, (String) description);
        Map<String, Object> response = new LinkedHashMap<>();
        response.put("id", uom.id().value());
        response.put("code", uom.code().value());
        response.put("description", uom.description());
        return ResponseEntity.status(HttpStatus.CREATED).body(response);
    }

    @GetMapping
    public ResponseEntity<List<Map<String, Object>>> list() {
        List<Map<String, Object>> uoms = listUoms.execute().stream()
                .map(uom -> {
                    Map<String, Object> response = new LinkedHashMap<>();
                    response.put("id", uom.id().value());
                    response.put("code", uom.code().value());
                    response.put("description", uom.description());
                    return response;
                })
                .toList();
        return ResponseEntity.ok(uoms);
    }

    @ExceptionHandler(HttpMessageNotReadableException.class)
    public ResponseEntity<Map<String, String>> invalidJson(HttpMessageNotReadableException error) {
        return ResponseEntity.badRequest().body(Map.of("error", "Invalid JSON body."));
    }

    @ExceptionHandler(IllegalArgumentException.class)
    public ResponseEntity<Map<String, String>> badRequest(IllegalArgumentException error) {
        return ResponseEntity.badRequest().body(Map.of("error", error.getMessage()));
    }

    @ExceptionHandler({UomConflict.class, DuplicateKeyException.class})
    public ResponseEntity<Map<String, String>> conflict(Exception error) {
        return ResponseEntity.status(HttpStatus.CONFLICT)
                .body(Map.of("error", "Uom code already exists."));
    }
}