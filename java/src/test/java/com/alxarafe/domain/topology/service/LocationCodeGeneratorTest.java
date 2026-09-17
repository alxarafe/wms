package com.alxarafe.domain.topology.service;

import com.alxarafe.app.domain.topology.service.LocationCodeGenerator;
import com.alxarafe.app.domain.topology.valueobject.NamingPolicy;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class LocationCodeGeneratorTest {

    private LocationCodeGenerator generator;

    @BeforeEach
    void setUp() {
        generator = new LocationCodeGenerator();
    }

    @Test
    void generateWithDashSeparator() {
        var policy = new NamingPolicy("-", 2, 2, 2, 3, 2);
        var code = generator.generate(policy, "WH", "Z", "A", 1, 1);
        assertEquals("WH-0Z-0A-001-01", code.value());
    }

    @Test
    void generateWithDotSeparator() {
        var policy = new NamingPolicy(".", 3, 2, 3, 2, 1);
        var code = generator.generate(policy, "WH", "Z", "A", 1, 1);
        assertEquals("0WH.0Z.00A.01.1", code.value());
    }

    @Test
    void generateWithLargeNumbers() {
        var policy = new NamingPolicy("-", 2, 2, 2, 3, 2);
        var code = generator.generate(policy, "WH", "Z", "A", 999, 99);
        assertEquals("WH-0Z-0A-999-99", code.value());
    }

    @Test
    void generateWithNoSeparator() {
        var policy = new NamingPolicy("_", 1, 1, 1, 1, 1);
        var code = generator.generate(policy, "W", "Z", "A", 1, 1);
        assertEquals("W_Z_A_1_1", code.value());
    }
}
