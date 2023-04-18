<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Repository\ApiTokenRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
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
    private AuthController $authController;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ProductRepository $productRepository, UserRepository $userRepository,
        OrderRepository $orderRepository, ApiTokenRepository $apiTokenRepository,
        AuthController $authController, EntityManagerInterface $entityManager)
    {
        $this->productRepository = $productRepository;
        $this->userRepository = $userRepository;
        $this->orderRepository = $orderRepository;
        $this->apiTokenRepository = $apiTokenRepository;
        $this->authController = $authController;
        $this->entityManager = $entityManager;
    }

    public function getAllProducts(): JsonResponse
    {
        $allProducts = $this->productRepository->findAll();
        $response = [
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
        if($product) {
            return new JsonResponse($product instanceof Product ? $product->toJson()->getContent() : [], 200, [], true);
        }
        return new JsonResponse(['error' => "ERROR 404 - Product not found"], Response::HTTP_NOT_FOUND, [], false);
    }

    public function addProduct(Request $request): JsonResponse
    {
        if($this->authController->authenticate($request)) {
            try {
                $data = json_decode($request->getContent(), true);
                $product = new Product(
                    $data["name"], $data["description"],
                    $data["photo"], $data["price"]
                );
                $user = $this->authController->getApiToken($request)->getUserId();
                $product->setUser($user);
                $this->productRepository->save($product, true);
                return new JsonResponse(['success' => "CODE 200 - Product added"], Response::HTTP_OK, [], false);
            }
            catch (Exception $exception) {
                return new JsonResponse(['error' => "ERROR 400 - Add product failed"], Response::HTTP_BAD_REQUEST, [], false);
            }
        }
        return new JsonResponse(['error' => "CODE 401 - Unauthorized"], Response::HTTP_UNAUTHORIZED, [], false);
    }

    public function modifyAndDeleteProduct(Request $request, int $productId): JsonResponse
    {
        if($this->authController->authenticate($request)) {
            try {
                $product = $this->productRepository->findOneBy(['id' => $productId]);
                if($product){
                    $user = $this->userRepository->findOneBy(['id' => $this->authController->getApiToken($request)->getUserId()]);
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
                return new JsonResponse(['error' => "ERROR 404 - Product not found"], Response::HTTP_NOT_FOUND, [], false);
            }
            catch (Exception $exception){
                return new JsonResponse(['error' => 'ERROR 400 - Modify/Delete product failed'], Response::HTTP_BAD_REQUEST, [], false);
            }
        }
        return new JsonResponse(['error' => "CODE 401 - Unauthorized"], Response::HTTP_UNAUTHORIZED, [], false);
    }

    public function addProductToShoppingCart(Request $request, int $productId): JsonResponse
    {
        $session = $request->getSession();
        if($this->authController->authenticate($request)) {
            if($this->productRepository->findOneBy(['id' => $productId])){
                $shoppingCart = $session->has('shoppingCart') ? $session->get('shoppingCart') : [];
                if(sizeof($shoppingCart) > 0) {
                    foreach ($shoppingCart as $productInOrder) {
                        $productInOrder['id'] === $productId
                            ? $shoppingCart[$productId]['quantity'] = $productInOrder['quantity'] + 1
                            : $shoppingCart[$productId] = ['id' => $productId, 'quantity' => 1];
                    }
                }
                else {
                    $shoppingCart[$productId] = ['id' => $productId, 'quantity' => 1];
                }
                $session->set('shoppingCart', $shoppingCart);
                return new JsonResponse(['success' => "CODE 200 - Product added to cart"], Response::HTTP_OK, [], false);
            }
            return new JsonResponse(['error' => "ERROR 404 - Product not found"], Response::HTTP_NOT_FOUND, [], false);
        }
        return new JsonResponse(['error' => "CODE 401 - Unauthorized"], Response::HTTP_UNAUTHORIZED, [], false);
    }

    public function removeProductFromShoppingCart(Request $request, int $productId): JsonResponse
    {
        $session = $request->getSession();
        if($this->authController->authenticate($request)) {
            if($this->productRepository->findOneBy(['id' => $productId])){
                $shoppingCart = $session->has('shoppingCart') ? $session->get('shoppingCart') : [];
                if(isset($shoppingCart[$productId])){
                    unset($shoppingCart[$productId]);
                    $session->set('shoppingCart', $shoppingCart);
                    return new JsonResponse(['success' => "CODE 200 - Product removed from cart"], Response::HTTP_OK, [], false);
                }
            }
            return new JsonResponse(['error' => "ERROR 404 - Product not found"], Response::HTTP_NOT_FOUND, [], false);
        }
        return new JsonResponse(['error' => "CODE 401 - Unauthorized"], Response::HTTP_UNAUTHORIZED, [], false);
    }

    public function getStateOfShoppingCart(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if($this->authController->authenticate($request)) {
            $shoppingCart = $session->has('shoppingCart') ? $session->get('shoppingCart') : [];
            $response = [
                'found' => sizeof($shoppingCart),
                'products' => $shoppingCart
            ];
            return new JsonResponse(json_encode($response), Response::HTTP_OK, [], true);
        }
        return new JsonResponse(['error' => "CODE 401 - Unauthorized"], Response::HTTP_UNAUTHORIZED, [], false);
    }

    public function validateShoppingCart(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if($this->authController->authenticate($request)) {
            try {
                $shoppingCart = $session->has('shoppingCart') ? $session->get('shoppingCart') : [];
                $totalPrice = 0;
                $creationDate = new DateTime();
                $creationDate = $creationDate->format("Y-m-d H:i:s eP");
                $productsOrder = [];
                if($shoppingCart) {
                    foreach ($shoppingCart as $productInOrder) {
                        $product = $this->productRepository->findOneBy(['id' => $productInOrder['id']]);
                        $totalPrice += $product->getPrice() * $productInOrder['quantity'];
                        $productsOrder[] = $product;
                    }
                    $order = new Order($totalPrice, $creationDate, $productsOrder);
                    $user = $this->authController->getApiToken($request)->getUserId();
                    $order->setUser($user);
                    $this->orderRepository->save($order, true);

                    $orderId = $order->getId();
                    $conn = $this->entityManager->getConnection();
                    foreach ($shoppingCart as $productInOrder) {
                        $sql = 'UPDATE order_product SET quantity = :quantity WHERE order_id= :order_id AND product_id = :product_id';
                        $exec = $conn->executeStatement($sql, [
                            'quantity' => $productInOrder['quantity'],
                            'order_id' => $orderId,
                            'product_id' => $productInOrder['id']
                        ]);
                    }
                    $conn->close();
                    $session->remove('shoppingCart');
                    return new JsonResponse(['success' => "CODE 200 - Shopping cart validated"], Response::HTTP_OK, [], false);
                }
                return new JsonResponse(['error' => "ERROR 400 - Validate cart failed"], Response::HTTP_BAD_REQUEST, [], false);
            }
            catch (Exception $exception){
                return new JsonResponse(['error' => "ERROR 400 - Validate cart failed"], Response::HTTP_BAD_REQUEST, [], false);
            }
        }
        return new JsonResponse(['error' => "CODE 401 - Unauthorized"], Response::HTTP_UNAUTHORIZED, [], false);
    }
}