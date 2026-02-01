<?php

declare(strict_types=1);

namespace App\Organization\Facade;

use App\Organization\Domain\Service\OrganizationDomainServiceInterface;
use App\Shared\Facade\Enum\Iso639_1Code;

readonly class OrganizationFacade implements OrganizationFacadeInterface
{
    public function __construct(
        private OrganizationDomainServiceInterface $organizationDomainService
    ) {
    }

    public function getOrganizationNameById(
        string  $organizationId,
        ?string $localeCode = null
    ): ?string {
        $organization = $this->organizationDomainService->getOrganizationById($organizationId);

        if ($organization === null) {
            return null;
        }

        $iso639_1Code = null;
        if ($localeCode !== null) {
            $iso639_1Code = Iso639_1Code::tryFrom($localeCode);
        }

        return $this->organizationDomainService->getOrganizationName($organization, $iso639_1Code);
    }
}
