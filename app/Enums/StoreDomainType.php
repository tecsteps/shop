<?php

namespace App\Enums;

enum StoreDomainType: string
{
    case Storefront = 'storefront';
    case Admin = 'admin';
    case Api = 'api';
}
