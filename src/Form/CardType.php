<?php

namespace App\Form;

use App\Entity\Card;
use App\Entity\Color;
use App\Entity\Media;
use App\Entity\Theme;
use App\Repository\ColorRepository;
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

class CardType extends AbstractType
{
    public function __construct(
        private MediaRepository $mediaRepo,
        private ColorRepository $colorRepo
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['class' => 'form-control']
            ])
            ->add('detail', TextareaType::class, [
                'label' => 'Détail',
                'attr' => ['rows' => 4, 'class' => 'form-control']
            ])
            ->add('moreInfos', CollectionType::class, [
                'entry_type' => MoreInfoType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Sections supplémentaires',
                'entry_options' => [
                    'label' => false,
                ],
                'attr' => [
                    'class' => 'more-infos-collection',
                    'data-prototype' => '',
                ],
                'prototype' => true,
                'prototype_name' => '__name__',
                'row_attr' => [
                    'class' => 'more-info-item'
                ],
            ])
            ->add('theme', EntityType::class, [
                'class' => Theme::class,
                'choice_label' => 'name',
                'label' => 'Thème',
                'placeholder' => 'Choisir un thème',
                'attr' => ['class' => 'form-control']
            ])
            ->add('medias', EntityType::class, [
                'class' => Media::class,
                'multiple' => true,
                'choice_label' => 'userGivenName',
                'label' => 'Images de fond',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('backgroundColor', EntityType::class, [
                'class' => Color::class,
                'required' => false,
            ])
            ->add('textColor', EntityType::class, [
                'class' => Color::class,
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Valider',
                'attr' => ['class' => 'submitButton btn btn-primary']
            ]);

        // Filtre les images selon le thème
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Card $card */
            $card = $event->getData();

            if ($card && $card->getTheme()) {
                $themeId = $card->getTheme()->getId();
                // Récupérer uniquement les images du thème
                $images = $this->mediaRepo->createQueryBuilder('m')
                    ->innerJoin('m.themes', 't')
                    ->where('t.id = :themeId')
                    ->andWhere('m.type = :type')
                    ->setParameter('themeId', $themeId)
                    ->setParameter('type', 'image')
                    ->getQuery()
                    ->getResult();

                $form = $event->getForm();
                $form->add('medias', EntityType::class, [
                    'class' => Media::class,
                    'choices' => $images,
                    'multiple' => true,
                    'choice_label' => 'userGivenName',
                    'label' => sprintf('Images du thème "%s"', $card->getTheme()->getName()),
                    'required' => false,
                    'attr' => ['class' => 'form-control']
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Card::class,
        ]);
    }
}