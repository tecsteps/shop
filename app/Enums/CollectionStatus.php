<?php

namespace App\Enums;

enum CollectionStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
