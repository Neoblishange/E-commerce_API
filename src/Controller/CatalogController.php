<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class CatalogController extends AbstractController {
    public function getAllProducts(): JsonResponse{
        return new JsonResponse("");
    }

    public function getProduct(int $productId): JsonResponse{
        return new JsonResponse("");
    }

    public function addProduct(): JsonResponse{
        return new JsonResponse("");
    }

    public function modifyAndDeleteProduct(int $productId): JsonResponse{
        return new JsonResponse("");
    }

    public function addProductToShoppingCart(int $productId): JsonResponse{
        return new JsonResponse("");
    }

    public function removeProductFromShoppingCart(int $productId): JsonResponse{
        return new JsonResponse("");
    }

    public function getStateOfShoppingCart(): JsonResponse{
        return new JsonResponse("");
    }

    public function validateShoppingCart(): JsonResponse{
        return new JsonResponse("");
    }
}