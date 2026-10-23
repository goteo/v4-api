<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChangePasswordFormType extends AbstractType
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'first_options' => [
                    'constraints' => [
                        new NotBlank(
                            message: $this->translator->trans('password_reset.reset.errorEmpty'),
                        ),
                        new Length(
                            min: 8,
                            minMessage: $this->translator->trans('password_reset.reset.errorLength'),
                            // max length allowed by Symfony for security reasons
                            max: 4096,
                        ),
                        new PasswordStrength(),
                        new NotCompromisedPassword(),
                    ],
                    'label' => $this->translator->trans('password_reset.reset.labelNew'),
                ],
                'second_options' => [
                    'label' => $this->translator->trans('password_reset.reset.labelRepeat'),
                ],
                'invalid_message' => $this->translator->trans('password_reset.reset.errorNoMatch'),
                // Instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
