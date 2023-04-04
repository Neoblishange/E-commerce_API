<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ApiTokenRepository;
use App\Repository\ProductRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CatalogController extends AbstractController {
    private ProductRepository $productRepository;
    private ApiTokenRepository $apiTokenRepository;

    public function __construct(ProductRepository $productRepository, ApiTokenRepository $apiTokenRepository)
    {
        $this->productRepository = $productRepository;
        $this->apiTokenRepository = $apiTokenRepository;
    }

    public function getAllProducts(): JsonResponse
    {
        $allProducts = $this->productRepository->findAll();
        $response = [];
        foreach ($allProducts as $product){
            $response[] = $product instanceof Product ? json_decode($product->toJson()->getContent()) : [];
        }
        $response = json_encode($response);
        return new JsonResponse($response, 200, [], true);
    }

    public function getProduct(int $productId): JsonResponse
    {
        $product = $this->productRepository->findOneBy(['id' => $productId]);
        return new JsonResponse($product instanceof Product ? $product->toJson() : [], 200, [], true);
    }

    public function addProduct(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $currentApiToken = $session->get('apiToken');
        $apiToken = $this->apiTokenRepository->findOneBy(['token' => $currentApiToken]);
        if($apiToken){
            $data = json_decode($request->getContent(), true);
            $product = new Product(
                $data["name"], $data["description"],
                $data["photo"], $data["price"]
            );
            $user = $apiToken->getUserId();
            $product->setUser($user);
            try {
                $this->productRepository->save($product, true);
                return new JsonResponse("CODE 200 - Product added", Response::HTTP_OK, [], true);
            }
            catch (Exception $exception) {
                return new JsonResponse("CODE " . $exception->getCode() . " - Add product failed", $exception->getCode(), [], true);
            }

        }
        return new JsonResponse("CODE 500 - Add product failed", Response::HTTP_INTERNAL_SERVER_ERROR, [], true);
    }

    public function modifyAndDeleteProduct(int $productId): JsonResponse
    {
        return new JsonResponse("");
    }

    public function addProductToShoppingCart(int $productId): JsonResponse
    {
        return new JsonResponse("");
    }

    public function removeProductFromShoppingCart(int $productId): JsonResponse
    {
        return new JsonResponse("");
    }

    public function getStateOfShoppingCart(): JsonResponse
    {
        return new JsonResponse("");
    }

    public function validateShoppingCart(): JsonResponse
    {
        return new JsonResponse("");
    }
}