<?php

namespace App\Controller;

use App\Entity\ApiToken;
use App\Repository\ApiTokenRepository;
use Symfony\Component\HttpFoundation\Request;

class AuthController
{
    private ApiTokenRepository $apiTokenRepository;

    public function __construct(ApiTokenRepository $apiTokenRepository)
    {
        $this->apiTokenRepository = $apiTokenRepository;
    }

    public function authenticate(Request $request) : bool
    {
        if($request->headers->has('Authorization')) {
            $apiToken = $this->getToken($request);
            if($apiToken) {
                return true;
            }
        }
        return false;
    }

    public function getToken(Request $request): ?ApiToken
    {
        $bearerToken = substr($request->headers->get('Authorization'), 7);
        return $this->apiTokenRepository->findOneBy(['token' => $bearerToken]);
    }
}