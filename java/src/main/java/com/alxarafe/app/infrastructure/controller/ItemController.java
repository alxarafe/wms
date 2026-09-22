package com.alxarafe.app.infrastructure.controller;

import com.alxarafe.app.application.catalogue.CreateItem;
import com.alxarafe.app.application.catalogue.ItemConflict;
import com.alxarafe.app.application.catalogue.ItemFamilyRepository;
import com.alxarafe.app.application.catalogue.ItemRepository;
import com.alxarafe.app.application.catalogue.ItemView;
import com.alxarafe.app.application.catalogue.ListItems;
import com.alxarafe.app.application.catalogue.UomRepository;
import com.alxarafe.app.domain.catalogue.entity.Item;
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
@RequestMapping("/api/items")
public class ItemController {
    private final CreateItem createItem;
    private final ListItems listItems;

    public ItemController(ItemRepository items, ItemFamilyRepository families, UomRepository uoms) {
        this.createItem = new CreateItem(items, families, uoms);
        this.listItems = new ListItems(items);
    }

    @PostMapping
    public ResponseEntity<Map<String, Object>> create(@RequestBody Map<String, Object> body) {
        Object sku = body.get("sku");
        Object name = body.get("name");
        Object familyCode = body.get("familyCode");
        Object baseUomCode = body.get("baseUomCode");
        Object isBatchManaged = body.get("isBatchManaged");
        Object isExpirable = body.get("isExpirable");
        if (!(sku instanceof String) || !(name instanceof String)
                || !(familyCode instanceof String) || !(baseUomCode instanceof String)
                || !(isBatchManaged instanceof Boolean) || !(isExpirable instanceof Boolean)) {
            throw new IllegalArgumentException(
                    "Expected sku, name, familyCode, baseUomCode, isBatchManaged and isExpirable.");
        }
        Object baseCost = body.get("baseCost");
        if (baseCost != null && !(baseCost instanceof Number)) {
            throw new IllegalArgumentException("Expected baseCost as a number.");
        }
        Object currency = body.get("currency");
        if (currency != null && !(currency instanceof String)) {
            throw new IllegalArgumentException("Expected currency as a string.");
        }

        Item item = createItem.execute((String) sku, (String) name, (String) familyCode,
                (String) baseUomCode, (Boolean) isBatchManaged, (Boolean) isExpirable,
                baseCost == null ? 0.0 : ((Number) baseCost).doubleValue(),
                currency == null ? "EUR" : (String) currency);

        Map<String, Object> response = payload(item, (String) familyCode, (String) baseUomCode);
        return ResponseEntity.status(HttpStatus.CREATED).body(response);
    }

    @GetMapping
    public ResponseEntity<List<Map<String, Object>>> list() {
        List<Map<String, Object>> items = listItems.execute().stream()
                .map(view -> payload(view.item(), view.familyCode(), view.baseUomCode()))
                .toList();
        return ResponseEntity.ok(items);
    }

    private Map<String, Object> payload(Item item, String familyCode, String baseUomCode) {
        Map<String, Object> response = new LinkedHashMap<>();
        response.put("id", item.id().value());
        response.put("sku", item.sku().value());
        response.put("name", item.name());
        response.put("familyCode", familyCode);
        response.put("baseUomCode", baseUomCode);
        response.put("isBatchManaged", item.isBatchManaged());
        response.put("isExpirable", item.isExpirable());
        response.put("baseCost", item.baseCost().amount());
        response.put("currency", item.baseCost().currency());
        return response;
    }

    @ExceptionHandler(HttpMessageNotReadableException.class)
    public ResponseEntity<Map<String, String>> invalidJson(HttpMessageNotReadableException error) {
        return ResponseEntity.badRequest().body(Map.of("error", "Invalid JSON body."));
    }

    @ExceptionHandler(IllegalArgumentException.class)
    public ResponseEntity<Map<String, String>> badRequest(IllegalArgumentException error) {
        return ResponseEntity.badRequest().body(Map.of("error", error.getMessage()));
    }

    @ExceptionHandler({ItemConflict.class, DuplicateKeyException.class})
    public ResponseEntity<Map<String, String>> conflict(Exception error) {
        return ResponseEntity.status(HttpStatus.CONFLICT)
                .body(Map.of("error", "Item sku already exists."));
    }
}