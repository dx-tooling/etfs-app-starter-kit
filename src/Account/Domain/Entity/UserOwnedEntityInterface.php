<?php

namespace App\Account\Domain\Entity;


interface UserOwnedEntityInterface
{
    public function getId(): ?string;

    public function getUser(): User;
}
