<?php

namespace App\Form;


use App\Entity\Category\Category;
use App\Entity\Product\Product;
use App\Entity\Product\ProductPictures;
use Doctrine\DBAL\Types\FloatType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class,['label' =>'Nom'])
            ->add('description', TextType::class )
            ->add('price', TextType::class,['label' =>'Prix'])
            ->add('productPictures', CollectionType::class, [
                'entry_type' => ProductPicturesFormType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label'=>false
            ])
            ->add('stock', TextType::class, [
                'label'=>'stock'
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'label' =>'Categorie',
                'label_attr' => [
                    'class' => 'font-weight-bold',
                ],
                'choice_label' => function (Category $category): string {
                    return $category->getName();
                }
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}