<?php

declare(strict_types=1);

namespace App\Registration\Presentation\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route(
        path: '/registration',
        name: 'registration.presentation.index'
    )]
    public function index(): Response
    {
        return $this->render('registration/index.html.twig');
    }
}
