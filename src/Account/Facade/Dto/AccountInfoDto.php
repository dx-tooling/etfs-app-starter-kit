<?php

declare(strict_types=1);

namespace App\Account\Facade\Dto;

use DateTimeImmutable;

final readonly class AccountInfoDto
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public string            $id,
        public string            $email,
        public array             $roles,
        public DateTimeImmutable $createdAt,
        public ?string           $currentlyActiveOrganizationId = null
    ) {
    }

    public function getDisplayName(): string
    {
        return $this->email;
    }
}
