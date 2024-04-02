<?php

namespace App\Controller;


use App\Entity\Order\Order;
use App\Entity\Order\OrderLine;
use App\Entity\Product\Product;
use App\Form\FinaliseFormType;
use App\Repository\CategoryRepository;
use App\Repository\OrderLineRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\StateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/cart', name: 'cart_')]
class CartController extends AbstractController
{
    protected OrderRepository $orderRepository;
    protected OrderLineRepository $orderLineRepository;
    protected EntityManagerInterface $entityManager;
    protected StateRepository $stateRepository;
    protected CategoryRepository $categoryRepository;
    private ProductRepository $productRepository;

    public function __construct(OrderRepository $orderRepository, CategoryRepository $categoryRepository, OrderLineRepository $orderLineRepository, ProductRepository $productRepository, StateRepository $stateRepository, EntityManagerInterface $entityManager)
    {
        $this->orderRepository = $orderRepository;
        $this->orderLineRepository = $orderLineRepository;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->stateRepository = $stateRepository;
        $this->entityManager = $entityManager;
    }


    #[Route('/', name: 'index')]
    public function index(SessionInterface $session, ProductRepository $productRepository)
    {
        $panier = $session->get('panier', []);
        $dataPanier = [];
        $total = 0;
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
        return $this->render('cart/index.html.twig', [
            'dataPanier' => $dataPanier,
            'total' => $total
        ]);
    }


