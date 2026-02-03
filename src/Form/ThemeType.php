<?php

namespace App\Form;

use App\Entity\Theme;
use App\Entity\Color;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\All;

class ThemeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du thème',
                'attr' => ['class' => 'formWidget'],
                'label_attr' => ['class' => 'formLabel']
            ])
            ->add('themeBackgroundColor', EntityType::class, [
                'class' => Color::class,
                'choice_label' => 'name',
                'label' => 'Couleur de fond',
                'required' => false,
                'placeholder' => 'Choisir une couleur',
                'attr' => ['class' => 'formWidget'],
                'label_attr' => ['class' => 'formLabel']
            ])
            // Image de fond principale (unique)
            ->add('backgroundImageFile', FileType::class, [
                'label' => 'Image de fond principale',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                            'image/svg+xml'
                        ],
                        'mimeTypesMessage' => 'Image uniquement (JPEG, PNG, GIF, WebP, SVG)',
                    ])
                ],
                'attr' => [
                    'class' => 'formWidget',
                    'accept' => 'image/*'
                ],
                'label_attr' => ['class' => 'formLabel']
            ])
            // Médias multiples
            ->add('mediaFiles', FileType::class, [
                'label' => 'Médias associés (images, vidéos, audio)',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'constraints' => [
                    new All([
                        new File([
                            'maxSize' => '50M',
                            'mimeTypes' => [
                                'image/jpeg',
                                'image/jpg',
                                'image/png',
                                'image/gif',
                                'image/webp',
                                'image/svg+xml',
                                'video/mp4',
                                'video/mpeg',
                                'video/quicktime',
                                'video/x-msvideo',
                                'video/webm',
                                'audio/mpeg',
                                'audio/wav',
                                'audio/ogg',
                                'audio/mp3',
                            ],
                            'mimeTypesMessage' => 'Fichier média uniquement (images, vidéos, audio)',
                        ])
                    ])
                ],
                'attr' => [
                    'class' => 'formWidget',
                    'accept' => 'image/*,video/*,audio/*'
                ],
                'label_attr' => ['class' => 'formLabel']
            ]);

        if ($options['is_admin']) {
            $builder->add('archived', null, [
                'label' => 'Archivé',
                'required' => false,
                'attr' => ['class' => 'formWidget'],
                'label_attr' => ['class' => 'formLabel']
            ]);
        }

        $builder->add('submit', SubmitType::class, [
            'label' => 'Valider',
            'attr' => ['class' => 'submitButton']
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Theme::class,
            'is_admin' => false,
        ]);
    }
}
