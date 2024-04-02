<?php

namespace App\Form;

use App\Entity\Product\ProductPictures;
use Doctrine\DBAL\Types\BooleanType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductPicturesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('attachment', FileType::class, [
                'required'=>false
            ])
            ->add('active', CheckboxType::class,
                [
                    'required'=>false,
                    'label_attr' => ['class' => 'checkbox-switch'],
                    'label'=>'active'
                ])
            ->add('cover', CheckboxType::class,
                [
                    'required'=>false,
                    'label_attr' => ['class' => 'checkbox-switch'],
                    'label'=>'cover'
                ])
//            ->add('selected',  CheckboxType::class, ['required' => false])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductPictures::class,
        ]);
    }
}