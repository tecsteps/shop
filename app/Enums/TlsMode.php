<?php

namespace App\Enums;

enum TlsMode: string
{
    case Managed = 'managed';
    case BringYourOwn = 'bring_your_own';
}
