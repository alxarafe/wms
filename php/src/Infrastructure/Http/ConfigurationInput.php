<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Http;

use Alxarafe\App\Domain\Configuration\WarehouseFormat;
use InvalidArgumentException;
use JsonException;
use stdClass;

final readonly class ConfigurationInput
{
    /** @param array<string, mixed> $fields */
    private function __construct(private array $fields)
    {
    }

    /** @param list<string> $allowed */
    public static function parse(string $body, array $allowed): self
    {
        $object = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        if (!$object instanceof stdClass) {
            throw new JsonException('Expected a JSON object.');
        }
        $fields = get_object_vars($object);
        if (array_diff(array_keys($fields), $allowed) !== []) {
            throw new InvalidArgumentException('Unknown configuration fields.');
        }
        return new self($fields);
    }

    public function string(string $key, ?string $default = null): string
    {
        $value = array_key_exists($key, $this->fields) ? $this->fields[$key] : $default;
        if (!is_string($value)) {
            throw new InvalidArgumentException("Expected string: $key.");
        }
        return $value;
    }

    public function boolean(string $key, bool $default = false): bool
    {
        $value = array_key_exists($key, $this->fields) ? $this->fields[$key] : $default;
        if (!is_bool($value)) {
            throw new InvalidArgumentException("Expected boolean: $key.");
        }
        return $value;
    }

    public function integer(string $key): int
    {
        $value = $this->fields[$key] ?? null;
        if (!is_int($value)) {
            throw new InvalidArgumentException("Expected integer: $key.");
        }
        return $value;
    }

    public function optionalInteger(string $key): ?int
    {
        return ($this->fields[$key] ?? null) === null ? null : $this->integer($key);
    }

    public function format(): WarehouseFormat
    {
        return new WarehouseFormat(
            $this->integer('aisle_digits'),
            $this->integer('bay_digits'),
            $this->integer('level_digits'),
            $this->boolean('uses_zones'),
            $this->string('separator', '.'),
            $this->boolean('include_zone_in_code'),
        );
    }
}