    #[Route('/add/{id}', name: 'add')]
    public function add(Product $product, SessionInterface $session)
    {

        $panier = $session->get('panier', []);
        $id = $product->getId();
        if (!empty($panier[$id])) {
            $panier[$id]++;
        } else {
            $panier[$id] = 1;
        }
        $session->set('panier', $panier);
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/addAjax/{id}', name: 'addAjax')]
    public function addAjax(Product $product, Request $request, SessionInterface $session): JsonResponse
    {
        $newQuantity = $request->query->getInt('quantity');
        $panier = $session->get('panier', []);
        $id = $product->getId();
        $stock = $product->getStock();
        if ($stock > 0) {
            if ($newQuantity <= 0) {
                return new JsonResponse(['success' => false, 'message' => 'La quantité doit être supérieure à zéro.']);
            }

            if (!isset($panier[$id])) {
                $panier[$id] = $newQuantity;
            } else {
                $panier[$id] += $newQuantity;
            }

            $session->set('panier', $panier);

            $total = 0;
            foreach ($panier as $id => $quantity) {
                $product = $this->productRepository->find($id);
                if ($product !== null) {
                    $total += $product->getPrice() * $quantity;
                }
            }

            return new JsonResponse(['success' => true, 'total' => $total]);
        }
        return new JsonResponse(['success' => false, 'message' => 'le produit n\'a plus de stock disponible']);
    }


    #[Route('/remove/{id}', name: 'remove')]
    public function remove(Product $product, SessionInterface $session)
    {

        $panier = $session->get('panier', []);
        $id = $product->getId();
        if (!empty($panier[$id])) {
            if ($panier[$id] > 1) {
                unset($panier[$id]);
            }
        }
        $session->set('panier', $panier);
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/removeAjax/{id}', name: 'removeAjax')]
    public function removeAjax(Product $product, SessionInterface $session): JsonResponse
    {

        $panier = $session->get('panier', []);
        $id = $product->getId();
        if (!empty($panier[$id])) {
            unset($panier[$id]);
        }

        $total = 0;
        foreach ($panier as $id => $quantity) {
            $product = $this->productRepository->find($id);
            if ($product !== null) {
                $total += $product->getPrice() * $quantity;
            }
        }
        $session->set('panier', $panier);
        return new JsonResponse(['success' => true, 'total' => $total]);
    }

    #[Route('/delete/{id}', name: 'delete')]
    public function delete(Product $product, SessionInterface $session)
    {

        $panier = $session->get('panier', []);
        $id = $product->getId();
        if (!empty($panier[$id])) {
            if ($panier[$id] > 1) {
                $panier[$id]--;
            } else {
                unset($panier[$id]);
            }
        }
        $session->set('panier', $panier);
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/update/{id}', name: 'update')]
    public function update(Product $product, Request $request, SessionInterface $session): JsonResponse
    {
        $newQuantity = $request->query->getInt('quantity');
        $panier = $session->get('panier', []);
        $id = $product->getId();


        if ($newQuantity >= 1) {
            $panier[$id] = $newQuantity;
        } else {
            unset($panier[$id]);
        }

        $session->set('panier', $panier);

        // Calculer le total
        $total = 0;
        foreach ($panier as $id => $quantity) {
            $product = $this->productRepository->find($id);
            if ($product !== null) {
                $total += $product->getPrice() * $quantity;
            }
        }

        $productTotal = $product->getPrice() * $newQuantity;

        return new JsonResponse(['success' => true, 'total' => $total, 'productTotal' => $productTotal]);

    }

    #[Route('/updateCart/{id}', name: 'updateCart')]
    public function updateCart(Product $product, Request $request, SessionInterface $session): JsonResponse
    {
        $newQuantity = $request->query->getInt('quantity');
        $panier = $session->get('panier', []);
        $id = $product->getId();


        $panier[$id] = $newQuantity;

        $session->set('panier', $panier);

        $total = 0;
        foreach ($panier as $id => $quantity) {
            $product = $this->productRepository->find($id);
            if ($product !== null) {
                $total += $product->getPrice() * $quantity;
            }
        }

        // Calculer le total pour le produit modifié
        $productTotal = $product->getPrice() * $newQuantity;


        return new JsonResponse(['success' => true, 'total' => $total, 'productTotal' => $productTotal]);

    }

    #[Route('/data', name: 'cart_data')]
    public function getCartData(SessionInterface $session): JsonResponse
    {
        $panier = $session->get('panier', []);
        $data = [];

        foreach ($panier as $id => $quantity) {
            $product = $this->productRepository->find($id);
            if ($product !== null) {
                $data[] = [
                    'product' => [
                        'id' => $product->getId(),
                        'name' => $product->getName(),
                        'price' => $product->getPrice(),
                    ],
                    'quantity' => $quantity
                ];
            }
        }

        return $this->json($data);
    }

    #[Route('/finalise', name: 'finalise')]
    public function Finalise(SessionInterface $session, Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $panier = $session->get('panier', []);
        $order = new Order();
        $form = $this->createForm(FinaliseFormType::class, $order);
        $form->handleRequest($request);
        $dataPanier = [];
        $total = 0;
        foreach ($panier as $id => $quantity) {
            $product = $this->productRepository->find($id);
            if (($product !== null) && $product->getStock() < $quantity) {
                $this->addFlash('error', 'Le produit ' . $product->getName() . ' n\'a pas assez de stock. Stock restant : ' . $product->getStock());
                $newQuantity = $product->getStock();
                $panier[$id] = $newQuantity;
                $session->set('panier', $panier);
                return $this->redirectToRoute('cart_index');
            }
            $dataPanier[] = [
                'product' => $product,
                'quantity' => $quantity
            ];
            if ($product !== null) {
                $total += $product->getPrice() * $quantity;
            }
        }
        if ($form->isSubmitted() && $form->isValid()) {

            $order->setCodeState($this->stateRepository->find(2));
            $order->setUser($this->getUser());

            foreach ($panier as $id => $quantity) {
                $orderLine = new OrderLine();
                $product = $this->productRepository->find($id);
                $order->addOrderLine($orderLine);
                $order->setTotalPrice($total);
                $orderLine->setIdProduct($id);
                $orderLine->setName($product->getName());
                $orderLine->setPrice($product->getPrice());
                $orderLine->setQuantity($quantity);

            }
            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $session->set('panier', []);
            return $this->redirectToRoute('cart_order_recap', ['id' => $order->getId()]);
        }

        return $this->render('cart/finalise.html.twig', [
            'FinaliseForm' => $form->createView(),
            'dataPanier' => $dataPanier,
            'total' => $total

        ]);
    }

    #[Route('/order/recap/{id}', name: 'order_recap')]
    public function orderRecap(int $id): \Symfony\Component\HttpFoundation\Response
    {

        $order = $this->orderRepository->find($id);
        if (!$order) {
            return $this->redirectToRoute('shop_index');
        }
        if ($this->getUser() === $order->getUser()) {
            return $this->render('order/index.html.twig', ['order' => $order]);
        }
        return $this->redirectToRoute('shop_index');
    }


}