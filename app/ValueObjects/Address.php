<?php

namespace App\ValueObjects;

use JsonSerializable;

final readonly class Address implements JsonSerializable
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $address1,
        public string $city,
        public string $countryCode,
        public string $postalCode,
        public ?string $company = null,
        public ?string $address2 = null,
        public ?string $province = null,
        public ?string $provinceCode = null,
        public ?string $phone = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            firstName: (string) ($data['first_name'] ?? ''),
            lastName: (string) ($data['last_name'] ?? ''),
            address1: (string) ($data['address1'] ?? ''),
            city: (string) ($data['city'] ?? ''),
            countryCode: strtoupper((string) ($data['country_code'] ?? $data['country'] ?? '')),
            postalCode: (string) ($data['postal_code'] ?? $data['zip'] ?? ''),
            company: self::nullableString($data['company'] ?? null),
            address2: self::nullableString($data['address2'] ?? null),
            province: self::nullableString($data['province'] ?? null),
            provinceCode: self::nullableString($data['province_code'] ?? null),
            phone: self::nullableString($data['phone'] ?? null),
        );
    }

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'company' => $this->company,
            'address1' => $this->address1,
            'address2' => $this->address2,
            'city' => $this->city,
            'province' => $this->province,
            'province_code' => $this->provinceCode,
            'country' => $this->countryCode,
            'country_code' => $this->countryCode,
            'postal_code' => $this->postalCode,
            'zip' => $this->postalCode,
            'phone' => $this->phone,
        ];
    }

    /** @return array<string, string|null> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private static function nullableString(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }
}
