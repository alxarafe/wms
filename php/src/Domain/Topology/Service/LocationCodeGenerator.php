<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Topology\Service;

use Alxarafe\App\Domain\Topology\Entity\Location;
use Alxarafe\App\Domain\Topology\ValueObject\LocationCode;
use Alxarafe\App\Domain\Topology\ValueObject\NamingPolicy;

/**
 * Domain Service that generates composite location codes
 * according to a zone's NamingPolicy.
 *
 * Code format: {warehouse}{sep}{zone}{sep}{aisle}{sep}{bay}{sep}{level}
 * Each segment is zero-padded to the width defined in the policy.
 */
final class LocationCodeGenerator
{
    /**
     * Generates a location code from its component parts.
     *
     * @param NamingPolicy $policy    The naming policy from the zone
     * @param string       $warehouse The warehouse code segment
     * @param string       $zone      The zone code segment
     * @param string       $aisle     The aisle code segment
     * @param int          $bay       The bay number
     * @param int          $level     The level number
     */
    public function generate(
        NamingPolicy $policy,
        string $warehouse,
        string $zone,
        string $aisle,
        int $bay,
        int $level,
    ): LocationCode {
        $sep = $policy->codeSeparator();

        $code = str_pad($warehouse, $policy->warehousePadding(), '0', STR_PAD_LEFT)
            . $sep . str_pad($zone, $policy->zonePadding(), '0', STR_PAD_LEFT)
            . $sep . str_pad($aisle, $policy->aislePadding(), '0', STR_PAD_LEFT)
            . $sep . str_pad((string) $bay, $policy->bayPadding(), '0', STR_PAD_LEFT)
            . $sep . str_pad((string) $level, $policy->levelPadding(), '0', STR_PAD_LEFT);

        return new LocationCode($code);
    }
}
