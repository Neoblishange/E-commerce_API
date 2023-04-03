<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class OrdersController extends AbstractController {
    public function getAllOrdersOfUser(): JsonResponse{
        return new JsonResponse("");
    }

    public function getOrder(int $orderId): JsonResponse{
        return new JsonResponse("");
    }
}