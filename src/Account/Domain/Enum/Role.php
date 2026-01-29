<?php

namespace App\Account\Domain\Enum;

enum Role: string
{
    case USER  = 'ROLE_USER';
    case ADMIN = 'ROLE_ADMIN';
}
