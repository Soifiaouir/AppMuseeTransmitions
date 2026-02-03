<?php

namespace App\Form;

use App\Entity\Color;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ColorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('colorCode', TextType::class, [
                'label' => 'Code HEX',
                'label_attr' => ['class' => 'formLabel'],
                'row_attr' => ['class' => 'formWidget'],
                'attr' => [
                    'placeholder' => '#FF0000'
                ]
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom de la couleur',
                'label_attr' => ['class' => 'formLabel'],
                'row_attr' => ['class' => 'formWidget'],
                'attr' => [
                    'placeholder' => 'Nom de la couleur (ex: Rouge)'
                ]
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
            'attr' => [
                'class' => 'form'
            ]
        ]);
    }
}