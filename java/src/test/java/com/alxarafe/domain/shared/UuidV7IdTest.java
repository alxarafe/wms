package com.alxarafe.domain.shared;

import com.alxarafe.app.domain.shared.UuidV7Id;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class UuidV7IdTest {

    @Test
    void validUuidV7() {
        var id = new TestUuidV7Id("018e4e3a-3e7b-7b3e-8000-000000000001");
        assertEquals("018e4e3a-3e7b-7b3e-8000-000000000001", id.value());
    }

    @Test
    void nullValue() {
        assertThrows(NullPointerException.class, () -> new TestUuidV7Id(null));
    }

    @Test
    void emptyString() {
        assertThrows(IllegalArgumentException.class, () -> new TestUuidV7Id(""));
    }

    @Test
    void blankString() {
        assertThrows(IllegalArgumentException.class, () -> new TestUuidV7Id("   "));
    }

    @Test
    void invalidUuidFormat() {
        assertThrows(IllegalArgumentException.class, () -> new TestUuidV7Id("not-a-uuid"));
    }

    @Test
    void nonV7Uuid() {
        assertThrows(IllegalArgumentException.class, () -> new TestUuidV7Id("018e4e3a-3e7b-4b3e-8000-000000000001"));
    }

    @Test
    void uppercaseUuid() {
        var id = new TestUuidV7Id("018E4E3A-3E7B-7B3E-8000-000000000001");
        assertEquals("018E4E3A-3E7B-7B3E-8000-000000000001", id.value());
    }

    @Test
    void equalsSameValue() {
        var a = new TestUuidV7Id("018e4e3a-3e7b-7b3e-8000-000000000001");
        var b = new TestUuidV7Id("018e4e3a-3e7b-7b3e-8000-000000000001");
        assertEquals(a, b);
    }

    @Test
    void equalsDifferentClass() {
        var a = new TestUuidV7Id("018e4e3a-3e7b-7b3e-8000-000000000001");
        var b = new OtherTestUuidV7Id("018e4e3a-3e7b-7b3e-8000-000000000001");
        assertNotEquals(a, b);
    }

    @Test
    void equalsDifferentValue() {
        var a = new TestUuidV7Id("018e4e3a-3e7b-7b3e-8000-000000000001");
        var b = new TestUuidV7Id("018e4e3a-3e7b-7b3e-8000-000000000002");
        assertNotEquals(a, b);
    }

    @Test
    void hashCodeConsistentWithEquals() {
        var a = new TestUuidV7Id("018e4e3a-3e7b-7b3e-8000-000000000001");
        var b = new TestUuidV7Id("018e4e3a-3e7b-7b3e-8000-000000000001");
        assertEquals(a.hashCode(), b.hashCode());
    }

    @Test
    void testToString() {
        var uuid = "018e4e3a-3e7b-7b3e-8000-000000000001";
        var id = new TestUuidV7Id(uuid);
        assertEquals(uuid, id.toString());
    }
}

class TestUuidV7Id extends UuidV7Id {
    public TestUuidV7Id(String value) {
        super(value);
    }
}

class OtherTestUuidV7Id extends UuidV7Id {
    public OtherTestUuidV7Id(String value) {
        super(value);
    }
}
