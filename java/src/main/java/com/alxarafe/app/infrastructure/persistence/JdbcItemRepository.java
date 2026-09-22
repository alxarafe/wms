package com.alxarafe.app.infrastructure.persistence;

import com.alxarafe.app.application.catalogue.ItemRepository;
import com.alxarafe.app.application.catalogue.ItemView;
import com.alxarafe.app.domain.catalogue.entity.Item;
import com.alxarafe.app.domain.catalogue.valueobject.ItemFamilyId;
import com.alxarafe.app.domain.catalogue.valueobject.ItemId;
import com.alxarafe.app.domain.catalogue.valueobject.Money;
import com.alxarafe.app.domain.catalogue.valueobject.Sku;
import com.alxarafe.app.domain.catalogue.valueobject.UomId;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Repository;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
import java.util.List;
import java.util.Optional;

@Repository
public class JdbcItemRepository implements ItemRepository {
    private final JdbcTemplate jdbc;

    public JdbcItemRepository(JdbcTemplate jdbc) {
        this.jdbc = jdbc;
    }

    @Override
    public Optional<Item> findBySku(Sku sku) {
        String sql = """
                SELECT i.id, i.sku, i.name, i.family_id, i.base_uom_id,
                       i.is_batch_managed, i.is_expirable, i.base_cost, i.base_cost_currency,
                       f.code AS family_code, u.code AS uom_code
                FROM item i
                JOIN item_family f ON f.id = i.family_id
                JOIN uom u ON u.id = i.base_uom_id
                WHERE i.sku = ?""";
        List<Item> items = jdbc.query(sql, (result, row) -> hydrate(result.getString("id"),
                        result.getString("sku"), result.getString("name"),
                        result.getString("family_id"), result.getString("base_uom_id"),
                        result.getBoolean("is_batch_managed"), result.getBoolean("is_expirable"),
                        result.getDouble("base_cost"), result.getString("base_cost_currency")),
                sku.value());
        return items.stream().findFirst();
    }

    @Override
    public List<ItemView> findAll() {
        String sql = """
                SELECT i.id, i.sku, i.name, i.family_id, i.base_uom_id,
                       i.is_batch_managed, i.is_expirable, i.base_cost, i.base_cost_currency,
                       f.code AS family_code, u.code AS uom_code
                FROM item i
                JOIN item_family f ON f.id = i.family_id
                JOIN uom u ON u.id = i.base_uom_id
                ORDER BY i.sku""";
        return jdbc.query(sql, (result, row) -> new ItemView(
                hydrate(result.getString("id"), result.getString("sku"), result.getString("name"),
                        result.getString("family_id"), result.getString("base_uom_id"),
                        result.getBoolean("is_batch_managed"), result.getBoolean("is_expirable"),
                        result.getDouble("base_cost"), result.getString("base_cost_currency")),
                result.getString("family_code"),
                result.getString("uom_code")));
    }

    @Override
    @Transactional
    public void save(Item item) {
        jdbc.update("""
                INSERT INTO item (id, sku, name, family_id, base_uom_id,
                                  is_batch_managed, is_expirable, base_cost, base_cost_currency)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)""",
                item.id().value(), item.sku().value(), item.name(),
                item.familyId().value(), item.baseUomId().value(),
                item.isBatchManaged(), item.isExpirable(),
                BigDecimal.valueOf(item.baseCost().amount()), item.baseCost().currency());
    }

    private Item hydrate(String id, String sku, String name, String familyId, String baseUomId,
                         boolean isBatchManaged, boolean isExpirable,
                         double baseCost, String baseCostCurrency) {
        return new Item(new ItemId(id), new Sku(sku), name, new ItemFamilyId(familyId),
                new UomId(baseUomId), isBatchManaged, isExpirable,
                new Money(baseCost, baseCostCurrency));
    }
}