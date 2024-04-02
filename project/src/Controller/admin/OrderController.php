<?php

namespace App\Controller\admin;

use App\Repository\ProductRepository;
use http\Env\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\OrderRepository;
use App\Repository\StateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('admin/orders', name: 'admin_orders_')]
class OrderController extends AbstractController
{
    protected ProductRepository $productRepository;
    protected OrderRepository $orderRepository;

    protected EntityManagerInterface $entityManager;
    protected StateRepository $stateRepository;

    public function __construct(OrderRepository $orderRepository, StateRepository $stateRepository, EntityManagerInterface $entityManager, ProductRepository $productRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->stateRepository = $stateRepository;
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
    }

    #[Route('/view', name: 'view' )]
    public function ordersView(){
        $orders = $this->orderRepository->findBy([], ['id' => 'desc']);
        $statusValues = [];
        $orderDetails = null;
        foreach ($orders as $order){
            $statusId = $order->getCodeState()->getId() + 1;
            $status = $this->stateRepository->find($statusId);
            if($status !== null) {
                $statusValues[$order->getId()] = $status->getName();
            }
        }
        return $this->render('/admin/ordersView.html.twig', ['orders'=>$orders, 'orderDetails'=>$orderDetails, 'statusValues'=>$statusValues]);
    }



    #[Route('/get_order_details/{id}', name: 'get_order_details', methods: ['GET'])]
    public function getOrderDetails(int $id): JsonResponse
    {
        // Récupérez les détails de la commande avec l'ID $orderId depuis la base de données
        // Par exemple, supposons que vous ayez une méthode dans votre repository pour récupérer les détails de la commande
        $orderDetails = $this->orderRepository->find($id);

        // Vérifiez si la commande existe
        if (!$orderDetails) {
            // Retournez une réponse JSON avec un message d'erreur si la commande n'est pas trouvée
            return new JsonResponse(['error' => 'Commande non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Formattez les détails de la commande selon vos besoins
        $formattedOrderDetails = [
            'status' => $orderDetails->getCodeState()->getName(),
            'user' => $orderDetails->getUser()->getEmail(),
            'date' => $orderDetails->getCreatedAt()->format('d-m-Y H:i:s'),
            'total' =>$orderDetails->getTotalPrice()
            // Ajoutez d'autres détails de la commande selon vos besoins
        ];
        // Récupérer toutes les lignes de commande associées à cette commande
        $orderLines = $orderDetails->getOrderLines();

        // Formattez les détails de la commande selon vos besoins
        foreach ($orderLines as $orderLine) {
            $formattedOrderLine = [
                'product' => $orderLine->getName(),
                'quantity' => $orderLine->getQuantity(),
                'price' => $orderLine->getPrice(),
                // Ajoutez d'autres détails de la ligne de commande selon vos besoins
            ];
            $formattedOrderDetails['orderLines'][] = $formattedOrderLine;
        }


        // Retournez les détails de la commande sous forme de réponse JSON
        return new JsonResponse($formattedOrderDetails);
    }

    #[Route('/statusNext/{id}', name: 'status_next')]
    public function statusNext($id): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $order = $this->orderRepository->find($id);
        $orderLines = $order->getOrderLines();
        if (isset($order)) {
            $newStateId = $order->getCodeState()->getId()+1;
            $newState = $this->stateRepository->find($newStateId);
            if($newStateId === 4){
                foreach( $orderLines as $orderLine){
                    $productId = $orderLine->getIdProduct();
                    $product = $this->productRepository->find($productId);
                    if ($product !== null) {
                        $newStock = $product->getStock() - $orderLine->getQuantity();
                        $product->setStock($newStock);
                        $this->entityManager->persist($product);
                    }
                }
            }
            if($newState !== null){
                $order->setCodeState($newState);
                $this->entityManager->persist($order);
            }
            $this->entityManager->flush();
        }



        return $this->redirectToRoute('admin_orders_view');
    }

    #[Route('deleteOrder/{id}', name:'delete_order')]
    public function deleteOrder($id)
    {
        $order = $this->orderRepository->find($id);

        if (isset($order)) {
            $orderLines = $order->getOrderLines();
            $StateId = $order->getCodeState()->getId();
            if($StateId >= 4){
                foreach( $orderLines as $orderLine){
                    $productId = $orderLine->getIdProduct();
                    $product = $this->productRepository->find($productId);
                    if ($product !== null) {
                        $newStock = $product->getStock() + $orderLine->getQuantity();
                        $product->setStock($newStock);
                        $this->entityManager->persist($product);
                    }
                }
            }
            $this->entityManager->remove($order);
            $this->entityManager->flush();
        }
        return $this->redirectToRoute('admin_orders_view');
    }
}