<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Repository\ApiTokenRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CatalogController extends AbstractController {
    private ProductRepository $productRepository;
    private UserRepository $userRepository;
    private OrderRepository $orderRepository;
    private ApiTokenRepository $apiTokenRepository;

    public function __construct(
        ProductRepository $productRepository, UserRepository $userRepository,
        OrderRepository $orderRepository, ApiTokenRepository $apiTokenRepository)
    {
        $this->productRepository = $productRepository;
        $this->userRepository = $userRepository;
        $this->orderRepository = $orderRepository;
        $this->apiTokenRepository = $apiTokenRepository;
    }

    public function getAllProducts(): JsonResponse
    {
        $allProducts = $this->productRepository->findAll();
        $response = [
            'success' => 200,
            'found' => sizeof($allProducts),
        ];
        foreach ($allProducts as $product){
            $response['products'][] = $product instanceof Product ? json_decode($product->toJson()->getContent()) : [];
        }
        $response = json_encode($response);
        return new JsonResponse($response, 200, [], true);
    }

    public function getProduct(int $productId): JsonResponse
    {
        $product = $this->productRepository->findOneBy(['id' => $productId]);
        return new JsonResponse($product instanceof Product ? $product->toJson()->getContent() : [], 200, [], true);
    }

    public function addProduct(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $currentApiToken = $session->get('apiToken');
        $apiToken = $this->apiTokenRepository->findOneBy(['token' => $currentApiToken]);
        if($apiToken){
            try {
                $data = json_decode($request->getContent(), true);
                $product = new Product(
                    $data["name"], $data["description"],
                    $data["photo"], $data["price"]
                );
                $user = $apiToken->getUserId();
                $product->setUser($user);
                $this->productRepository->save($product, true);
                return new JsonResponse(['success' => "CODE 200 - Product added"], Response::HTTP_OK, [], false);
            }
            catch (Exception $exception) {
                return new JsonResponse(['error' => "ERROR 400 - Add product failed"], Response::HTTP_BAD_REQUEST, [], false);
            }
        }
        return new JsonResponse(['error' => "ERROR 400 - Not authenticated"], Response::HTTP_BAD_REQUEST, [], false);
    }

    public function modifyAndDeleteProduct(Request $request, int $productId): JsonResponse
    {
        $session = $request->getSession();
        $currentApiToken = $session->get('apiToken');
        $apiToken = $this->apiTokenRepository->findOneBy(['token' => $currentApiToken]);
        if($apiToken){
            try {
                $product = $this->productRepository->findOneBy(['id' => $productId]);
                if($product){
                    $user = $this->userRepository->findOneBy(['id' => $apiToken->getUserId()]);
                    if($user === $product->getUser()){
                        if($request->getMethod() == Request::METHOD_POST) {
                            $data = json_decode($request->getContent(), true);
                            $product->setName($data["name"]);
                            $product->setDescription($data["description"]);
                            $product->setPhoto($data["photo"]);
                            $product->setPrice($data["price"]);
                            $this->productRepository->save($product, true);
                            return new JsonResponse(['success' => "CODE 200 - Modify product succeed"], Response::HTTP_OK, [], false);
                        }
                        elseif ($request->getMethod() == Request::METHOD_DELETE) {
                            $this->productRepository->remove($product, true);
                            return new JsonResponse(['success' => "CODE 200 - Delete product succeed"], Response::HTTP_OK, [], false);
                        }
                    }
                }
                return new JsonResponse(['error' => 'ERROR 400 - Modify/Delete product failed'], Response::HTTP_BAD_REQUEST, [], false);
            }
            catch (Exception $exception){
                return new JsonResponse(['error' => 'ERROR 400 - Modify/Delete product failed'], Response::HTTP_BAD_REQUEST, [], false);
            }
        }
        return new JsonResponse(['error' => "ERROR 400 - Not authenticated"], Response::HTTP_BAD_REQUEST, [], false);
    }

    public function addProductToShoppingCart(Request $request, int $productId): JsonResponse
    {
        $session = $request->getSession();
        $currentApiToken = $session->get('apiToken');
        $apiToken = $this->apiTokenRepository->findOneBy(['token' => $currentApiToken]);
        if($apiToken){
            if($this->productRepository->findOneBy(['id' => $productId])){
                $shoppingCart = $session->has('shoppingCart') ? $session->get('shoppingCart') : [];
                if(sizeof($shoppingCart) > 0) {
                    foreach ($shoppingCart as $id => $quantity) {
                        $id === $productId
                            ? $shoppingCart[$productId] = $quantity + 1
                            : $shoppingCart[$productId] = 1;
                    }
                }
                else {
                    $shoppingCart[$productId] = 1;
                }
                $session->set('shoppingCart', $shoppingCart);
                return new JsonResponse(['success' => "CODE 200 - Product added to cart"], Response::HTTP_OK, [], false);
            }
            return new JsonResponse(['error' => "ERROR 400 - Product doesn't exist"], Response::HTTP_BAD_REQUEST, [], false);
        }
        return new JsonResponse(['error' => "ERROR 400 - Not authenticated"], Response::HTTP_BAD_REQUEST, [], false);
    }

    public function removeProductFromShoppingCart(Request $request, int $productId): JsonResponse
    {
        $session = $request->getSession();
        $currentApiToken = $session->get('apiToken');
        $apiToken = $this->apiTokenRepository->findOneBy(['token' => $currentApiToken]);
        if($apiToken){
            if($this->productRepository->findOneBy(['id' => $productId])){
                $shoppingCart = $session->has('shoppingCart') ? $session->get('shoppingCart') : [];
                if(isset($shoppingCart[$productId])){
                    unset($shoppingCart[$productId]);
                    $session->set('shoppingCart', $shoppingCart);
                    return new JsonResponse(['success' => "CODE 200 - Product removed from cart"], Response::HTTP_OK, [], false);
                }
            }
            return new JsonResponse(['error' => "ERROR 400 - Product doesn't exist"], Response::HTTP_BAD_REQUEST, [], false);
        }
        return new JsonResponse(['error' => "ERROR 400 - Not authenticated"], Response::HTTP_BAD_REQUEST, [], false);
    }

    public function getStateOfShoppingCart(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $currentApiToken = $session->get('apiToken');
        $apiToken = $this->apiTokenRepository->findOneBy(['token' => $currentApiToken]);
        if($apiToken){
            $shoppingCart = $session->has('shoppingCart') ? $session->get('shoppingCart') : [];
            return new JsonResponse(json_encode($shoppingCart), Response::HTTP_OK, [], true);
        } // VOIR AVEC NICO SI ON AFFICHE SEULEMENT IDs DES PRODUITS OU TOUT LE DESCRIPTIF DES PRODUITS
        return new JsonResponse(['error' => "ERROR 400 - Not authenticated"], Response::HTTP_BAD_REQUEST, [], false);
    }

    public function validateShoppingCart(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $currentApiToken = $session->get('apiToken');
        $apiToken = $this->apiTokenRepository->findOneBy(['token' => $currentApiToken]);
        if($apiToken){
            try {
                $shoppingCart = $this->getStateOfShoppingCart($request);
                $shoppingCart = json_decode($shoppingCart->getContent());
                $totalPrice = 0;
                $creationDate = new DateTime();
                $creationDate = $creationDate->format("Y-m-d H:i:s eP");
                $products = [];
                if($shoppingCart) {
                    foreach ($shoppingCart as $productId => $quantity) {
                        $product = $this->productRepository->findOneBy(['id' => $productId]);
                        $product->setQuantity($quantity);
                        $totalPrice += $product->getPrice() * $quantity;
                        $products[] = $product;
                    }
                    $order = new Order($totalPrice, $creationDate, $products);
                    $user = $apiToken->getUserId();
                    $order->setUser($user);
                    $this->orderRepository->save($order, true);
                    $session->remove('shoppingCart');
                    return new JsonResponse(['success' => "CODE 200 - Shopping cart validated"], Response::HTTP_OK, [], false);
                }
                return new JsonResponse(['error' => "ERROR 400 - Validate cart failed"], Response::HTTP_BAD_REQUEST, [], false);
            }
            catch (Exception $exception){
                return new JsonResponse(['error' => "ERROR 400 - Validate cart failed"], Response::HTTP_BAD_REQUEST, [], false);
            }
        }
        return new JsonResponse(['error' => "ERROR 400 - Not authenticated"], Response::HTTP_BAD_REQUEST, [], false);
    }
}