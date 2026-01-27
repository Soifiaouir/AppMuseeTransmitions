<?php

namespace App\Form;

use App\Entity\Theme;
use App\Entity\Color;  // ← AJOUT MANQUANT
use App\Repository\ColorRepository;  // ← AJOUT MANQUANT
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ThemeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
//            ->add('colors', EntityType::class, [
//                'class' => Color::class,
//                'query_builder' => function (ColorRepository $repo) {
//                    return $repo->createQueryBuilder('c')->orderBy('c.name', 'ASC');
//                },
//                'choice_label' => function (Color $color) {
//                    return sprintf(
//                        '%s <span class="color-pastille" style="background-color: %s;"></span>',
//                        $color->getName(),
//                        $color->getColorCode()
//                    );
//                },
//                'choice_attr' => function (Color $color) {
//                    return ['data-color' => $color->getColorCode()];
//                },
//                'multiple' => true,
//                'expanded' => true,
//                'label' => 'Couleurs',
//                'label_html' => true, // Important pour afficher le HTML dans les labels
//            ])
            ->add('themeBackgroundColor', EntityType::class, [
                'class' => Color::class,
                'required' => false,
            ])
            ->add('backgroundImageFile', FileType::class, [
                'label' => 'Image de fond',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => ['image/*'],
                        'mimeTypesMessage' => 'Image uniquement',
                    ])
                ],
            ]);
        if ($options['is_admin']) {
            $builder
            ->add('archived');
                }
        $builder
            ->add('submit', SubmitType::class, [
                'label' => 'Valider',
                'attr' => ['class' => 'submitButton']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Theme::class,
            'is_admin' => false,
        ]);
    }
}