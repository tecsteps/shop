<?php

namespace App\Enums;

enum StoreDomainTlsMode: string
{
    case Managed = 'managed';
    case BringYourOwn = 'bring_your_own';
}
