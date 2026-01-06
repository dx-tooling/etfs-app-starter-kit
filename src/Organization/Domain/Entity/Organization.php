<?php

namespace App\Organization\Domain\Entity;

use App\Account\Domain\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use ValueError;

#[ORM\Entity]
#[ORM\Table(name: 'organizations')]
class Organization
{
    public function __construct(
        User $owningUser
    ) {
        $this->owningUser  = $owningUser;
        $this->joinedUsers = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(
        type: Types::GUID,
        unique: true
    )]
    private ?string $id = null;

    /**
     * @throws Exception
     */
    public function getId(): string
    {
        return (string)$this->id;
    }

    #[ORM\ManyToOne(
        targetEntity: User::class,
        cascade: ['persist'],
        inversedBy: 'ownedOrganizations'
    )]
    #[ORM\JoinColumn(
        name: 'owning_users_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private readonly User $owningUser;

    public function getOwningUser(): User
    {
        return $this->owningUser;
    }

    /**
     * @var Collection|User[]
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'joinedOrganizations')]
    private array|Collection $joinedUsers;

    /**
     * @return Collection|User[]
     */
    public function getJoinedUsers(): Collection|array
    {
        return $this->joinedUsers;
    }

    public function addJoinedUser(
        User $user
    ): void {
        foreach ($this->joinedUsers as $joinedUser) {
            if ($joinedUser->getId() === $user->getId()) {
                throw new ValueError(
                    "User '{$user->getId()}' already in list of joined users."
                );
            }
        }

        $this->joinedUsers->add($user);
    }

    #[ORM\Column(
        type: Types::STRING,
        length: 256,
        unique: false,
        nullable: true
    )]
    private ?string $name;

    public function setName(?string $name): void
    {
        $this->name = mb_substr(trim($name), 0, 256);
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
