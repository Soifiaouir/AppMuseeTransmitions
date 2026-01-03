<?php

namespace App\Form;

use App\Entity\Card;
use App\Entity\Theme;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('detail')
            ->add('moreInfoTitle')
            ->add('moreInfoDetails')
            ->add('theme', EntityType::class, [
                'class' => Theme::class,
                'choice_label' => 'name',
            ])
            ->add('submit', SubmitType::class,
                ['label' => 'Valider',
                    'attr' => ['class' => 'submitButton']]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Card::class,
        ]);
    }
}
