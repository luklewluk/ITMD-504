<?php

namespace App\Controller;

use App\Entity\Product as Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ApiController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
    ) {
    }

    #[Route('/api/health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json(['status' => 'ok']);
    }

    #[Route('/api/products', methods: ['GET'])]
    public function products(): JsonResponse
    {
        $products = $this->productRepository->findAll();
        $productsArray = [];
        foreach ($products as $product) {
            $productsArray[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
            ];
        }

        return $this->json($productsArray);
    }

    #[Route('/api/product/{id}', methods: ['DELETE'])]
    public function productDelete(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if ($product === null) {
            return $this->json(
                [
                    'success' => false,
                    'message' => sprintf('Product %d not found', $id),
                ]
            );
        }

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => sprintf('Product %d deleted', $id),
        ]);
    }

    #[Route('/api/products', methods: ['POST'])]
    public function productCreate(Request $request): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);
        $productName = $requestData['name'];
        $productPrice = $requestData['price'];

        $product = new Product();
        $product->setName($productName);
        $product->setPrice($productPrice);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => sprintf('Product created with ID %d', $product->getId()),
        ]);
    }

    #[Route('/api/product/{id}', methods: ['PUT'])]
    public function productUpdate(int $id, Request $request): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);
        $productName = $requestData['name'];
        $productPrice = $requestData['price'];

        $product = $this->productRepository->find($id);
        if ($product === null) {
            return $this->json(
                [
                    'success' => false,
                    'message' => sprintf('Product %d not found', $id),
                ]
            );
        }

        $product->setName($productName);
        $product->setPrice($productPrice);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => sprintf('Product %d updated', $product->getId()),
        ]);
    }

    private function json(mixed $data): JsonResponse
    {
        $response = new JsonResponse($data);
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }
}
