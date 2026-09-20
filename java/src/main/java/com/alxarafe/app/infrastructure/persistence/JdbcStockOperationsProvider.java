package com.alxarafe.app.infrastructure.persistence;

import com.alxarafe.app.application.movement.StockOperationException;
import com.alxarafe.app.application.movement.StockOperationsProvider;
import com.alxarafe.app.domain.inventory.valueobject.HandlingUnitId;
import com.alxarafe.app.domain.inventory.valueobject.Quantity;
import com.alxarafe.app.domain.inventory.valueobject.Sscc;
import com.alxarafe.app.domain.inventory.valueobject.StockQuantId;
import com.alxarafe.app.domain.rules.valueobject.StockMovementId;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Repository;
import org.springframework.transaction.annotation.Transactional;

import java.sql.ResultSet;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

/**
 * JDBC implementation of the stock operations.
 *
 * <ul>
 *   <li>Receipt (INBOUND): creates an HU (generated SSCC) with one stock_quant
 *       and records the movement from NULL to the target location.</li>
 *   <li>Issue (OUTBOUND): only accepts consuming the full stock of the
 *       location (full deoccupation). Deletes the stock_quant, detaches the HU
 *       (location_id = NULL, kept as a historical record because the immutable
 *       ledger references it) and records the return movement.</li>
 * </ul>
 */
@Repository
public class JdbcStockOperationsProvider implements StockOperationsProvider {

    private final JdbcTemplate jdbc;

    public JdbcStockOperationsProvider(JdbcTemplate jdbc) {
        this.jdbc = jdbc;
    }

    @Override
    @Transactional
    public Map<String, Object> receive(String locationId, String itemCode,
                                       Quantity quantity, String batchCode) {
        Map<String, Object> location = findLocation(locationId);
        if (location == null) {
            throw new StockOperationException("Location not found.", 404);
        }
        if (Boolean.TRUE.equals(location.get("aisle_blocked"))
                || !"ACTIVE".equals(location.get("status"))) {
            throw new StockOperationException("Location is not available.", 409);
        }
        if (handlingUnitAt(locationId).isPresent()) {
            throw new StockOperationException("Location is already occupied.", 409);
        }

        Map<String, Object> item = findItemBySku(itemCode);
        if (item == null) {
            throw new StockOperationException("Item not found.", 404);
        }
        String batchId = resolveBatch(item, batchCode);

        String huId = HandlingUnitId.generate().value();
        String quantId = StockQuantId.generate().value();
        String movementId = StockMovementId.generate().value();
        String sscc = Sscc.generate().value();

        jdbc.update("INSERT INTO handling_unit (id, code, location_id, parent_hu_id, status) "
                        + "VALUES (?, ?, ?, NULL, 'AVAILABLE')",
                huId, sscc, locationId);
        jdbc.update("INSERT INTO stock_quant (id, hu_id, item_id, batch_id, quantity, unit) "
                        + "VALUES (?, ?, ?, ?, ?, ?)",
                quantId, huId, item.get("id"), batchId,
                new java.math.BigDecimal(quantity.toDecimalString()), quantity.unit());
        jdbc.update("INSERT INTO stock_movement (id, type, hu_id, from_location_id, to_location_id) "
                        + "VALUES (?, 'INBOUND', ?, NULL, ?)",
                movementId, huId, locationId);

        return locationView(locationId);
    }

    @Override
    @Transactional
    public Map<String, Object> issue(String locationId, String itemCode, Quantity quantity) {
        Map<String, Object> location = findLocation(locationId);
        if (location == null) {
            throw new StockOperationException("Location not found.", 404);
        }
        String huId = handlingUnitAt(locationId).orElseThrow(
                () -> new StockOperationException("Location has no stock to issue.", 409));

        Map<String, Object> quant = findQuant(huId, itemCode);
        if (quant == null) {
            throw new StockOperationException("Item " + itemCode + " is not stored in this location.", 409);
        }
        Quantity stored = Quantity.fromDecimalString((String) quant.get("quantity"), (String) quant.get("unit"));
        if (!quantity.unit().equals(stored.unit())) {
            throw new StockOperationException(
                    "Cannot issue quantity in unit " + quantity.unit()
                            + ": stored stock is in unit " + stored.unit() + ".", 400);
        }
        if (!quantity.equals(stored)) {
            throw new StockOperationException(
                    "Issue quantity does not match the stored stock ("
                            + stored.toDecimalString() + " " + stored.unit() + ").", 409);
        }

        jdbc.update("DELETE FROM stock_quant WHERE id = ?", quant.get("id"));
        jdbc.update("UPDATE handling_unit SET location_id = NULL WHERE id = ?", huId);
        jdbc.update("INSERT INTO stock_movement (id, type, hu_id, from_location_id, to_location_id) "
                        + "VALUES (?, 'OUTBOUND', ?, ?, NULL)",
                StockMovementId.generate().value(), huId, locationId);

        return locationView(locationId);
    }

