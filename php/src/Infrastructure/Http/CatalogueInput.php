<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Http;

use InvalidArgumentException;
use JsonException;
use stdClass;

final readonly class CatalogueInput
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
            throw new InvalidArgumentException('Unknown catalogue fields.');
        }
        return new self($fields);
    }

    public function string(string $key): string
    {
        $value = $this->fields[$key] ?? null;
        if (!is_string($value)) {
            throw new InvalidArgumentException("Expected string: $key.");
        }
        return $value;
    }

    public function nullableString(string $key): ?string
    {
        return ($this->fields[$key] ?? null) === null ? null : $this->string($key);
    }

    /** @return list<string> */
    public function strings(string $key): array
    {
        $value = $this->fields[$key] ?? null;
        if (!is_array($value) || !array_is_list($value)) {
            throw new InvalidArgumentException("Expected list: $key.");
        }
        foreach ($value as $item) {
            if (!is_string($item)) {
                throw new InvalidArgumentException("Expected strings in: $key.");
            }
        }
        return $value;
    }
}
