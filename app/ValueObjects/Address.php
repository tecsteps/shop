<?php

namespace App\ValueObjects;

readonly class Address
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $company = null,
        public ?string $address1 = null,
        public ?string $address2 = null,
        public ?string $city = null,
        public ?string $province = null,
        public ?string $provinceCode = null,
        public ?string $country = null,
        public ?string $countryCode = null,
        public ?string $postalCode = null,
        public ?string $phone = null
    ) {}

    /**
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

    /**
     * @param  array<string, string|null>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            company: $data['company'] ?? null,
            address1: $data['address1'] ?? null,
            address2: $data['address2'] ?? null,
            city: $data['city'] ?? null,
            province: $data['province'] ?? null,
            provinceCode: $data['province_code'] ?? null,
            country: $data['country'] ?? null,
            countryCode: $data['country_code'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            phone: $data['phone'] ?? null,
        );
    }
}
