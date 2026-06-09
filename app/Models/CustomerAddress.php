<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerAddressFactory> */
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'label',
        'address_json',
        'is_default',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'address_json' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Map the stored address JSON (spec 01 shape, "zip" key) to the checkout
     * shipping address shape ("postal_code" key) used by the address form.
     *
     * @return array<string, string>
     */
    public function toCheckoutAddress(): array
    {
        $address = $this->address_json ?? [];

        return array_filter([
            'first_name' => (string) ($address['first_name'] ?? ''),
            'last_name' => (string) ($address['last_name'] ?? ''),
            'address1' => (string) ($address['address1'] ?? ''),
            'address2' => (string) ($address['address2'] ?? ''),
            'city' => (string) ($address['city'] ?? ''),
            'province' => (string) ($address['province'] ?? ''),
            'postal_code' => (string) ($address['zip'] ?? ''),
            'country_code' => (string) ($address['country_code'] ?? ''),
            'phone' => (string) ($address['phone'] ?? ''),
        ], fn (string $value): bool => $value !== '');
    }

    /**
     * One-line summary for saved-address pickers, e.g.
     * "Jane Doe, Musterstrasse 1, 10115 Berlin".
     */
    public function summaryLine(): string
    {
        $address = $this->address_json ?? [];

        $name = trim(($address['first_name'] ?? '').' '.($address['last_name'] ?? ''));
        $cityLine = trim(($address['zip'] ?? '').' '.($address['city'] ?? ''));

        return implode(', ', array_filter([$name, $address['address1'] ?? '', $cityLine]));
    }
}
