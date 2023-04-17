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

    public function __construct(UserRepository $userRepository,
        ApiTokenRepository $apiTokenRepository, TokenGeneratorInterface $tokenGenerator
    ){
        $this->userRepository = $userRepository;
        $this->apiTokenRepository = $apiTokenRepository;
        $this->tokenGenerator = $tokenGenerator;
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
        $session = $request->getSession();
        $session->set('apiToken', $token);
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
        $session = $request->getSession();
        if($session->has('apiToken')){
            $apiToken = $this->apiTokenRepository->findOneBy(['token' => $session->get('apiToken')]);
            if($apiToken){
                return new JsonResponse(['success' => "CODE 200 - Authenticated with token"], Response::HTTP_OK, [], false);
            }
        }
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
                else {
                    $session->set('apiToken', $apiToken->getToken());
                }
                return new JsonResponse(['success' => "CODE 200 - Authenticated with login"], Response::HTTP_OK, [], false);
            }
        }
    }

    public function updateUser(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $session = $request->getSession();
        $apiToken = $this->apiTokenRepository->findOneBy(['token' => $session->get('apiToken')]);
        if($session->has('apiToken') && $apiToken){
            $data = json_decode($request->getContent(), true);
            $currentUser = $this->userRepository->findOneBy(['id' => $apiToken->getUserId()]);
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
        return new JsonResponse(['error' => "ERROR 401 - You are not connected"], Response::HTTP_UNAUTHORIZED, [], false);
    }

    public function displayUser(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $apiToken = $this->apiTokenRepository->findOneBy(['token' => $session->get('apiToken')]);
        $user = $this->userRepository->findOneBy(['id' => $apiToken->getUserId()]);
        return new JsonResponse($user instanceof User ? $user->toJson()->getContent() : [], 200, [], true);
    }

    public function disconnect(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $apiToken = $this->apiTokenRepository->findOneBy(['token' => $session->get('apiToken')]);
        if($apiToken) {
            $this->apiTokenRepository->remove($apiToken, true);
        }
        $session->clear();
        return new JsonResponse(['success' => "CODE 200 - You have been disconnected"], Response::HTTP_OK, [], false);
    }
}