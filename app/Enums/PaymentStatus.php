<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Captured = 'captured';
    case Failed = 'failed';
    case Refunded = 'refunded';
}
