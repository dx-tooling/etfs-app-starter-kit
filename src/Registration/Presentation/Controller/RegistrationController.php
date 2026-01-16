<?php

declare(strict_types=1);

namespace App\Registration\Presentation\Controller;

use App\Account\Domain\Entity\User;
use App\Account\Domain\Enum\Role;
use App\Account\Infrastructure\Repository\UserRepositoryInterface;
use App\Registration\Presentation\Form\RegistrationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route(
        path: '/register',
        name: 'registration.presentation.register'
    )]
    public function register(
        Request                     $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepositoryInterface     $userRepository,
        Security                    $security
    ): Response {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('content.presentation.homepage');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $plainPassword
                )
            );

            $user->addRole(Role::REGISTERED_USER);

            $userRepository->add($user, true);

            $this->addFlash('success', 'Your account has been created. Welcome!');

            $security->login($user, 'form_login', 'main');

            return $this->redirectToRoute('content.presentation.homepage');
        }

        return $this->render('@registration.presentation/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
