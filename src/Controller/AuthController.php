<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;


final class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $constraints = new Assert\Collection([
            'email' => [new Assert\NotBlank(), new Assert\Email()],
            'password' => [new Assert\NotBlank(), new Assert\Length(['min' => 6])],
            'name' => [new Assert\NotBlank()],
            'phone' => [new Assert\NotBlank()],
        ], allowExtraFields: true);


        $violations = $validator->validate($data, $constraints);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return new JsonResponse(['errors' => $errors], 422);
        }

        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Email already registered'], 409);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        $user->setName($data['name']);
        $user->setPhone($data['phone']);

        $em->persist($user);
        $em->flush();

        return $this->json(['message' => 'User registered successfully']);
    }

    #[Route('/api/manual-login', name: 'api_manual_login', methods: ['POST'])]
    public function manualLogin(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $JWTManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email and password are required'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        $token = $JWTManager->create($user);

        return $this->json([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'phone' => $user->getPhone()
            ]
        ]);
    }

    #[Route('/api/users', name: 'api_user_list', methods: ['GET'])]
    public function listUsers(EntityManagerInterface $em, Request $request, PaginatorInterface $paginator): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $search = $request->query->get('search');
        $sortBy = $request->query->get('sort_by');
        $sortOrder = $request->query->get('sort_order');

        $query = $em->getRepository(User::class)->createQueryBuilder('u');

        //search
        if (!empty($search)) {
            $query->where('u.name LIKE :search')
                ->orWhere('u.email LIKE :search')
                ->setParameter('search', "%$search%");
        }

        // Sorting
        $allowedSortFields = ['id', 'name', 'email', 'createdAt'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'id';
        }

        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $query->orderBy("u.$sortBy", $sortOrder);

        $page = $request->query->getInt('page', 1); //default page 1
        $limit = $request->query->getInt('limit', 10); //default limit 10

        $paginatedUser = $paginator->paginate($query, $page, $limit);

        $userData = [];

        foreach ($paginatedUser->getItems() as $user) {
            $userData[] = [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'phone' => $user->getPhone(),
            ];
        }

        return new JsonResponse([
            'users' => $userData,
            'pagination' => [
                'current_page' => $paginatedUser->getCurrentPageNumber(),
                'total_pages' => ceil($paginatedUser->getTotalItemCount() / $limit),
                'total_items' => $paginatedUser->getTotalItemCount(),
                'items_per_page' => $limit,
            ],
        ]);
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        // Just return a response — token deletion is handled by the client
        return new JsonResponse([
            'message' => 'Logged out successfully. Please delete the token on client side.'
        ]);
    }
}