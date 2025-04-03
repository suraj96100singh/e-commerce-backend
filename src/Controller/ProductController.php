<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    #[Route('/home', name: 'home')]
    public function home(): Response
    {
        return $this->render('home.html.twig');
    }

    #[Route('/products', name: 'product_list')]
    public function productList(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();
        return $this->render('product/list.html.twig', ['products' => $products]);
    }

    #[Route('/products/{id}', name: 'product_detail')]
    public function productDetail(int $id, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        return $this->render('product/detail.html.twig', ['product' => $product]);
    }
}

