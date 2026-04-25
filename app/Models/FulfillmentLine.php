<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FulfillmentLine extends Model
{
    public $timestamps = false;

    protected $fillable = ['fulfillment_id', 'order_line_id', 'quantity'];
}

