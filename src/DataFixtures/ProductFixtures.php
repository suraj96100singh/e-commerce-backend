<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Product;

class ProductFixtures extends Fixture
{
    // public function load(ObjectManager $manager): void
    // {
    //     // $product = new Product();
    //     // $manager->persist($product);

    //     $manager->flush();
    // }

    public function load(ObjectManager $manager): void
    {
        $categories = ['Electronics', 'Clothing', 'Books', 'Furniture'];

        for ($i = 1; $i <= 10; $i++) {
            $product = new Product();
            $product->setTitle("Product $i");
            $product->setDescription("Description for product $i");
            $product->setPriceExclVat(mt_rand(1000, 5000) / 100); // Random price
            $product->setCategory($categories[array_rand($categories)]);
            $product->setImage(null); // No image for now

            $manager->persist($product);
        }

        $manager->flush();
    }
}
