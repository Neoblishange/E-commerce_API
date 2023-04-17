<?php

namespace App\Controller;

use App\Entity\ApiToken;
use App\Entity\User;
use App\Repository\ApiTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;


class UsersController extends AbstractController {
    private UserRepository $userRepository;
    private ApiTokenRepository $apiTokenRepository;
    private TokenGeneratorInterface $tokenGenerator;
    private AuthController $authController;

    public function __construct(
        UserRepository $userRepository, ApiTokenRepository $apiTokenRepository,
        TokenGeneratorInterface $tokenGenerator, AuthController $authController
    ){
        $this->userRepository = $userRepository;
        $this->apiTokenRepository = $apiTokenRepository;
        $this->tokenGenerator = $tokenGenerator;
        $this->authController = $authController;
    }

    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = new User(
            $data["login"],
            $data["password"], $data["email"],
            $data["firstname"], $data["lastname"]
        );
        $token = $this->tokenGenerator->generateToken();
        $apiToken = new ApiToken($user, $token);
        try {
            $this->userRepository->save($user, true);
            $this->apiTokenRepository->save($apiToken, true);
            return new JsonResponse(['success' => "CODE 201 - New user registered"], Response::HTTP_CREATED, [], false);
        }
        catch (Exception $exception) {
            return new JsonResponse(['error' => "ERROR 401 - User already exists"], Response::HTTP_UNAUTHORIZED, [], false);
        }
    }

    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->userRepository->findOneBy(['email' => $data['email']]);
        if (!$user) {
            return new JsonResponse(['error' => "ERROR 401 - Bad logins"], Response::HTTP_UNAUTHORIZED, [], false);
        }
        else {
            $isValid = $user->getPassword() == $data['password'];
            if(!$isValid) {
                return new JsonResponse(['error' => "ERROR 401 - Bad logins"], Response::HTTP_UNAUTHORIZED, [], false);
            }
            else {
                $newToken = $this->tokenGenerator->generateToken();
                $apiToken = $this->apiTokenRepository->findOneBy(['user' => $user]);
                if(!$apiToken) {
                    $apiToken = new ApiToken($user, $newToken);
                    $this->apiTokenRepository->save($apiToken, true);
                }
                return new JsonResponse(['token' => $apiToken->getToken()], Response::HTTP_OK, [], false);
            }
        }
    }

    public function updateUser(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if($this->authController->authenticate($request)) {
            $data = json_decode($request->getContent(), true);
            $currentUser = $this->userRepository->findOneBy(['id' => $this->authController->getApiToken($request)->getUserId()]);
            if($currentUser){
                $currentUser->setLogin($data["login"]);
                $currentUser->setPassword($data["password"]);
                $currentUser->setEmail($data["email"]);
                $currentUser->setFirstname($data["firstname"]);
                $currentUser->setLastname($data["lastname"]);
                try {
                    $this->userRepository->save($currentUser, true);
                    return new JsonResponse(['success' => "CODE 200 - Update user success"],Response::HTTP_OK, [], false);
                }
                catch (Exception $exception) {
                    return new JsonResponse(['error' => "ERROR 400 - Update user failed"], Response::HTTP_BAD_REQUEST, [], false);
                }
            }
        }
        return new JsonResponse(['error' => "CODE 401 - Unauthorized"], Response::HTTP_UNAUTHORIZED, [], false);
    }

    public function displayUser(Request $request): JsonResponse
    {
        if($this->authController->authenticate($request)) {
            $user = $this->userRepository->findOneBy(['id' => $this->authController->getApiToken($request)->getUserId()]);
            return new JsonResponse($user instanceof User ? $user->toJson()->getContent() : [], 200, [], true);
        }
        return new JsonResponse(['error' => "CODE 401 - Unauthorized"], Response::HTTP_UNAUTHORIZED, [], false);
    }

    public function disconnect(Request $request): JsonResponse
    {
        if($this->authController->authenticate($request)) {
            $apiToken = $this->apiTokenRepository->findOneBy(['token' => $this->authController->getApiToken($request)->getToken()]);
            $this->apiTokenRepository->remove($apiToken, true);
            $session = $request->getSession();
            $session->remove('shoppingCart');
            return new JsonResponse(['success' => "CODE 200 - You have been disconnected"], Response::HTTP_OK, [], false);
        }
        return new JsonResponse(['error' => "CODE 401 - Unauthorized"], Response::HTTP_UNAUTHORIZED, [], false);
    }
}