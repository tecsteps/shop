<?php

namespace App\Enums;

enum ProductVariantStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
}
