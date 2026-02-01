<?php

declare(strict_types=1);

namespace App\Common\Presentation\Service;

use EnterpriseToolingForSymfony\WebuiBundle\Entity\MainNavigationEntry;
use EnterpriseToolingForSymfony\WebuiBundle\Service\AbstractMainNavigationService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use ValueError;

readonly class MainNavigationPresentationService extends AbstractMainNavigationService
{
    public function __construct(
        RouterInterface               $router,
        RequestStack                  $requestStack,
        private ParameterBagInterface $parameterBag,
        private Security              $security,
        private TranslatorInterface   $translator,
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

    public function secondaryMainNavigationIsPartOfDropdown(): bool
    {
        return true;
    }

    public function getPrimaryMainNavigationTitle(): string
    {
        return $this->translator->trans('navigation.primary_title', [], 'messages');
    }

    /**
     * @return list<MainNavigationEntry>
     */
    public function getPrimaryMainNavigationEntries(): array
    {
        $entries = [];

        if (!$this->security->isGranted('ROLE_USER')) {
            $entries = [
                $this->generateEntry(
                    $this->translator->trans('navigation.entries.sign_in', [], 'messages'),
                    'account.presentation.sign_in',
                ),
                $this->generateEntry(
                    $this->translator->trans('navigation.entries.sign_up', [], 'messages'),
                    'account.presentation.sign_up',
                ),
            ];
        }

        if ($this->security->isGranted('ROLE_USER')) {
            $entries[] = $this->generateEntry(
                $this->translator->trans('navigation.entries.your_account', [], 'messages'),
                'account.presentation.dashboard',
            );
            $entries[] = $this->generateEntry(
                $this->translator->trans('navigation.entries.organization', [], 'messages'),
                'organization.presentation.dashboard',
            );
        }

        $entries[] = $this->generateEntry(
            $this->translator->trans('navigation.entries.home', [], 'messages'),
            'content.presentation.homepage',
        );

        return $entries;
    }

    public function getSecondaryMainNavigationTitle(): string
    {
        return $this->translator->trans('navigation.secondary_title', [], 'messages');
    }

    /**
     * @return list<MainNavigationEntry>
     */
    protected function getSecondaryMainNavigationEntries(): array
    {
        return [];
    }

    /**
     * @return list<MainNavigationEntry>
     */
    public function getFinalSecondaryMainNavigationEntries(): array
    {
        return $this->getSecondaryMainNavigationEntries();
    }

    public function getTertiaryMainNavigationTitle(): string
    {
        return $this->translator->trans('navigation.tertiary_title', [], 'messages');
    }

    /**
     * @return list<MainNavigationEntry>
     */
    public function getTertiaryMainNavigationEntries(): array
    {
        $entries = [
            $this->generateEntry(
                $this->translator->trans('navigation.entries.living_styleguide', [], 'messages'),
                'webui.living_styleguide.show',
            ),
        ];

        return $entries;
    }

    public function getBrandLogoHtml(): string
    {
        return '<strong>' . $this->translator->trans('navigation.brand', [], 'messages') . '</strong>';
    }
}
