<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ApiController
{
    private const DEFAULT_PRODUCTS = [
        'Milk',
        'Eggs',
        'Bread',
        'Apples',
        'Chicken',
        'Rice',
        'Coffee',
        'Cheese',
        'Yogurt',
        'Spinach',
        'Pasta',
        'Tomatoes',
        'Butter',
    ];

    #[Route('/api/health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json(['status' => 'ok']);
    }

    #[Route('/api/products', methods: ['GET'])]
    public function products(): JsonResponse
    {
        return $this->json(self::DEFAULT_PRODUCTS);
    }

    private function json(mixed $data): JsonResponse
    {
        $response = new JsonResponse($data);
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }
}
