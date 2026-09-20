package com.alxarafe.app.domain.inventory.valueobject;

import com.alxarafe.app.domain.shared.UuidV7Id;

import java.security.SecureRandom;
import java.util.UUID;

public final class HandlingUnitId extends UuidV7Id {
    private static final SecureRandom RANDOM = new SecureRandom();

    public HandlingUnitId(String value) {
        super(value);
    }

    public static HandlingUnitId generate() {
        long high = (System.currentTimeMillis() << 16) | 0x7000L | RANDOM.nextInt(0x1000);
        long low = (RANDOM.nextLong() & 0x3fffffffffffffffL) | 0x8000000000000000L;
        return new HandlingUnitId(new UUID(high, low).toString());
    }
}
