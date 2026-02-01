<?php

declare(strict_types=1);

namespace App\Account\Domain\Entity;

use App\Account\Domain\Enum\Role;
use App\Account\Infrastructure\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(
    fields: ['email'],
    message: 'There is already an account with this email'
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->createdAt = DateAndTimeService::getDateTimeImmutable();
        $this->roles     = [Role::USER->value];
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(
        type: Types::GUID,
        unique: true
    )]
    private ?string $id = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    #[ORM\Column(
        type: Types::DATETIME_IMMUTABLE,
        nullable: true
    )]
    private ?DateTimeImmutable $createdAt;

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\Column(
        type: Types::GUID,
        unique: false,
        nullable: true,
    )]
    private ?string $currentlyActiveOrganizationId = null;

    public function getCurrentlyActiveOrganizationsId(): ?string
    {
        return $this->currentlyActiveOrganizationId;
    }

    public function setCurrentlyActiveOrganizationsId(?string $currentlyActiveOrganizationId): void
    {
        $this->currentlyActiveOrganizationId = $currentlyActiveOrganizationId;
    }

    #[ORM\Column(
        type: Types::STRING,
        length: 180,
        unique: true,
        nullable: false
    )]
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = trim(mb_strtolower($email));
    }

    #[ORM\Column(
        type: Types::STRING,
        length: 255,
        nullable: true
    )]
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name !== null ? trim($name) : null;
    }

    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    public function getRoles(): array
    {
        $roles   = $this->roles;
        $roles[] = Role::USER->value;

        return array_unique($roles);
    }

    public function hasRole(Role $role): bool
    {
        return in_array(
            strtoupper($role->value),
            $this->getRoles(),
            true
        );
    }

    public function addRole(Role $role): void
    {
        $role = $role->value;
        $role = strtoupper($role);

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
    }

    public function removeRole(Role $roleToRemove): void
    {
        $remainingRoles = [];
        foreach ($this->roles as $role) {
            if ($role !== $roleToRemove->value) {
                $remainingRoles[] = $role;
            }
        }
        $this->roles = $remainingRoles;
    }

    #[ORM\Column(type: Types::STRING)]
    private string $password;

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    public function getUserIdentifier(): string
    {
        if (is_null($this->email)) {
            return $this->id;
        }

        return $this->email;
    }

    public function eraseCredentials(): void
    {
    }
}
