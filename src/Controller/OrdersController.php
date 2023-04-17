<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrdersController extends AbstractController {
    private OrderRepository $orderRepository;
    private ProductRepository $productRepository;
    private AuthController $authController;

    public function __construct(
        OrderRepository $orderRepository, ProductRepository $productRepository,
        AuthController $authController)
    {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->authController = $authController;
    }

    public function getAllOrdersOfUser(Request $request): JsonResponse
    {
        if($this->authController->authenticate($request)) {
            $allOrders = $this->orderRepository->findAll();
            $response = [
                'success' => 200,
                'found' => sizeof($allOrders),
            ];
            foreach ($allOrders as $order) {
                $response['orders'][] = $order instanceof Order ? json_decode($order->toJson()->getContent()) : [];
            }
            $response = json_encode($response);
            return new JsonResponse($response, 200, [], true);
        }
        return new JsonResponse(['error' => "CODE 401 - Unauthorized"], Response::HTTP_UNAUTHORIZED, [], false);
    }

    public function getOrder(Request $request, int $orderId): JsonResponse
    {
        if($this->authController->authenticate($request)) {
            $order = $this->orderRepository->findOneBy(['id' => $orderId]);
            return new JsonResponse($order instanceof Order ? $order->toJson()->getContent() : [], 200, [], true);
        }
        return new JsonResponse(['error' => "CODE 401 - Unauthorized"], Response::HTTP_UNAUTHORIZED, [], false);
    }
}