<?php

declare(strict_types=1);

namespace App\Registration\Presentation\Form;

use App\Account\Domain\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<User>
 */
class RegistrationFormType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array                $options
    ): void {
        $builder
            ->add(
                'email',
                EmailType::class,
                [
                    'label' => 'Email address',
                    'attr'  => [
                        'autocomplete' => 'email',
                        'placeholder'  => 'your@email.com',
                    ],
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Please enter your email address.',
                        ]),
                        new Email([
                            'message' => 'Please enter a valid email address.',
                        ]),
                    ],
                ]
            )
            ->add(
                'plainPassword',
                RepeatedType::class,
                [
                    'type'          => PasswordType::class,
                    'mapped'        => false,
                    'first_options' => [
                        'label' => 'Password',
                        'attr'  => [
                            'autocomplete' => 'new-password',
                            'placeholder'  => '••••••••',
                        ],
                        'constraints' => [
                            new NotBlank([
                                'message' => 'Please enter a password.',
                            ]),
                            new Length([
                                'min'        => 8,
                                'minMessage' => 'Your password should be at least {{ limit }} characters.',
                                'max'        => 4096,
                            ]),
                        ],
                    ],
                    'second_options' => [
                        'label' => 'Confirm Password',
                        'attr'  => [
                            'autocomplete' => 'new-password',
                            'placeholder'  => '••••••••',
                        ],
                    ],
                    'invalid_message' => 'The password fields must match.',
                ]
            )
            ->add(
                'agreeTerms',
                CheckboxType::class,
                [
                    'mapped'      => false,
                    'label'       => 'I agree to the terms of service',
                    'constraints' => [
                        new IsTrue([
                            'message' => 'You must agree to the terms of service.',
                        ]),
                    ],
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
