<?php

declare(strict_types=1);

namespace App\Common\Presentation\Service;

use EnterpriseToolingForSymfony\WebuiBundle\Entity\MainNavigationEntry;
use EnterpriseToolingForSymfony\WebuiBundle\Service\AbstractMainNavigationService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use ValueError;

readonly class MainNavigationPresentationService extends AbstractMainNavigationService
{
    public function __construct(
        RouterInterface               $router,
        RequestStack                  $requestStack,
        private ParameterBagInterface $parameterBag,
    ) {
        $symfonyEnvironment = $this->parameterBag->get('kernel.environment');

        if (!is_string($symfonyEnvironment)) {
            throw new ValueError('Symfony environment is not a string.');
        }

        parent::__construct(
            $router,
            $requestStack,
            $symfonyEnvironment
        );
    }

    public function getDropdownTitle(): string
    {
        return 'Menu';
    }

    public function getDropdownSvgIcon(): string
    {
        return '
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/>
            <path d="M12 10m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/>
            <path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855"/>';
    }

    public function secondaryMainNavigationIsPartOfDropdown(): bool
    {
        return false;
    }

    public function getPrimaryMainNavigationTitle(): string
    {
        return 'Primary';
    }

    /**
     * @return MainNavigationEntry[]
     */
    public function getPrimaryMainNavigationEntries(): array
    {
        $entries = [
            $this->generateEntry(
                'Home',
                'content.presentation.homepage',
            )
        ];

        return $entries;
    }

    public function getSecondaryMainNavigationTitle(): string
    {
        return 'Secondary';
    }

    /**
     * @return MainNavigationEntry[]
     */
    protected function getSecondaryMainNavigationEntries(): array
    {
        $entries = [
            $this->generateEntry(
                'Home 2',
                'content.presentation.homepage',
            )
        ];

        return $entries;
    }

    public function getFinalSecondaryMainNavigationEntries(): array
    {
        return $this->getSecondaryMainNavigationEntries();
    }

    public function getTertiaryMainNavigationTitle(): string
    {
        return 'Tertiary';
    }

    /**
     * @return MainNavigationEntry[]
     */
    public function getTertiaryMainNavigationEntries(): array
    {
        $entries = [
            $this->generateEntry(
                'Living Styleguide',
                'webui.living_styleguide.show',
            )
        ];

        return $entries;
    }

    public function getBrandLogoHtml(): string
    {
        return '<strong>AppStarterKit</strong>';
    }
}
