# Symfony E-Commerce Backend

This is a simple e-commerce API built with Symfony 6.4.

## Installation & Setup

1. Clone the repository:
   git clone https://github.com/suraj96100singh/e-commerce-backend.git
   cd e-commerce-backend

docker compose up -d --build

composer install

php bin/console doctrine:migrations:migrate

php bin/console doctrine:fixtures:load

API is now accessible at:

http://localhost:8080/api/products

