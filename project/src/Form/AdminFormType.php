<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormTypeInterface;

class AdminFormType extends AbstractType
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
                'label'=>false
            ])
            ->add('roles',ChoiceType::class, ['choices' =>
                [
                    'ROLE_ADMIN' => 'ROLE_ADMIN',
                    'ROLE_USER' => 'ROLE_USER',
                    'ROLE_BANI' => 'ROLE_BANI'
                ],
                'multiple' => true,
                'label'=>false])
            ->add('infos',TextType::class,[
                'label'=>false,
                'required'=>false,
            ]);

  #      $builder->get('roles')
  #          ->addModelTransformer(new CallbackTransformer(
   #             function ($rolesArray) {
    #                // transform the array to a string
     #               return count($rolesArray)? $rolesArray[0]: null;
      #          },
       #         function ($rolesString) {
        #            // transform the string back to an array
         #           return [$rolesString];
          #      }
           # ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
