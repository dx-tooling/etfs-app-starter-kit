<?php

declare(strict_types=1);

namespace App\Registration\Presentation\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route(
        path: '/register',
        name: 'registration.presentation.register'
    )]
    public function register(): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('content.presentation.homepage');
        }

        return $this->render('@registration.presentation/register.html.twig');
    }
}
