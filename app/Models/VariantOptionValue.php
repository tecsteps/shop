<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class VariantOptionValue extends Pivot
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'variant_option_values';

    protected $fillable = ['variant_id', 'product_option_value_id'];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function optionValue(): BelongsTo
    {
        return $this->belongsTo(ProductOptionValue::class, 'product_option_value_id');
    }
}
