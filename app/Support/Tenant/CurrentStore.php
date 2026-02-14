<?php

namespace App\Support\Tenant;

use InvalidArgumentException;

final readonly class CurrentStore
{
    public function __construct(
        public int $id,
        public string $status,
        public ?string $handle = null,
        public ?string $name = null,
    ) {}

    /**
     * @param  array<string, mixed>|object  $record
     */
    public static function fromRecord(array|object $record): self
    {
        $data = is_array($record) ? $record : (array) $record;

        if (! array_key_exists('id', $data)) {
            throw new InvalidArgumentException('Store record must contain an id.');
        }

        $idValue = $data['id'];

        if (! is_int($idValue) && ! is_string($idValue) && ! is_float($idValue)) {
            throw new InvalidArgumentException('Store record id must be numeric.');
        }

        $status = is_string($data['status'] ?? null) ? $data['status'] : 'active';
        $handle = self::normalizeNullableString($data['handle'] ?? null);
        $name = self::normalizeNullableString($data['name'] ?? null);

        return new self(
            id: (int) $idValue,
            status: $status,
            handle: $handle,
            name: $name,
        );
    }

    private static function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * @return array{id: int, status: string, handle: string|null, name: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'handle' => $this->handle,
            'name' => $this->name,
        ];
    }
}
