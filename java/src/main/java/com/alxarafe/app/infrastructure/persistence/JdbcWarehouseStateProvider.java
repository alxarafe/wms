package com.alxarafe.app.infrastructure.persistence;

import com.alxarafe.app.application.state.WarehouseStateProvider;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Repository;

import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

/**
 * Builds the warehouse state view for the viewer by reading the topology
 * (warehouse/zone/aisle/location) plus the handling units and stock quants
 * assigned to each location.
 */
@Repository
public class JdbcWarehouseStateProvider implements WarehouseStateProvider {

    private static final String LOCATIONS_QUERY = """
            SELECT
                l.id AS location_id, l.code AS location_code, l.bay, l.level, l.role, l.status,
                a.id AS aisle_id, a.code AS aisle_code, a.is_blocked AS aisle_blocked,
                z.id AS zone_id, z.code AS zone_code,
                zt.code AS zone_type_code, zt.allows_multi_sku, zt.is_operative,
                hu.code AS hu_code,
                i.id AS item_id, i.sku AS item_code, i.name AS name,
                sq.quantity, sq.unit,
                b.batch_code
            FROM location l
            JOIN aisle a ON a.id = l.aisle_id
            JOIN zone z ON z.id = a.zone_id
            JOIN zone_type zt ON zt.id = z.zone_type_id
            LEFT JOIN handling_unit hu ON hu.location_id = l.id AND hu.parent_hu_id IS NULL
            LEFT JOIN stock_quant sq ON sq.hu_id = hu.id
            LEFT JOIN item i ON i.id = sq.item_id
            LEFT JOIN batch b ON b.id = sq.batch_id
            WHERE z.warehouse_id = ?
            ORDER BY z.code, a.code, l.level DESC, l.bay
            """;

    private final JdbcTemplate jdbc;

    public JdbcWarehouseStateProvider(JdbcTemplate jdbc) {
        this.jdbc = jdbc;
    }

    @Override
    public Map<String, Object> stateFor(String warehouseId) {
        Map<String, Object> warehouse = fetchWarehouse(warehouseId);
        if (warehouse == null) {
            return null;
        }

        Map<String, Map<String, Object>> zones = new LinkedHashMap<>();
        Map<String, Map<String, Object>> aisles = new LinkedHashMap<>();
        Map<String, Map<String, Object>> locations = new LinkedHashMap<>();

        jdbc.query(LOCATIONS_QUERY, (result, row) -> {
            String zoneId = result.getString("zone_id");
            String zoneCode = result.getString("zone_code");
            String zoneTypeCode = result.getString("zone_type_code");
            boolean allowsMultiSku = result.getBoolean("allows_multi_sku");
            boolean isOperative = result.getBoolean("is_operative");
            String aisleId = result.getString("aisle_id");
            String aisleCode = result.getString("aisle_code");
            boolean aisleBlocked = result.getBoolean("aisle_blocked");
            String locationId = result.getString("location_id");
            String locationCode = result.getString("location_code");
            int bay = result.getInt("bay");
            int level = result.getInt("level");
            String role = result.getString("role");
            String status = result.getString("status");

            Map<String, Object> location = locations.computeIfAbsent(locationId, key -> {
                Map<String, Object> loc = new LinkedHashMap<>();
                loc.put("id", locationId);
                loc.put("code", locationCode);
                loc.put("bay", bay);
                loc.put("level", level);
                loc.put("role", role);
                loc.put("status", status);
                boolean blocked = aisleBlocked || !"ACTIVE".equals(status);
                loc.put("blocked", blocked);
                loc.put("references", new ArrayList<>());
                return loc;
            });

            Map<String, Object> aisle = aisles.computeIfAbsent(aisleId, key -> {
                Map<String, Object> ais = new LinkedHashMap<>();
                ais.put("id", aisleId);
                ais.put("code", aisleCode);
                ais.put("zoneId", zoneId);
                ais.put("bays", 0);
                ais.put("levels", 0);
                ais.put("isBlocked", aisleBlocked);
                ais.put("locations", new ArrayList<>());
                return ais;
            });
            aisle.put("bays", Math.max((int) aisle.get("bays"), bay));
            aisle.put("levels", Math.max((int) aisle.get("levels"), level));
            @SuppressWarnings("unchecked")
            List<Map<String, Object>> aisleLocations = (List<Map<String, Object>>) aisle.get("locations");
            if (!aisleLocations.contains(location)) {
                aisleLocations.add(location);
            }

            Map<String, Object> zone = zones.computeIfAbsent(zoneId, key -> {
                Map<String, Object> zon = new LinkedHashMap<>();
                zon.put("id", zoneId);
                zon.put("code", zoneCode);
                zon.put("zoneTypeCode", zoneTypeCode);
                zon.put("allowsMultiSku", allowsMultiSku);
                zon.put("isOperative", isOperative);
                zon.put("aisles", new ArrayList<>());
                return zon;
            });
            @SuppressWarnings("unchecked")
            List<Map<String, Object>> zoneAisles = (List<Map<String, Object>>) zone.get("aisles");
            if (!zoneAisles.contains(aisle)) {
                zoneAisles.add(aisle);
            }

            String huCode = result.getString("hu_code");
            if (huCode != null && result.getString("item_code") != null) {
                @SuppressWarnings("unchecked")
                List<Map<String, Object>> references = (List<Map<String, Object>>) location.get("references");
                Map<String, Object> reference = new LinkedHashMap<>();
                reference.put("itemId", result.getString("item_id"));
                reference.put("itemCode", result.getString("item_code"));
                reference.put("name", result.getString("name"));
                reference.put("quantity", result.getBigDecimal("quantity").doubleValue());
                reference.put("unit", result.getString("unit"));
                reference.put("batchCode", result.getString("batch_code"));
                reference.put("huCode", huCode);
                references.add(reference);
            }
            return null;
        }, warehouseId);

        Map<String, Object> state = new LinkedHashMap<>();
        state.put("id", warehouse.get("id"));
        state.put("code", warehouse.get("code"));
        state.put("name", warehouse.get("name"));
        state.put("zones", new ArrayList<>(zones.values()));
        return state;
    }

    private Map<String, Object> fetchWarehouse(String warehouseId) {
        List<Map<String, Object>> rows = jdbc.query(
                "SELECT id, code, name FROM warehouse WHERE id = ?",
                (result, row) -> {
                    Map<String, Object> warehouse = new LinkedHashMap<>();
                    warehouse.put("id", result.getString("id"));
                    warehouse.put("code", result.getString("code"));
                    warehouse.put("name", result.getString("name"));
                    return warehouse;
                }, warehouseId);
        return rows.isEmpty() ? null : rows.get(0);
    }
}