<?php
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Sequentially;

class UserForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Pseudo',
                'disabled' => $options['disable_username'],
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 3, 'max' => 180]),
                ],
            ])
            ->add('email', EmailType::class, [
                'disabled' => $options['disable_mail'],
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 180]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => $options['password_required'],
                'constraints' => $options['password_required'] ? [
                    new Sequentially([
                        new NotBlank([
                            'message' => 'Veuillez entrer un mot de passe',
                        ]),
                        new Length([
                            'min' => 8,
                            'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                            'max' => 4096,
                        ]),
                        new Regex([
                            'pattern' => '/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$ %^&*-]).{8,}$/',
                            'message' => 'Votre mot de passe doit contenir au moins une lettre majuscule, une lettre minuscule, un chiffre et un caractère spécial.'
                        ]),
                    ]),
                ] : [],
            ])
            ->add('isAdmin', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Administrateur',
                'data' => in_array('ROLE_ADMIN', $options['current_roles']),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'disable_username' => false,
            'disable_mail' => false,
            'password_required' => true,
            'current_roles' => [],
        ]);
    }
}