<?php

namespace App\ValueObjects;

/**
 * A structured address used for zone matching, tax calculation and storage
 * in checkouts.shipping_address_json / billing_address_json.
 */
readonly class Address
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public ?string $company,
        public string $address1,
        public ?string $address2,
        public string $city,
        public ?string $province,
        public ?string $provinceCode,
        public string $country,
        public string $countryCode,
        public string $postalCode,
        public ?string $phone,
    ) {}

    /**
     * Build from a plain array using the snake_case JSON representation.
     * `country_code` falls back to `country` and vice versa.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            firstName: (string) ($data['first_name'] ?? ''),
            lastName: (string) ($data['last_name'] ?? ''),
            company: $data['company'] ?? null,
            address1: (string) ($data['address1'] ?? ''),
            address2: $data['address2'] ?? null,
            city: (string) ($data['city'] ?? ''),
            province: $data['province'] ?? null,
            provinceCode: $data['province_code'] ?? null,
            country: (string) ($data['country'] ?? $data['country_code'] ?? ''),
            countryCode: (string) ($data['country_code'] ?? $data['country'] ?? ''),
            postalCode: (string) ($data['postal_code'] ?? ''),
            phone: $data['phone'] ?? null,
        );
    }

    /**
     * Serialize to the snake_case JSON representation stored on checkouts.
     *
     * @return array<string, string|null>
     */
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
            'country' => $this->country,
            'country_code' => $this->countryCode,
            'postal_code' => $this->postalCode,
            'phone' => $this->phone,
        ];
    }
}
