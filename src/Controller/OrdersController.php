<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrdersController extends AbstractController {
    private OrderRepository $orderRepository;
    private ProductRepository $productRepository;
    private UserRepository $userRepository;
    private AuthController $authController;
    private EntityManagerInterface $entityManager;

    public function __construct(
        OrderRepository $orderRepository, ProductRepository $productRepository,
        UserRepository $userRepository, AuthController $authController,
        EntityManagerInterface $entityManager)
    {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->userRepository = $userRepository;
        $this->authController = $authController;
        $this->entityManager = $entityManager;
    }

    public function getAllOrdersOfUser(Request $request): JsonResponse
    {
        if($this->authController->authenticate($request)) {
            try {
                $allOrders = $this->orderRepository->findAll();
                $response = [
                    'found' => sizeof($allOrders),
                ];
                $conn = $this->entityManager->getConnection();
                foreach ($allOrders as $order) {
                    $response['orders'][] = $order instanceof Order ? json_decode($order->toJson()->getContent()) : [];
                    $orderId = $order->getId();
                    $sql = 'SELECT product_id, quantity FROM e_commerce.order_product WHERE order_id = ' . $orderId;
                    $exec = $conn->fetchAllAssociative($sql);
                    $products = $response['orders'][array_search($order, $allOrders)]->products;
                    foreach($products as $product) {
                        $id = array_search($product, $products);
                        if($product->id === $exec[$id]["product_id"]) {
                            $product->quantity = $exec[$id]["quantity"];
                        }
                    }
                }
                $conn->close();
                $response = json_encode($response);
                return new JsonResponse($response, 200, [], true);
            }
            catch (Exception $exception) {
                return new JsonResponse(['error' => "ERROR 404 - Order not found" .  $exception], Response::HTTP_NOT_FOUND, [], false);
            }
        }
        return new JsonResponse(['error' => "CODE 401 - Unauthorized"], Response::HTTP_UNAUTHORIZED, [], false);
    }

    public function getOrder(Request $request, int $orderId): JsonResponse
    {
        if($this->authController->authenticate($request)) {
            $user = $this->authController->getApiToken($request)->getUserId();
            $order = $this->orderRepository->findOneBy(['id' => $orderId, 'user' => $user]);
            try {
                $conn = $this->entityManager->getConnection();
                if($order){
                    $sql = 'SELECT product_id, quantity FROM e_commerce.order_product WHERE order_id = ' . $order->getId();
                    $exec = $conn->fetchAllAssociative($sql);
                    $response = $order instanceof Order ? json_decode($order->toJson()->getContent()) : [];
                    $products = $response->products;
                    foreach ($products as $product) {
                        $id = array_search($product, $products);
                        $response->products[$id]->quantity = $exec[$id]["quantity"];
                    }
                    $conn->close();
                    $response = json_encode($response);
                    return new JsonResponse($response, 200, [], true);
                }
            }
            catch (Exception $exception) {
                return new JsonResponse(['error' => "ERROR 404 - Order not found"], Response::HTTP_NOT_FOUND, [], false);
            }
        }
        return new JsonResponse(['error' => "CODE 401 - Unauthorized"], Response::HTTP_UNAUTHORIZED, [], false);
    }
}