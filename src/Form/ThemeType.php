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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ThemeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('colors', EntityType::class, [
                'class' => Color::class,
                'choice_label' => 'name',
                'multiple' => true,
                'by_reference' => false,
                'query_builder' => function (ColorRepository $repo) {
                    return $repo->createQueryBuilder('c')->orderBy('c.name', 'ASC');
                },
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