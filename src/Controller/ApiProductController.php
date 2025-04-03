<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;


#[Route('/api/products', name: 'api_products_')]
final class ApiProductController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function getProducts(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        Request $request
    ): JsonResponse {
        // Pagination parameters
        $page = max(1, (int) $request->query->get('page', 1));
        $per_page = max(1, (int) $request->query->get('per_page', 10)); // Default: 10 per page

        // Query builder for pagination
        $query = $entityManager->createQueryBuilder()
            ->select('p')
            ->from('App\Entity\Product', 'p')
            ->setFirstResult(($page - 1) * $per_page)
            ->setMaxResults($per_page)
            ->getQuery();

        // Use Doctrine Paginator
        $paginator = new Paginator($query);
        $totalItems = count($paginator);
        $totalPages = $totalItems / $per_page;

        $products = [];
        foreach ($paginator as $product) {
            $products[] = $product;
        }

        // Serialize response
        $jsonData = $serializer->serialize([
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'products' => $products
        ], 'json');

        return new JsonResponse($jsonData, 200, [], true);
    }


    #[Route('/{id}', methods: ['GET'])]
    public function getProduct(int $id, ProductRepository $productRepository, SerializerInterface $serializer): JsonResponse
    {
        $product = $productRepository->find($id);

        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], 404);
        }

        return new JsonResponse($serializer->serialize($product, 'json'), 200, [], true);
    }
}
