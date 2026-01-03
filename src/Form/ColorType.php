<?php

namespace App\Form;

use App\Entity\Color;
use App\Entity\Theme;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ColorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('colorCode')
            ->add('themes', EntityType::class, [  // ← 'themes' au pluriel
                'class' => Theme::class,
                'choice_label' => 'name',
                'multiple' => true,  // ← Ajout pour permettre plusieurs thèmes
                'expanded' => false,  // ← Select multiple au lieu de checkboxes
                'required' => false,  // ← Optionnel selon vos besoins
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Valider',
                'attr' => ['class' => 'submitButton']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Color::class,
        ]);
    }
}