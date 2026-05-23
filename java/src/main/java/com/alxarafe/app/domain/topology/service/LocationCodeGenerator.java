package com.alxarafe.app.domain.topology.service;

import com.alxarafe.app.domain.topology.valueobject.LocationCode;
import com.alxarafe.app.domain.topology.valueobject.NamingPolicy;

/**
 * Domain Service that generates composite location codes
 * according to a zone's NamingPolicy.
 *
 * Code format: {warehouse}{sep}{zone}{sep}{aisle}{sep}{bay}{sep}{level}
 * Each segment is zero-padded to the width defined in the policy.
 */
public final class LocationCodeGenerator {

    /**
     * Generates a location code from its component parts.
     *
     * @param policy    the naming policy from the zone
     * @param warehouse the warehouse code segment
     * @param zone      the zone code segment
     * @param aisle     the aisle code segment
     * @param bay       the bay number
     * @param level     the level number
     * @return the generated LocationCode
     */
    public LocationCode generate(NamingPolicy policy, String warehouse, String zone,
                                 String aisle, int bay, int level) {
        String sep = policy.codeSeparator();

        String code = padLeft(warehouse, policy.warehousePadding())
                + sep + padLeft(zone, policy.zonePadding())
                + sep + padLeft(aisle, policy.aislePadding())
                + sep + padLeft(String.valueOf(bay), policy.bayPadding())
                + sep + padLeft(String.valueOf(level), policy.levelPadding());

        return new LocationCode(code);
    }

    private static String padLeft(String input, int width) {
        if (input.length() >= width) {
            return input;
        }
        return "0".repeat(width - input.length()) + input;
    }
}
