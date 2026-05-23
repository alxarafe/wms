package com.alxarafe.app.infrastructure.config;

import com.alxarafe.app.application.usecase.CreateGreetingUseCase;
import com.alxarafe.app.application.usecase.GetGreetingUseCase;
import com.alxarafe.app.domain.model.GreetingRepository;
import com.alxarafe.app.infrastructure.persistence.InMemoryGreetingRepository;
import com.alxarafe.app.infrastructure.persistence.JpaGreetingRepository;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.boot.autoconfigure.condition.ConditionalOnMissingBean;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.jdbc.core.JdbcTemplate;

@Configuration
public class AppConfig {

    private static final Logger log = LoggerFactory.getLogger(AppConfig.class);

    @Bean
    public GreetingRepository greetingRepository(JdbcTemplate jdbc) {
        try {
            jdbc.queryForObject("SELECT 1 FROM greetings LIMIT 1", Integer.class);
            log.info("Database available — using JpaGreetingRepository");
            return new JpaGreetingRepository(jdbc);
        } catch (Exception e) {
            log.warn("Database not available — using InMemoryGreetingRepository: {}", e.getMessage());
            return new InMemoryGreetingRepository();
        }
    }

    @Bean
    public CreateGreetingUseCase createGreetingUseCase(GreetingRepository repository) {
        return new CreateGreetingUseCase(repository);
    }

    @Bean
    public GetGreetingUseCase getGreetingUseCase(GreetingRepository repository) {
        return new GetGreetingUseCase(repository);
    }
}
