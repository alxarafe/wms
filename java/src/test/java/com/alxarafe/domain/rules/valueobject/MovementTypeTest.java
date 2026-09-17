package com.alxarafe.domain.rules.valueobject;

import com.alxarafe.app.domain.rules.valueobject.MovementType;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class MovementTypeTest {

    @Test
    void values() {
        assertEquals(MovementType.INBOUND, MovementType.valueOf("INBOUND"));
        assertEquals(MovementType.OUTBOUND, MovementType.valueOf("OUTBOUND"));
        assertEquals(MovementType.TRANSFER, MovementType.valueOf("TRANSFER"));
        assertEquals(MovementType.ADJUSTMENT, MovementType.valueOf("ADJUSTMENT"));
        assertEquals(4, MovementType.values().length);
    }
}
