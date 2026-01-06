<?php

namespace App\Account\Domain\Entity;

use App\Account\Domain\Enum\Role;
use App\Account\Infrastructure\Repository\UserRepository;
use App\Organization\Domain\Entity\Organization;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use ValueError;

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
        $this->createdAt           = DateAndTimeService::getDateTimeImmutable();
        $this->ownedOrganizations  = new ArrayCollection();
        $this->joinedOrganizations = new ArrayCollection();
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

    /** @var Organization[] */
    #[ORM\OneToMany(
        targetEntity: Organization::class,
        mappedBy: 'owningUser',
        cascade: ['persist']
    )]
    private array|Collection $ownedOrganizations;

    public function getOwnedOrganizations(): array|Collection
    {
        return $this->ownedOrganizations;
    }

    /**
     * @throws Exception
     */
    public function addOwnedOrganization(
        Organization $organization
    ): void {
        foreach ($this->ownedOrganizations as $ownedOrganization) {
            if ($ownedOrganization->getId() === $organization->getId()) {
                throw new ValueError(
                    "Organization '{$organization->getId()}' already in list of owned organizations."
                );
            }
        }
        $this->ownedOrganizations->add($organization);
    }

    #[ORM\ManyToOne(
        targetEntity: Organization::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'currently_active_organizations_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'CASCADE'
    )]
    private ?Organization $currentlyActiveOrganization = null;

    public function getCurrentlyActiveOrganization(): Organization
    {
        return $this->currentlyActiveOrganization;
    }

    public function setCurrentlyActiveOrganization(
        Organization $organization
    ): void {
        foreach ($this->ownedOrganizations as $ownedOrganization) {
            if ($ownedOrganization->getId() === $organization->getId()) {
                $this->currentlyActiveOrganization = $organization;

                return;
            }
        }

        foreach ($this->joinedOrganizations as $joinedOrganization) {
            if ($joinedOrganization->getId() === $organization->getId()) {
                $this->currentlyActiveOrganization = $organization;

                return;
            }
        }

        throw new ValueError(
            "Cannot set organization '{$organization->getId()}' as currently active because it is neither owned nor joined."
        );
    }

    public function ownsCurrentlyActiveOrganization(): bool
    {
        return $this->getId() === $this->currentlyActiveOrganization->getOwningUser()->getId();
    }

    /**
     * @var Collection|Organization[]
     */
    #[ORM\JoinTable(name: 'users_organizations')]
    #[ORM\JoinColumn(
        name: 'users_id',
        referencedColumnName: 'id',
        unique: false
    )]
    #[ORM\InverseJoinColumn(
        name: 'organizations_id',
        referencedColumnName: 'id',
        unique: false
    )]
    #[ORM\ManyToMany(targetEntity: Organization::class, inversedBy: 'joinedUsers')]
    private array|Collection $joinedOrganizations;

    /**
     * @return Collection|Organization[]
     */
    public function getJoinedOrganizations(): Collection|array
    {
        return $this->joinedOrganizations;
    }

    /**
     * @throws Exception
     */
    public function addJoinedOrganization(
        Organization $organization
    ): void {
        foreach ($this->joinedOrganizations as $joinedOrganization) {
            if ($joinedOrganization->getId() === $organization->getId()) {
                throw new ValueError(
                    "Organization '{$organization->getId()}' already in list of joined organizations."
                );
            }
        }

        $this->joinedOrganizations->add($organization);
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

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isVerified = false;

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): void
    {
        $this->isVerified = $isVerified;
    }

    public function isRegistered(): bool
    {
        return $this->hasRole(Role::REGISTERED_USER);
    }

    public function isUnregistered(): bool
    {
        return $this->hasRole(Role::UNREGISTERED_USER);
    }

    public function isExtensionOnly(): bool
    {
        return $this->hasRole(Role::EXTENSION_ONLY_USER);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    #[ORM\OneToOne(
        mappedBy: 'user',
        targetEntity: ThirdPartyAuthLinkedinResourceOwner::class,
        cascade: ['persist']
    )]
    private ?ThirdPartyAuthLinkedinResourceOwner $thirdPartyAuthLinkedinResourceOwner = null;

    public function getThirdPartyAuthLinkedinResourceOwner(): ?ThirdPartyAuthLinkedinResourceOwner
    {
        return $this->thirdPartyAuthLinkedinResourceOwner;
    }

    #[ORM\Column(
        type: Types::STRING,
        length: 2,
        nullable: true,
        enumType: Iso639_1Code::class
    )]
    private ?Iso639_1Code $uiLanguageCode;

    public function getUiLanguageCode(): ?Iso639_1Code
    {
        return $this->uiLanguageCode;
    }

    public function setUiLanguageCode(
        ?Iso639_1Code $iso639_1Code
    ): void {
        $this->uiLanguageCode = $iso639_1Code;
    }

    #[ORM\Column(
        type: Types::STRING,
        length: 128,
        nullable: true
    )]
    private ?string $uiTimezone;

    public function getUiTimezone(): ?string
    {
        return $this->uiTimezone;
    }

    public function setUiTimezone(
        ?string $uiTimezone
    ): void {
        $this->uiTimezone = $uiTimezone;
    }

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $hasSkippedLingoSyncPrerequisitesInfo = false;

    public function hasSkippedLingoSyncPrerequisitesInfo(): bool
    {
        return $this->hasSkippedLingoSyncPrerequisitesInfo;
    }

    public function setHasSkippedLingoSyncPrerequisitesInfo(bool $hasSkippedLingoSyncPrerequisitesInfo): void
    {
        $this->hasSkippedLingoSyncPrerequisitesInfo = $hasSkippedLingoSyncPrerequisitesInfo;
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

    public function getFirstName(): ?string
    {
        if (!is_null($this->getThirdPartyAuthLinkedinResourceOwner())) {
            return $this->getThirdPartyAuthLinkedinResourceOwner()
                ->getFirstName();
        }

        return null;
    }

    public function getLastName(): ?string
    {
        if (!is_null($this->getThirdPartyAuthLinkedinResourceOwner())) {
            return $this->getThirdPartyAuthLinkedinResourceOwner()
                ->getLastName();
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function getNameOrEmail(): string
    {
        $nameOrEmail = $this->getFirstName();
        if (is_null($nameOrEmail)) {
            $nameOrEmail = $this->getLastName();
        } else {
            if (!is_null($this->getLastName())) {
                $nameOrEmail .= ' ' . $this->getLastName();
            }

            return $nameOrEmail;
        }

        if (is_null($nameOrEmail)) {
            if (is_null($this->getEmail())) {
                throw new Exception("No nameOrEmail for user '{$this->getId()}' because firstname, lastname, and email are all NULL.");
            }

            return $this->getEmail();
        } else {
            return $nameOrEmail;
        }
    }

    public function hasProfilePhoto(): bool
    {
        if (!is_null($this->getThirdPartyAuthLinkedinResourceOwner())
            && !is_null(
                $this->getThirdPartyAuthLinkedinResourceOwner()
                    ->getSortedProfilePicture800Url()
            )
        ) {
            return true;
        }

        return false;
    }

    public function getProfilePhotoUrl(): ?string
    {
        if ($this->hasProfilePhoto()) {
            return $this->getThirdPartyAuthLinkedinResourceOwner()
                ->getSortedProfilePicture800Url();
        }

        return null;
    }

    public function getProfilePhotoContentType(): ?string
    {
        if ($this->hasProfilePhoto()) {
            return $this->getThirdPartyAuthLinkedinResourceOwner()
                ->getSortedProfilePicture800ContentType();
        }

        return null;
    }
}
