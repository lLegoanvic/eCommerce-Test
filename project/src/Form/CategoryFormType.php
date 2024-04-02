<?php

namespace App\Form;

use App\Entity\Category\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label'=>false])
            ->add('attachment',FileType::class, ['label'=>false,
                                                        'required'=>false])
            ->add('description', TextType::class, ['label'=>false,
                                                                'required'=>false])
            ->add('parentCategory', EntityType::class, [
                'class' => Category::class,
                'label'=>false,
                'label_attr' => [
                    'class' => 'font-weight-bold',
                ],
                'required'=>false,
                'choice_value' => function (Category $category = null): ?int {
                    return $category?->getId();
                },
                'choice_label' => function (Category $category): string {
                    return $category->getName();
                },

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}