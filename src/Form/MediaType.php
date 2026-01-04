<?php

namespace App\Form;

use App\Entity\Card;
use App\Entity\Media;
use App\Entity\Theme;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class MediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Fichier à uploader
            ->add('file', FileType::class, [
                'label' => 'Fichier (Image, Vidéo ou Audio)',
                'mapped' => false, // Non mappé car géré manuellement
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '500M',
                        'mimeTypes' => [
                            // Images
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                            'image/svg+xml',
                            // Videos
                            'video/mp4',
                            'video/mpeg',
                            'video/webm',
                            // Audios
                            'audio/mpeg',
                            'audio/mp3',
                            'audio/wav',
                            'audio/ogg',
                            'audio/webm',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier valide (image, vidéo ou audio)',
                        'maxSizeMessage' => 'Le fichier est trop volumineux ({{ size }} {{ suffix }}). Maximum autorisé : {{ limit }} {{ suffix }}.',
                    ])
                ],
                'attr' => [
                    'accept' => 'image/*,video/*,audio/*'
                ]
            ])

            // Nom donné par l'utilisateur (optionnel, peut être modifié)
            ->add('userGivenName', TextType::class, [
                'label' => 'Nom du média',
                'required' => false,
                'help' => 'Laissez vide pour utiliser le nom du fichier'
            ])

            // Type de média (optionnel si vous voulez le choisir manuellement)
            ->add('type', ChoiceType::class, [
                'label' => 'Type de média',
                'choices' => [
                    'Image' => 'image',
                    'Vidéo' => 'video',
                    'Audio' => 'audio'
                ],
                'required' => false,
                'placeholder' => 'Détection automatique',
                'help' => 'Laissez vide pour détection automatique'
            ])

            // Associations
            ->add('cards', EntityType::class, [
                'class' => Card::class,
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'expanded' => false,
                'attr' => [
                    'class' => 'form-select',
                    'size' => 5
                ],
                'label' => 'Associer à des cartes',
                'help' => 'Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs cartes'
            ])
            ->add('themes', EntityType::class, [
                'class' => Theme::class,
                'choice_label' => 'name',
                'multiple' => true,
                'required' => false,
                'expanded' => false,
                'attr' => [
                    'class' => 'form-select',
                    'size' => 5
                ],
                'label' => 'Associer à des thèmes',
                'help' => 'Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs thèmes'
            ])

            ->add('submit', SubmitType::class, [
                'label' => 'Valider',
                'attr' => ['class' => 'submitButton']
            ]);

        // Note :
        // - name : toujours généré automatiquement (ID du média)
        // - size : toujours généré automatiquement (taille du fichier)
        // - extensionFile : toujours généré automatiquement (extension du fichier)
        // - userGivenName : peut être personnalisé ici, sinon prend le nom du fichier
        // - type : peut être forcé ici, sinon détecté automatiquement
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
        ]);
    }
}