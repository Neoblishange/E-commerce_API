<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class OrdersController extends AbstractController {
    private OrderRepository $orderRepository;
    private ProductRepository $productRepository;

    public function __construct(OrderRepository $orderRepository, ProductRepository $productRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
    }

    public function getAllOrdersOfUser(): JsonResponse
    {
        $allOrders = $this->orderRepository->findAll();
        $response = [
            'success' => 200,
            'found' => sizeof($allOrders),
        ];
        foreach ($allOrders as $order){
            $response['orders'][] = $order instanceof Order ? json_decode($order->toJson()->getContent()) : [];
        }
        $response = json_encode($response);
        return new JsonResponse($response, 200, [], true);
    }

    public function getOrder(int $orderId): JsonResponse
    {
        $order = $this->orderRepository->findOneBy(['id' => $orderId]);
        return new JsonResponse($order instanceof Order ? $order->toJson()->getContent() : [], 200, [], true);
    }
}