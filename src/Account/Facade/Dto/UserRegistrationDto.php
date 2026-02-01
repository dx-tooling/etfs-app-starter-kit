<?php

declare(strict_types=1);

namespace App\Account\Facade\Dto;

use App\Shared\Facade\ValueObject\EmailAddress;

final class UserRegistrationDto
{
    public function __construct(
        public EmailAddress $emailAddress,
        public ?string      $plainPassword = null
    ) {
    }
}
