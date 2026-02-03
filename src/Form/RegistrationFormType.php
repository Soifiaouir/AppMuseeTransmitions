<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Pseudo',
                'label_attr' => ['class' => 'formLabel'],
                'row_attr' => ['class' => 'formWidget'],
                'attr' => [
                    'placeholder' => 'Votre pseudo'
                ]
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Mot de passe temporaire',
                    'label_attr' => ['class' => 'formLabel'],
                    'row_attr' => ['class' => 'formWidget'],
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'placeholder' => 'Minimum 8 caractères'
                    ]
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'label_attr' => ['class' => 'formLabel'],
                    'row_attr' => ['class' => 'formWidget'],
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'placeholder' => 'Retapez votre mot de passe'
                    ]
                ],
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/',
                        'message' => '8+ caractères : 1 majuscule, 1 minuscule, 1 chiffre requis',
                    ]),
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôle',
                'label_attr' => ['class' => 'formLabel'],
                'row_attr' => ['class' => 'formWidget'],
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'J\'accepte les conditions d\'utilisation',
                'label_attr' => ['class' => 'formLabel'],
                'row_attr' => ['class' => 'formWidget'],
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions d\'utilisation.',
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Ajouter un utilisateur',
                'attr' => ['class' => 'submitButton']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'attr' => [
                'class' => 'form'
            ]
        ]);
    }
}