<?php

namespace App\Enums;

enum ProductMediaStatus: string
{
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
}
