<?php

namespace App\Controller\admin;

use App\Entity\Product\Product;
use App\Form\ProductFormType;
use App\Repository\ProductPicturesRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\file;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/admin/product', name: 'admin_product_')]
class ProductController extends AbstractController
{
    public EntityManagerInterface $entityManager;
    public ProductRepository $productRepository;
    public ProductPicturesRepository $productPicturesRepository;

    public function __construct(ProductPicturesRepository $productPicturesRepository,
                                ProductRepository         $productRepository,
                                EntityManagerInterface    $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->productPicturesRepository = $productPicturesRepository;
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {

        // findby renvoi une collec d'objet DONC YA PLUSIEURS PUTAIN DE PRODUIT :D
        $products = $this->productRepository->findBy([], ['id' => 'asc']);

        foreach ($products as $product) {
            if ($product->getProductPictures() !== NULL) {
                foreach ($product->getProductPictures() as $productPicture) {
                    if ($productPicture->isCover() && $productPicture->isActive()) {
                        $product->setProductCover($productPicture);
                        break;
                    }
                }
            }
        }
        return $this->render('/admin/product.html.twig', compact('products'));
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $product = new Product();

        $form = $this->createForm(ProductFormType::class, $product);

        $form->handleRequest($request);

        $directory = $this->getParameter('kernel.project_dir') . '/public/img';
        if (!file_exists($directory)) {
            if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // lo save image et set name
            foreach ($product->getProductPictures() as $productPicture) {
                if ($productPicture->getAttachment() !== null) {
                    $file = $productPicture->getAttachment();
                    $extension = $file->getClientOriginalExtension();
                    $name =  random_int(1, 99999) . '.' . $extension;
                    try {
                        $file->move($directory, $name);
                        $productPicture->setName($name);
                        $this->entityManager->persist($productPicture);
                    } catch (RandomException $e) {

                    }
                } elseif (!$this->entityManager->contains($productPicture)) {
                    $product->removeProductPicture($productPicture);
                }
            }
            $this->entityManager->persist($product);
            $this->entityManager->flush();

            $this->addFlash('success', 'Product created successfully.');

            return $this->redirectToRoute('admin_product_new');
        }
        return $this->render('admin/product_new.html.twig', [
            'ProductForm' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'edit')]
    #[ParamConverter('product', Product::class)]
    public function edit(Request $request, Product $product): Response
    {
        $form = $this->createForm(ProductFormType::class, $product);
        $form->handleRequest($request);
        $directory = $this->getParameter('kernel.project_dir') . '/public/img';
        if (!file_exists($directory)) {
            if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // lo save image et set name
            foreach ($product->getProductPictures() as $productPicture) {
                if ($productPicture->getAttachment() !== null) {
                    $file = $productPicture->getAttachment();
                    $extension = $file->getClientOriginalExtension();
                    $name =  random_int(1, 99999) . '.' . $extension;
                    try {
                        $file->move($directory, $name);
                        $productPicture->setName($name);
                        $this->entityManager->persist($productPicture);
                    } catch (RandomException $e) {

                    }
                } elseif (!$this->entityManager->contains($productPicture)) {
                    $product->removeProductPicture($productPicture);
                }
            }
            $this->entityManager->persist($product);
            $this->entityManager->flush();

            $this->addFlash('success', 'Product created successfully.');

            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/product_edit.html.twig', [
            'ProductForm' => $form->createView(),
            'product' => $product
        ]);
    }

    #[Route('/delete/{id}', name: 'delete')]
    #[ParamConverter('product', Product::class)]
    public function delete(Product $product): Response
    {
        $productPictures = $product->getProductPictures();

        foreach ($productPictures as $productPicture) {
            $this->removeProductPicturesFile($productPicture->getName());
        }
        //lo delete toutes les images sur le file system
        $this->entityManager->remove($product);
        $this->entityManager->flush();

        $this->addFlash('success', 'Product deleted successfully.');

        return $this->redirectToRoute('admin_product_index');
    }

    #[Route('/delete_selected_pictures', name: 'delete_selected_product_pictures')]
    public function deleteSelectedProductPictures(Request $request): Response
    {

        $selectedPictures = json_decode($request->getContent())->selectedValues;
        foreach ($selectedPictures as $id) {

            $productPictures = $this->productPicturesRepository->find($id);
            if ($productPictures !== null) {
                $this->removeProductPicturesFile($productPictures->getName());
                $this->entityManager->remove($productPictures);
                // lo delete img filersysteme

            }
        }
        try {
            $this->entityManager->flush();
            return new Response('OK');
        } catch (\Exception $e) {
            return new Response('KO', 400);
        }


    }

    private function removeProductPicturesFile(string $fileName): void
    {
        $directory = $this->getParameter('kernel.project_dir') . '/public/img';

        // Check if the file exists before attempting to remove it
        $filePath = $directory . '/' . $fileName;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

}