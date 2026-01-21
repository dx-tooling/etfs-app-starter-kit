<?php

declare(strict_types=1);

namespace App\Registration\Presentation\Component;

use App\Account\Domain\Entity\User;
use App\Account\Facade\AccountFacadeInterface;
use App\Account\Facade\Dto\UserCreationDto;
use App\Organization\Domain\Entity\Organization;
use App\Shared\Domain\ValueObject\EmailAddress;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Throwable;

#[AsLiveComponent(
    name: 'registration|presentation|register_flow',
    template: '@registration.presentation/components/register_flow.component.html.twig'
)]
class RegisterFlowComponent
{
    use DefaultActionTrait;

    private const int STEP_EMAIL_PASSWORD = 1;
    private const int STEP_NAME           = 2;
    private const int STEP_ORGANIZATION   = 3;
    private const int TOTAL_STEPS         = 3;

    #[LiveProp]
    public int $currentStep = self::STEP_EMAIL_PASSWORD;

    #[LiveProp(writable: true)]
    #[Assert\NotBlank(message: 'Please enter your email address.')]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    public string $email = '';

    #[LiveProp(writable: true)]
    #[Assert\NotBlank(message: 'Please enter a password.')]
    #[Assert\Length(min: 8, minMessage: 'Password must be at least {{ limit }} characters.')]
    public string $password = '';

    #[LiveProp(writable: true)]
    #[Assert\NotBlank(message: 'Please enter your name.')]
    public string $name = '';

    #[LiveProp(writable: true)]
    #[Assert\NotBlank(message: 'Please enter your organization name.')]
    public string $organizationName = '';

    #[LiveProp]
    public ?string $errorMessage = null;

    #[LiveProp]
    public bool $isComplete = false;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly Security                    $security,
        private readonly EntityManagerInterface      $entityManager,
        private readonly AccountFacadeInterface      $accountFacade
    ) {
    }

    #[LiveAction]
    public function nextStep(): void
    {
        $this->errorMessage = null;

        if (!$this->validateCurrentStep()) {
            return;
        }

        if ($this->currentStep < self::TOTAL_STEPS) {
            ++$this->currentStep;
        }
    }

    #[LiveAction]
    public function previousStep(): void
    {
        $this->errorMessage = null;

        if ($this->currentStep > self::STEP_EMAIL_PASSWORD) {
            --$this->currentStep;
        }
    }

    #[LiveAction]
    public function submit(): void
    {
        $this->errorMessage = null;

        if (!$this->validateCurrentStep()) {
            return;
        }

        try {
            $emailAddress = EmailAddress::fromString($this->email);

            $dto = new UserCreationDto(
                $emailAddress,
                $this->password
            );

            $result = $this->accountFacade->createRegisteredUser($dto);

            if ($result->isSuccess) {
                $this->isComplete = true;

                return;
            }

            $this->errorMessage = $result->errorMessage;
        } catch (InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        } catch (Throwable) {
            $this->errorMessage = 'An error occurred during registration. Please try again.';
        }
    }

    public function getProgressPercentage(): int
    {
        return (int) (($this->currentStep / self::TOTAL_STEPS) * 100);
    }

    public function isFirstStep(): bool
    {
        return $this->currentStep === self::STEP_EMAIL_PASSWORD;
    }

    public function isLastStep(): bool
    {
        return $this->currentStep === self::TOTAL_STEPS;
    }

    private function validateCurrentStep(): bool
    {
        return match ($this->currentStep) {
            self::STEP_EMAIL_PASSWORD => $this->validateEmailPasswordStep(),
            self::STEP_NAME           => $this->validateNameStep(),
            self::STEP_ORGANIZATION   => $this->validateOrganizationStep(),
            default                   => false,
        };
    }

    private function validateEmailPasswordStep(): bool
    {
        if (trim($this->email) === '') {
            $this->errorMessage = 'Please enter your email address.';

            return false;
        }

        try {
            $emailAddress = EmailAddress::fromString($this->email);
        } catch (InvalidArgumentException) {
            $this->errorMessage = 'Please enter a valid email address.';

            return false;
        }

        $existingUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => (string) $emailAddress]);

        if ($existingUser !== null) {
            $this->errorMessage = 'An account with this email address already exists.';

            return false;
        }

        if (trim($this->password) === '') {
            $this->errorMessage = 'Please enter a password.';

            return false;
        }

        if (mb_strlen($this->password) < 8) {
            $this->errorMessage = 'Password must be at least 8 characters.';

            return false;
        }

        return true;
    }

    private function validateNameStep(): bool
    {
        if (trim($this->name) === '') {
            $this->errorMessage = 'Please enter your name.';

            return false;
        }

        return true;
    }

    private function validateOrganizationStep(): bool
    {
        $trimmedName = trim($this->organizationName);

        if ($trimmedName === '') {
            $this->errorMessage = 'Please enter your organization name.';

            return false;
        }

        $existingOrganization = $this->entityManager
            ->getRepository(Organization::class)
            ->findOneBy(['name' => $trimmedName]);

        if ($existingOrganization !== null) {
            $this->errorMessage = 'An organization with this name already exists.';

            return false;
        }

        return true;
    }
}
