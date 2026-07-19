<?php

namespace App\Controller;

use App\Entity\ShoppingListItem;
use App\Repository\ProductRepository;
use App\Repository\ShoppingListItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ShoppingListController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ShoppingListItemRepository $shoppingListItemRepository,
        private readonly ProductRepository $productRepository,
    ) {
    }

    #[Route('/api/shopping-list-items', methods: ['GET'])]
    public function shoppingListItems(): JsonResponse
    {
        $shoppingListItems = $this->shoppingListItemRepository->findAll();
        $shoppingListItemsArray = [];
        foreach ($shoppingListItems as $shoppingListItem) {
            $shoppingListItemsArray[] = [
                'id' => $shoppingListItem->getId(),
                'product_name' => $shoppingListItem->getProduct()->getName(),
                'product_price' => $shoppingListItem->getProduct()->getPrice(),
                'quantity' => $shoppingListItem->getQuantity(),
                'is_checked' => $shoppingListItem->isChecked(),
            ];
        }

        return $this->json($shoppingListItemsArray);
    }

    #[Route('/api/add-item-by-product/{id}', methods: ['POST'])]
    public function addItemByProduct(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if ($product === null) {
            return $this->json([
                'success' => false,
                'message' => 'Product not found',
            ]);
        }

        $existingShoppingListItem = $this->shoppingListItemRepository->findByProductId($id);
        if ($existingShoppingListItem !== null) {
            return $this->json([
                'success' => false,
                'message' => 'Product already in shopping list',
            ]);
        }

        $newShoppingListItem = new ShoppingListItem();
        $newShoppingListItem->setProduct($product);
        $newShoppingListItem->setQuantity(1);
        $newShoppingListItem->setIsChecked(false);
        $this->entityManager->persist($newShoppingListItem);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Product added to shopping list',
        ]);
    }

    #[Route('/api/change-item-quantity/{id}/{quantity}', methods: ['PUT'])]
    public function changeItemQuantity(int $id, int $quantity)
    {
        $shoppingListItem = $this->shoppingListItemRepository->find($id);
        if ($shoppingListItem === null) {
            return $this->json([
                'success' => false,
                'message' => 'Shopping list item not found',
            ]);
        }

        $shoppingListItem->setQuantity($quantity);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Shopping list item quantity updated',
        ]);
    }

    #[Route('/api/check-item/{id}', methods: ['PUT'])]
    public function checkItem(int $id)
    {
        $shoppingListItem = $this->shoppingListItemRepository->find($id);
        if ($shoppingListItem === null) {
            return $this->json([
                'success' => false,
                'message' => 'Shopping list item not found',
            ]);
        }

        $currentStatus = $shoppingListItem->isChecked();

        if ($currentStatus === true) {
            $shoppingListItem->setIsChecked(false);
        } else {
            $shoppingListItem->setIsChecked(true);
        }

        $this->entityManager->persist($shoppingListItem);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Shopping list item checked changed to ' . ($currentStatus === true ? 'false' : 'true'),
        ]);

    }

    #[Route('/api/delete-shopping-list-item/{id}', methods: ['DELETE'])]
    public function deleteShoppingListItem(int $id)
    {
        $shoppingListItem = $this->shoppingListItemRepository->find($id);
        if ($shoppingListItem === null) {
            return $this->json([
                'success' => false,
                'message' => 'Shopping list item not found',
            ]);
        }

        $this->entityManager->remove($shoppingListItem);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Shopping list item deleted',
        ]);
    }

    private function json(mixed $data): JsonResponse
    {
        $response = new JsonResponse($data);
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }
}
