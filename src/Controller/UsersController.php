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

    public function register(Request $request): JsonResponse{
        $data = json_decode($request->getContent(), true);
        $user = new User(
            $data["login"],
            $data["password"], $data["email"],
            $data["firstname"], $data["lastname"]
        );
        $token = $this->tokenGenerator->generateToken();
        $apiToken = new ApiToken($user, $token);
        $session = $request->getSession();
        $session->set('api_token', $token);
        try {
            $this->userRepository->save($user, true);
            $this->apiTokenRepository->save($apiToken, true);
            return new JsonResponse("User added"); //code 200 OK
        }
        catch (Exception $exception) {
            return new JsonResponse("User exists"); // need error code
        }
    }

    public function login(Request $request): JsonResponse{
        $data = json_decode($request->getContent(), true);
        $user = $this->userRepository->findOneBy(['email' => $data['email']]);
        $session = $request->getSession();

        if($session->has('api_token')){
            $apiToken = $this->apiTokenRepository->findOneBy(['token' => $session->get('api_token')]);
            if($apiToken){
                return new JsonResponse("Authenticated with token"); //code 200 OK
            }
        }
        if (!$user) {
            return new JsonResponse("Bad login"); // need error code
        }
        else {
            $isValid = $user->getPassword() == $data['password'];
            if(!$isValid) {
                return new JsonResponse("Bad password"); // need error code
            }
            else {
                $newToken = $this->tokenGenerator->generateToken();
                $apiToken = $this->apiTokenRepository->findOneBy(['user' => $user]);
                if(!$apiToken) {
                    $apiToken = new ApiToken($user, $newToken);
                    $this->apiTokenRepository->save($apiToken, true);
                }
                else {
                    $session->set('api_token', $apiToken->getToken());
                }
                return new JsonResponse("Authenticated with login"); //need code 200 OK
            }
        }
    }

    public function updateUser(Request $request, EntityManagerInterface $entityManager): JsonResponse{
        $session = $request->getSession();
        $apiToken = $this->apiTokenRepository->findOneBy(['token' => $session->get('api_token')]);
        if($session->has('api_token') && $apiToken){
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
                    return new JsonResponse("Update user success"); //code 200 OK
                }
                catch (Exception $exception) {
                    return new JsonResponse("Update user failed"); // need error code
                }
            }
        }
        return new JsonResponse("You are not connected");
    }

    public function displayUser(Request $request): JsonResponse{
        $session = $request->getSession();
        $apiToken = $this->apiTokenRepository->findOneBy(['token' => $session->get('api_token')]);
        $user = $this->userRepository->findOneBy(['id' => $apiToken->getUserId()]);
        return new JsonResponse($user instanceof User ? $user->toArray() : []);
    }

    public function disconnect(Request $request): JsonResponse{
        $session = $request->getSession();
        $token = $session->get('api_token');
        $apiToken = $this->apiTokenRepository->findOneBy(['token' => $token]);
        if($apiToken) {
            $this->apiTokenRepository->remove($apiToken, true);
        }
        $session->clear();
        return new JsonResponse("You have been disconnected");
    }
}