<?php

namespace App\Enums;

enum NavigationItemType: string
{
    case Link = 'link';
    case Page = 'page';
    case Collection = 'collection';
    case Product = 'product';
}