    private String resolveBatch(Map<String, Object> item, String batchCode) {
        boolean batchManaged = Boolean.TRUE.equals(item.get("is_batch_managed"));
        String itemId = (String) item.get("id");
        if (batchManaged && (batchCode == null || batchCode.isBlank())) {
            throw new StockOperationException("Batch code is required for item " + itemId + ".", 400);
        }
        if (!batchManaged && batchCode != null && !batchCode.isBlank()) {
            throw new StockOperationException("Item " + itemId + " is not batch managed.", 400);
        }
        if (batchCode == null || batchCode.isBlank()) {
            return null;
        }
        List<String> ids = jdbc.query("SELECT id FROM batch WHERE item_id = ? AND batch_code = ?",
                (result, row) -> result.getString("id"), itemId, batchCode);
        return ids.stream().findFirst().orElseThrow(
                () -> new StockOperationException("Batch not found for item " + itemId + ".", 404));
    }

    private Map<String, Object> findLocation(String locationId) {
        List<Map<String, Object>> rows = jdbc.query(
                "SELECT l.id, l.status, a.is_blocked AS aisle_blocked "
                        + "FROM location l JOIN aisle a ON a.id = l.aisle_id WHERE l.id = ?",
                this::rowToMap, locationId);
        return rows.isEmpty() ? null : rows.get(0);
    }

    private java.util.Optional<String> handlingUnitAt(String locationId) {
        List<String> ids = jdbc.query(
                "SELECT id FROM handling_unit WHERE location_id = ? AND parent_hu_id IS NULL",
                (result, row) -> result.getString("id"), locationId);
        return ids.stream().findFirst();
    }

    private Map<String, Object> findItemBySku(String sku) {
        List<Map<String, Object>> rows = jdbc.query(
                "SELECT id, is_batch_managed FROM item WHERE sku = ?", this::rowToMap, sku);
        return rows.isEmpty() ? null : rows.get(0);
    }

    private Map<String, Object> findQuant(String huId, String itemCode) {
        List<Map<String, Object>> rows = jdbc.query(
                "SELECT sq.id, sq.quantity, sq.unit FROM stock_quant sq "
                        + "JOIN item i ON i.id = sq.item_id WHERE sq.hu_id = ? AND i.sku = ?",
                (result, row) -> {
                    Map<String, Object> map = new LinkedHashMap<>();
                    map.put("id", result.getString("id"));
                    map.put("quantity", result.getBigDecimal("quantity").toPlainString());
                    map.put("unit", result.getString("unit"));
                    return map;
                }, huId, itemCode);
        return rows.isEmpty() ? null : rows.get(0);
    }

    private Map<String, Object> rowToMap(ResultSet result, int row) throws java.sql.SQLException {
        Map<String, Object> map = new LinkedHashMap<>();
        java.sql.ResultSetMetaData metadata = result.getMetaData();
        for (int i = 1; i <= metadata.getColumnCount(); i++) {
            String column = metadata.getColumnName(i);
            Object value = result.getObject(column);
            if (value instanceof java.math.BigDecimal decimal) {
                value = decimal.toPlainString();
            }
            map.put(column, value);
        }
        return map;
    }

    private Map<String, Object> locationView(String locationId) {
        List<Map<String, Object>> rows = jdbc.query(
                """
                SELECT
                    l.id AS location_id, l.code AS location_code, l.bay, l.level, l.role, l.status,
                    a.is_blocked AS aisle_blocked,
                    hu.code AS hu_code,
                    i.id AS item_id, i.sku AS item_code, i.name AS name,
                    sq.quantity, sq.unit,
                    b.batch_code
                FROM location l
                JOIN aisle a ON a.id = l.aisle_id
                LEFT JOIN handling_unit hu ON hu.location_id = l.id AND hu.parent_hu_id IS NULL
                LEFT JOIN stock_quant sq ON sq.hu_id = hu.id
                LEFT JOIN item i ON i.id = sq.item_id
                LEFT JOIN batch b ON b.id = sq.batch_id
                WHERE l.id = ?
                """,
                this::rowToMap, locationId);

        if (rows.isEmpty()) {
            throw new StockOperationException("Location not found.", 404);
        }
        Map<String, Object> first = rows.get(0);

        List<Map<String, Object>> references = new ArrayList<>();
        for (Map<String, Object> row : rows) {
            if (row.get("hu_code") == null || row.get("item_code") == null) {
                continue;
            }
            Map<String, Object> reference = new LinkedHashMap<>();
            reference.put("itemId", row.get("item_id"));
            reference.put("itemCode", row.get("item_code"));
            reference.put("name", row.get("name"));
            reference.put("quantity", Double.valueOf((String) row.get("quantity")));
            reference.put("unit", row.get("unit"));
            reference.put("batchCode", row.get("batch_code"));
            reference.put("huCode", row.get("hu_code"));
            references.add(reference);
        }

        Map<String, Object> view = new LinkedHashMap<>();
        view.put("id", first.get("location_id"));
        view.put("code", first.get("location_code"));
        view.put("bay", first.get("bay"));
        view.put("level", first.get("level"));
        view.put("role", first.get("role"));
        view.put("status", first.get("status"));
        boolean blocked = Boolean.TRUE.equals(first.get("aisle_blocked")) || !"ACTIVE".equals(first.get("status"));
        view.put("blocked", blocked);
        view.put("references", references);
        return view;
    }
}