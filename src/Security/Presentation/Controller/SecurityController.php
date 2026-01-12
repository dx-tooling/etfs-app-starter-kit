<?php

namespace App\Security\Presentation\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    #[Route(
        path: '/login',
        name: 'security.presentation.login'
    )]
    public function login(): Response
    {
        return $this->render('security/login.html.twig');
    }
}
