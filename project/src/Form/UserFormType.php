<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class UserFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        /**
         * recuperation auto des champs car email et infos sont des propriétés de user (class sur laquelle se base le form)
         * et que ces champs ont les EXACT meme nom que les propriété
         * le passage de la data se fait a la declaration du form dans le controller UserController
         */
        $builder
            ->add('email' ,EmailType::class,[

            ])
            ->add('infos',TextType::class,[

            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}