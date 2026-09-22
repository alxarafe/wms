<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Configuration;

use Alxarafe\App\Domain\Configuration\HandlingUnitType;
use Alxarafe\App\Domain\Configuration\LocationType;
use Alxarafe\App\Domain\Configuration\LocationTypeHuPolicy;
use Alxarafe\App\Domain\Configuration\WarehouseConfiguration;
use Alxarafe\App\Domain\Configuration\WarehouseFormat;
use Alxarafe\App\Domain\Configuration\WarehouseFormatLocked;
use Alxarafe\App\Domain\Shared\ValueObject\Uuid;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConfigurationTest extends TestCase
{
    public function testConfigurableNamesHaveNoSpecialMeaning(): void
    {
        foreach (['PALLET', 'BOX', 'CUSTOM'] as $code) {
            self::assertSame($code, (new HandlingUnitType(Uuid::generate(), $code, 'Tipo'))->code);
        }
        $warehouse = new WarehouseConfiguration(Uuid::generate(), '01', 'Almacén', new WarehouseFormat(1, 2, 1));
        foreach (['PICKING', 'RESERVE', 'RETURNS', 'OTHER'] as $code) {
            $type = new LocationType(Uuid::generate(), $warehouse->id, $code, 'Tipo');
            self::assertNull($type->maxLocationsPerItem);
            self::assertFalse($type->allowsMultiSku);
        }
    }

    public function testPolicyInvariantsAcrossEveryFlagCombination(): void
    {
        for ($flags = 0; $flags < 16; $flags++) {
            $full = (bool) ($flags & 1);
            $partial = (bool) ($flags & 2);
            $breakdown = (bool) ($flags & 4);
            $dispatch = (bool) ($flags & 8);
            $valid = ($full || $partial) && (!$dispatch || $full);
            try {
                $policy = new LocationTypeHuPolicy(Uuid::generate(), Uuid::generate(), $full, $partial, $breakdown, $dispatch);
                self::assertTrue($valid, "Combination $flags should fail");
                self::assertSame($dispatch, $policy->allowsFullDispatch);
            } catch (InvalidArgumentException) {
                self::assertFalse($valid, "Combination $flags should pass");
            }
        }
    }

    /** @return iterable<string, array{int, int, int, bool, string, bool}> */
    public static function invalidFormats(): iterable
    {
        yield 'zero' => [0, 2, 1, false, '.', false];
        yield 'too wide' => [1, 10, 1, false, '.', false];
        yield 'negative' => [1, 2, -1, false, '.', false];
        yield 'empty separator' => [1, 2, 1, false, '', false];
        yield 'long separator' => [1, 2, 1, false, '..', false];
        yield 'blank separator' => [1, 2, 1, false, ' ', false];
        yield 'zone without mode' => [1, 2, 1, false, '.', true];
    }

    #[DataProvider('invalidFormats')]
    public function testInvalidFormat(int $aisle, int $bay, int $level, bool $zones, string $separator, bool $include): void
    {
        $this->expectException(InvalidArgumentException::class);
        new WarehouseFormat($aisle, $bay, $level, $zones, $separator, $include);
    }

    public function testUnchangedFormatIsAllowedAfterFirstAisle(): void
    {
        $format = new WarehouseFormat(1, 2, 1);
        $format->assertChangeAllowed(new WarehouseFormat(1, 2, 1), true);
        $format->assertChangeAllowed(new WarehouseFormat(2, 3, 2), false);
        $this->expectException(WarehouseFormatLocked::class);
        $format->assertChangeAllowed(new WarehouseFormat(2, 3, 2), true);
    }

    public function testCodeLengthUsesCharactersAndDoesNotNormalizeLiteralCodes(): void
    {
        $warehouse = new WarehouseConfiguration(Uuid::generate(), 'áááááááááá', 'Almacén', new WarehouseFormat(1, 2, 1));
        self::assertSame('áááááááááá', $warehouse->code);
        $this->expectException(InvalidArgumentException::class);
        new HandlingUnitType(Uuid::generate(), '  ', 'Tipo');
    }
}
