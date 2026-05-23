package com.alxarafe.app.infrastructure.persistence;

import com.alxarafe.app.domain.model.Greeting;
import com.alxarafe.app.domain.model.GreetingRepository;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.jdbc.core.RowMapper;

import java.sql.ResultSet;
import java.sql.SQLException;
import java.time.Instant;
import java.util.List;
import java.util.Optional;

public class JpaGreetingRepository implements GreetingRepository {

    private final JdbcTemplate jdbc;

    public JpaGreetingRepository(JdbcTemplate jdbc) {
        this.jdbc = jdbc;
    }

    @Override
    public void save(Greeting greeting) {
        jdbc.update(
                "INSERT INTO greetings (id, message, created_at) VALUES (?, ?, ?)",
                greeting.getId(),
                greeting.getMessage(),
                java.sql.Timestamp.from(greeting.getCreatedAt())
        );
    }

    @Override
    public Optional<Greeting> findById(String id) {
        var results = jdbc.query(
                "SELECT id, message, created_at FROM greetings WHERE id = ?",
                new GreetingRowMapper(),
                id
        );
        return results.isEmpty() ? Optional.empty() : Optional.of(results.getFirst());
    }

    @Override
    public List<Greeting> findAll() {
        return jdbc.query(
                "SELECT id, message, created_at FROM greetings ORDER BY created_at DESC",
                new GreetingRowMapper()
        );
    }

    private static class GreetingRowMapper implements RowMapper<Greeting> {
        @Override
        public Greeting mapRow(ResultSet rs, int rowNum) throws SQLException {
            return new Greeting(
                    rs.getString("id"),
                    rs.getString("message"),
                    rs.getTimestamp("created_at").toInstant()
            );
        }
    }
}
