package com.alxarafe.app.infrastructure.persistence;

import com.alxarafe.app.application.catalogue.ItemFamilyRepository;
import com.alxarafe.app.domain.catalogue.entity.ItemFamily;
import com.alxarafe.app.domain.catalogue.valueobject.ItemFamilyCode;
import com.alxarafe.app.domain.catalogue.valueobject.ItemFamilyId;
import com.alxarafe.app.domain.rules.valueobject.AttributeCode;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Repository;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;
import java.util.Optional;

@Repository
public class JdbcItemFamilyRepository implements ItemFamilyRepository {
    private final JdbcTemplate jdbc;

    public JdbcItemFamilyRepository(JdbcTemplate jdbc) {
        this.jdbc = jdbc;
    }

    @Override
    public Optional<ItemFamily> findByCode(ItemFamilyCode code) {
        List<ItemFamily> families = jdbc.query(
                "SELECT id, code, name FROM item_family WHERE code = ?",
                (result, row) -> new ItemFamily(
                        new ItemFamilyId(result.getString("id")),
                        new ItemFamilyCode(result.getString("code")),
                        result.getString("name"),
                        jdbc.query(
                                "SELECT a.code FROM attribute a "
                                        + "JOIN item_family_attribute fa ON fa.attribute_id = a.id "
                                        + "WHERE fa.item_family_id = ? ORDER BY a.code",
                                (attributes, index) -> new AttributeCode(attributes.getString("code")),
                                result.getString("id"))),
                code.value());
        return families.stream().findFirst();
    }

    @Override
    public List<String> availableFamilyAttributes(List<AttributeCode> codes) {
        return codes.stream()
                .map(code -> jdbc.queryForList(
                        "SELECT code FROM attribute WHERE code = ? AND target_type = 'FAMILY'",
                        String.class, code.value()))
                .flatMap(List::stream)
                .distinct()
                .toList();
    }

    @Override
    @Transactional
    public void save(ItemFamily family) {
        jdbc.update("INSERT INTO item_family (id, code, name) VALUES (?, ?, ?)",
                family.id().value(), family.code().value(), family.name());
        for (AttributeCode attribute : family.attributes()) {
            jdbc.update("INSERT INTO item_family_attribute (item_family_id, attribute_id) "
                            + "SELECT ?, id FROM attribute WHERE code = ? AND target_type = 'FAMILY'",
                    family.id().value(), attribute.value());
        }
    }
}
