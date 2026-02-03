<?php

namespace App\Form;

use App\Entity\Card;
use App\Entity\Color;
use App\Entity\Media;
use App\Entity\Theme;
use App\Repository\MediaRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de création/édition de carte
 * Filtre dynamiquement les images selon le thème sélectionné
 */
class CardType extends AbstractType
{
    public function __construct(
        private MediaRepository $mediaRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Titre de la carte (obligatoire, unique par thème)
            ->add('title', TextType::class, [
                'label' => 'Titre ',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le titre de la carte'
                ],
                'help' => 'Le titre doit être unique dans ce thème'
            ])

            // Contenu détaillé
            ->add('detail', TextareaType::class, [
                'label' => 'Contenu ',
                'attr' => [
                    'rows' => 4,
                    'class' => 'form-control',
                    'placeholder' => 'Décrivez le contenu de la carte...'
                ]
            ])

            // Sections supplémentaires (MoreInfo)
            ->add('moreInfos', CollectionType::class, [
                'entry_type' => MoreInfoType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Sections supplémentaires',
                'required' => false,
                'entry_options' => ['label' => false],
                'attr' => [
                    'class' => 'more-infos-collection',
                    'data-prototype' => ''
                ],
                'prototype' => true,
                'prototype_name' => '__name__',
                'row_attr' => ['class' => 'more-info-item']
            ])

            // Thème parent (obligatoire)
            ->add('theme', EntityType::class, [
                'class' => Theme::class,
                'choice_label' => 'name',
                'label' => 'Thème ',
                'placeholder' => 'Choisir un thème',
                'attr' => ['class' => 'form-control']
            ])

            // Couleur du texte
            ->add('textColor', EntityType::class, [
                'class' => Color::class,
                'choice_label' => 'name',
                'label' => 'Couleur du texte',
                'required' => false,
                'placeholder' => 'Aucune',
                'attr' => ['class' => 'form-control']
            ])

            // Couleur de fond
            ->add('backgroundColor', EntityType::class, [
                'class' => Color::class,
                'choice_label' => 'name',
                'label' => 'Couleur de fond',
                'required' => false,
                'placeholder' => 'Aucune',
                'attr' => ['class' => 'form-control']
            ])

            // Bouton submit
            ->add('submit', SubmitType::class, [
                'label' => 'Créer la carte',
                'attr' => [
                    'class' => 'buttonCreate'
                ]
            ]);

        // Écouteur d'événements : filtre dynamique des images
        $this->addMediaFilterListener($builder);
    }

    /**
     * Ajoute l'écouteur pour filtrer dynamiquement les images selon le thème
     */
    private function addMediaFilterListener(FormBuilderInterface $builder): void
    {
        $mediaRepository = $this->mediaRepository;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($mediaRepository) {
            /** @var Card|null $card */
            $card = $event->getData();
            $form = $event->getForm();

            // Calculer le thème et récupérer les images filtrées
            $themeId = $card?->getTheme()?->getId();
            $images = $mediaRepository->findImagesByThemeForForm($themeId);

            $label = $themeId
                ? sprintf('Images du thème "%s" (%d disponibles)', $card->getTheme()->getName(), count($images))
                : sprintf('Images disponibles (%d)', count($images));

            $form->add('medias', EntityType::class, [
                'class' => Media::class,
                'choices' => $images,
                'multiple' => true,
                'choice_label' => 'userGivenName',
                'label' => $label,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Card::class,
            'attr' => ['novalidate' => true]
        ]);
    }
}
