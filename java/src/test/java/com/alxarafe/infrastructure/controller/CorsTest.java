package com.alxarafe.infrastructure.controller;

import com.alxarafe.app.HexagonalApplication;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.autoconfigure.web.servlet.AutoConfigureMockMvc;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.test.web.servlet.MockMvc;

import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.options;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.header;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

@SpringBootTest(classes = HexagonalApplication.class)
@AutoConfigureMockMvc
class CorsTest {

    @Autowired
    private MockMvc mockMvc;

    private static final String VITE_ORIGIN = "http://localhost:5173";

    @Test
    void apiAllowsViteOrigin() throws Exception {
        mockMvc.perform(get("/api/health").header("Origin", VITE_ORIGIN))
                .andExpect(status().isOk())
                .andExpect(header().string("Access-Control-Allow-Origin", VITE_ORIGIN));
    }

    @Test
    void apiAnswersCorsPreflight() throws Exception {
        mockMvc.perform(options("/api/item-families")
                        .header("Origin", VITE_ORIGIN)
                        .header("Access-Control-Request-Method", "POST"))
                .andExpect(status().isOk())
                .andExpect(header().string("Access-Control-Allow-Origin", VITE_ORIGIN))
                .andExpect(header().string("Access-Control-Allow-Methods", org.hamcrest.Matchers.containsString("POST")));
    }
}