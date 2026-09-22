package com.alxarafe.app.infrastructure.persistence;

import com.alxarafe.app.application.catalogue.UomRepository;
import com.alxarafe.app.domain.catalogue.valueobject.Uom;
import com.alxarafe.app.domain.catalogue.valueobject.UomCode;
import com.alxarafe.app.domain.catalogue.valueobject.UomId;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Repository;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;
import java.util.Optional;

@Repository
public class JdbcUomRepository implements UomRepository {
    private final JdbcTemplate jdbc;

    public JdbcUomRepository(JdbcTemplate jdbc) {
        this.jdbc = jdbc;
    }

    @Override
    public Optional<Uom> findByCode(UomCode code) {
        List<Uom> uoms = jdbc.query(
                "SELECT id, code, description FROM uom WHERE code = ?",
                (result, row) -> hydrate(result.getString("id"), result.getString("code"), result.getString("description")),
                code.value());
        return uoms.stream().findFirst();
    }

    @Override
    public List<Uom> findAll() {
        return jdbc.query(
                "SELECT id, code, description FROM uom ORDER BY code",
                (result, row) -> hydrate(result.getString("id"), result.getString("code"), result.getString("description")));
    }

    @Override
    @Transactional
    public void save(Uom uom) {
        jdbc.update("INSERT INTO uom (id, code, description) VALUES (?, ?, ?)",
                uom.id().value(), uom.code().value(), uom.description());
    }

    private Uom hydrate(String id, String code, String description) {
        return new Uom(new UomId(id), new UomCode(code), description);
    }
}