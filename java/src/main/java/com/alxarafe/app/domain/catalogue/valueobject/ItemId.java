package com.alxarafe.app.domain.catalogue.valueobject;

import com.alxarafe.app.domain.shared.UuidV7Id;

import java.security.SecureRandom;
import java.util.UUID;

public final class ItemId extends UuidV7Id {
    private static final SecureRandom RANDOM = new SecureRandom();

    public ItemId(String value) {
        super(value);
    }

    public static ItemId generate() {
        long high = (System.currentTimeMillis() << 16) | 0x7000L | RANDOM.nextInt(0x1000);
        long low = (RANDOM.nextLong() & 0x3fffffffffffffffL) | 0x8000000000000000L;
        return new ItemId(new UUID(high, low).toString());
    }
}