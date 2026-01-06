<?php

namespace App\Organization\Domain\Entity;

interface OrganizationOwnedEntityInterface
{
    public function getId(): ?string;

    public function getOrganization(): Organization;
}
