<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class VariantOptionValue extends Pivot
{
    protected $table = 'variant_option_values';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'variant_id',
        'product_option_value_id',
    ];
}
