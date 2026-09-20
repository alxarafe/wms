<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Inventory\ValueObject;

use InvalidArgumentException;

/**
 * Quantity stored internally as an integer scaled by 10^-SCALE to avoid
 * floating point drift (decisión M5).
 *
 * Solo se usan números de coma flotante en la frontera HTTP
 * (Quantity::fromDecimal), que redondea HALF_UP a la escala definida.
 * La aritmética y las lecturas de base de datos trabajan siempre con
 * enteros exactos (fromScaled/fromDecimalString).
 *
 * La unidad no interviene en la aritmética: se admite cualquier unidad,
 * que debe coincidir entre las cantidades que se combinen.
 */
final readonly class Quantity
{
    public const int SCALE = 6;
    private const int SCALE_FACTOR = 1_000_000;

    private function __construct(
        private int $scaledUnits,
        private string $unit,
    ) {
        if ($scaledUnits <= 0) {
            throw new InvalidArgumentException(
                'Quantity must be strictly positive.'
            );
        }
        if ($unit === '') {
            throw new InvalidArgumentException('Quantity unit cannot be empty.');
        }
    }

    /**
     * Crea la cantidad desde un valor decimal (frontera JSON, redondeo HALF_UP).
     */
    public static function fromDecimal(int|float $value, string $unit): self
    {
        if (is_float($value) && !is_finite($value)) {
            throw new InvalidArgumentException('Quantity must be a finite number.');
        }
        if ($value <= 0) {
            throw new InvalidArgumentException(
                'Quantity must be strictly positive. Got: ' . $value
            );
        }
        $scaled = (int) round($value * self::SCALE_FACTOR, 0, PHP_ROUND_HALF_UP);
        return new self($scaled, $unit);
    }

    /**
     * Crea la cantidad desde entero ya escalado (exacto, sin coma flotante).
     */
    public static function fromScaled(int $scaledUnits, string $unit): self
    {
        return new self($scaledUnits, $unit);
    }

    /**
     * Crea la cantidad desde un NUMERIC(18,6) de PostgreSQL ('30.500000').
     */
    public static function fromDecimalString(string $decimal, string $unit): self
    {
        if (preg_match('/^(\d+)(?:\.(\d+))?$/', $decimal, $matches) !== 1) {
            throw new InvalidArgumentException('Invalid decimal quantity: ' . $decimal);
        }
        $whole = (int) $matches[1];
        $fraction = str_pad(substr($matches[2] ?? '', 0, self::SCALE), self::SCALE, '0');
        return new self($whole * self::SCALE_FACTOR + (int) $fraction, $unit);
    }

    public function scaledUnits(): int
    {
        return $this->scaledUnits;
    }

    public function unit(): string
    {
        return $this->unit;
    }

    public function value(): float
    {
        return $this->scaledUnits / self::SCALE_FACTOR;
    }

    /**
     * Representación decimal exacta tipo NUMERIC(15,6) para escritura en BD.
     */
    public function toDecimalString(): string
    {
        return sprintf(
            '%d.%06d',
            intdiv($this->scaledUnits, self::SCALE_FACTOR),
            $this->scaledUnits % self::SCALE_FACTOR
        );
    }

    public function equals(self $other): bool
    {
        return $this->scaledUnits === $other->scaledUnits
            && $this->unit === $other->unit;
    }

    public function add(self $other): self
    {
        $this->assertSameUnit($other);
        return new self($this->scaledUnits + $other->scaledUnits, $this->unit);
    }

    public function subtract(self $other): self
    {
        $this->assertSameUnit($other);
        if ($this->scaledUnits < $other->scaledUnits) {
            throw new InvalidArgumentException(
                "Insufficient quantity for subtraction. Has: {$this->value()}, wants: {$other->value()}"
            );
        }
        return new self($this->scaledUnits - $other->scaledUnits, $this->unit);
    }

    private function assertSameUnit(self $other): void
    {
        if ($this->unit !== $other->unit) {
            throw new InvalidArgumentException(
                "Cannot combine quantities with different units: {$this->unit} vs {$other->unit}"
            );
        }
    }
}
