<?php

declare(strict_types=1);

namespace App\Common\Presentation\Service;

use EnterpriseToolingForSymfony\WebuiBundle\Entity\MainNavigationEntry;
use EnterpriseToolingForSymfony\WebuiBundle\Service\AbstractMainNavigationService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use ValueError;

readonly class MainNavigationPresentationService extends AbstractMainNavigationService
{
    private RouterInterface $routerInstance;

    public function __construct(
        RouterInterface               $router,
        RequestStack                  $requestStack,
        private ParameterBagInterface $parameterBag,
        private Security              $security,
    ) {
        $symfonyEnvironment = $this->parameterBag->get('kernel.environment');

        if (!is_string($symfonyEnvironment)) {
            throw new ValueError('Symfony environment is not a string.');
        }

        $this->routerInstance = $router;

        parent::__construct(
            $router,
            $requestStack,
            $symfonyEnvironment
        );
    }

    public function secondaryMainNavigationIsPartOfDropdown(): bool
    {
        // Keep secondary nav in the header (not in dropdown) so auth links are always visible
        return false;
    }

    public function getPrimaryMainNavigationTitle(): string
    {
        return 'Main';
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
        return 'Account';
    }

    /**
     * Secondary navigation appears in the header (right side)
     * Contains auth-related links that should be easily accessible.
     *
     * @return MainNavigationEntry[]
     */
    protected function getSecondaryMainNavigationEntries(): array
    {
        $entries = [];

        if ($this->security->getUser() === null) {
            // User is not logged in - show sign in and sign up
            $entries[] = $this->generateEntry(
                'Sign In',
                'account.presentation.sign_in',
            );
            $entries[] = $this->generateEntry(
                'Sign Up',
                'account.presentation.sign_up',
            );
        } else {
            // User is logged in - show dashboard and sign out
            $entries[] = $this->generateEntry(
                'Dashboard',
                'account.presentation.dashboard',
            );
            $entries[] = new MainNavigationEntry(
                'Sign Out',
                $this->routerInstance->generate('account.presentation.sign_out'),
                '',
                false
            );
        }

        return $entries;
    }

    public function getFinalSecondaryMainNavigationEntries(): array
    {
        return $this->getSecondaryMainNavigationEntries();
    }

    public function getTertiaryMainNavigationTitle(): string
    {
        return 'More';
    }

    /**
     * Tertiary navigation appears in the dropdown menu.
     * Contains less frequently used links.
     *
     * @return MainNavigationEntry[]
     */
    public function getTertiaryMainNavigationEntries(): array
    {
        return [
            $this->generateEntry(
                'About',
                'content.presentation.about',
            )
        ];
    }

    public function getBrandLogoHtml(): string
    {
        return '<strong>EtfsAppStarterKit</strong>';
    }
}
