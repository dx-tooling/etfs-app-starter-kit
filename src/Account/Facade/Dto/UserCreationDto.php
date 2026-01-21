<?php

namespace App\Account\Facade\Dto;

use App\Shared\Domain\ValueObject\EmailAddress;

final class UserCreationDto
{
    public function __construct(
        public EmailAddress $emailAddress,
        public ?string      $plainPassword = null,
        public bool         $isVerified = false
    ) {
    }
}
