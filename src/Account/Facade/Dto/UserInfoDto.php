<?php

namespace App\Account\Facade\Dto;

use DateTimeImmutable;

final readonly class UserInfoDto
{
    public function __construct(
        public string            $id,
        public string            $email,
        public ?string           $name,
        public DateTimeImmutable $createdAt
    ) {
    }

    public function getDisplayName(): string
    {
        return $this->name ?? $this->email;
    }
}
