<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class UsersController extends AbstractController {
    public function register(): JsonResponse{
        return new JsonResponse("");
    }

    public function login(): JsonResponse{
        return new JsonResponse("");
    }

    public function updateUser(): JsonResponse{
        return new JsonResponse("");
    }

    public function displayUser(): JsonResponse{
        return new JsonResponse("");
    }
}