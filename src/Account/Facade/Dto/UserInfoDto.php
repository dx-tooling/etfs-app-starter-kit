<?php

declare(strict_types=1);

namespace App\Account\Facade\Dto;

use DateTimeImmutable;

final readonly class UserInfoDto
{
    public function __construct(
        public string            $id,
        public string            $email,
        public ?string           $name,
        public DateTimeImmutable $createdAt,
        public ?string           $currentlyActiveOrganizationId = null
    ) {
    }

    public function getDisplayName(): string
    {
        return $this->name ?? $this->email;
    }
}
