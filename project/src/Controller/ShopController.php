<?php

namespace App\Controller;

use App\Entity\Category\Category;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/shop', name: 'shop_')]
class ShopController extends AbstractController
{

    private CategoryRepository $categoryRepository;
    private ProductRepository $productRepository;

    public function __construct(CategoryRepository $categoryRepository, ProductRepository $productRepository)
    {
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
    }


    #[Route('/', name: 'index')]
    public function index(SessionInterface $session): \Symfony\Component\HttpFoundation\Response
    {
        $panier = $session->get('panier', []);
        $dataPanier = [];
        $total = 0;
        $products = [];
        $categories = $this->categoryRepository->findall();
        $catTree = $this->categoryTree($categories);
        foreach ($panier as $id => $quantity) {
            $product = $this->productRepository->find($id);
            $dataPanier[] = [
                'product' => $product,
                'quantity' => $quantity
            ];
            if ($product !== null) {
                $total += $product->getPrice() * $quantity;
            }
        }
        return $this->render('shop/index.html.twig',[
            'products' => $products,
            'dataPanier' => $dataPanier,
            'total' => $total,
            'categoryTree' => $catTree
        ]);
    }


    #[Route('/test', 'test')]
    public function test(): \Symfony\Component\HttpFoundation\Response
    {
        $categories = $this->categoryRepository->findall();
        $catTree = $this->categoryTree($categories);
//        dd($catTree);
        return $this->render('shop/index.html.twig', [
            'categoryTree' => $catTree
        ]);
    }

    #[Route('/test2/{id}', name: 'test2')]
    public function test2(int $id, SessionInterface $session, ProductRepository $productRepository): \Symfony\Component\HttpFoundation\Response
    {
        $categories = $this->categoryRepository->findall();
        $catTree = $this->categoryTree($categories);
        $panier = $session->get('panier', []);
        $dataPanier = [];
        $total = 0;
        $products = [];
        $cat = $this->categoryRepository->find($id);

        if ($cat !== null) {
            $this->feedProductFromCat($cat, $products);
        }


//        dd($allproducts);
        $childrenCategories = $this->categoryTree($categories, $id);

        foreach ($childrenCategories as $category) {
            $cat = $this->categoryTree($categories, $category['id']);
            if (count($cat) > 0) {
                foreach ($cat as $subChildrenCat) {
                    $sub = $this->categoryTree($categories, $subChildrenCat['id']);
                    if (count($sub) > 0) {
                        foreach ($sub as $subchildrencat2) {
                            $sub2 = $this->categoryRepository->find($subchildrencat2['id']);
                            if (null !== $sub2) {
                                $this->feedProductFromCat($sub2, $products);
                            }
                        }

                    }
                    $sub = $this->categoryRepository->find($subChildrenCat['id']);

                    if (null !== $sub) {
                        $this->feedProductFromCat($sub, $products);
                    }

                }
            }
            $childrenCat = $this->categoryRepository->find($category['id']);
            if (null !== $childrenCat) {
                $this->feedProductFromCat($childrenCat, $products);
            }

        }
        foreach ($panier as $idProduct => $quantity) {
            $product = $this->productRepository->find($idProduct);
            $dataPanier[] = [
                'product' => $product,
                'quantity' => $quantity
            ];
            if ($product !== null) {
                $total += $product->getPrice() * $quantity;
            }
        }


        return $this->render('shop/shop.html.twig', [
            'products' => $products,
            'dataPanier' => $dataPanier,
            'total' => $total,
            'categoryTree' => $catTree]);
    }


    #[Route('/produit/{id}', name: 'product')]
    public function productView(int $id): \Symfony\Component\HttpFoundation\Response
    {
        $categories = $this->categoryRepository->findall();
        $catTree = $this->categoryTree($categories);
        $product = $this->productRepository->find($id);
        return $this->render('shop/product.html.twig', [
            'product' => $product,
            'categoryTree' => $catTree
        ]);
    }


    public function categoryTree(array $category, int $parentId = null): array
    {
        $categoryTree = [];

        foreach ($category as $cat) {
            if ($cat->getIdParent() === $parentId) {
                $subcategory = [
                    'id' => $cat->getId(),
                    'name' => $cat->getName(),
                    'subcategories' => $this->categoryTree($category, $cat->getId()),
                ];

                $categoryTree[] = $subcategory;
            }
        }

        return $categoryTree;
    }

    public function feedProductFromCat(Category $category, array &$products): void
    {
        foreach ($category->getProducts() as $product) {
            $products[] = $product;
        }
    }
}