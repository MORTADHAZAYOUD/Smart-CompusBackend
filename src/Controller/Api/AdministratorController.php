<?php

namespace App\Controller\Api;

use App\Entity\Administrator;
use App\Repository\AdministratorRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/administrators')]
class AdministratorController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AdministratorRepository $administratorRepository
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Tag(name: 'administrator')]

    public function index(): JsonResponse
    {
        return $this->json($this->administratorRepository->findAll());
    }

    #[Route('', methods: ['POST'])]
    #[OA\Tag(name: 'administrator')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $admin = new Administrator();
        $admin->setFirstname($data['firstname'] ?? null);
        $admin->setLastname($data['lastname'] ?? null);
        $admin->setEmail($data['email'] ?? null);
        $admin->setPrivileges($data['privileges'] ?? []);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        return $this->json($admin, 201);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Tag(name: 'administrator')]
    public function show(Administrator $admin): JsonResponse
    {
        return $this->json($admin);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Tag(name: 'administrator')]
    public function update(Request $request, Administrator $admin): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $admin->setFirstname($data['firstname'] ?? $admin->getFirstname());
        $admin->setLastname($data['lastname'] ?? $admin->getLastname());
        $admin->setEmail($data['email'] ?? $admin->getEmail());
        $admin->setPrivileges($data['privileges'] ?? $admin->getPrivileges());

        $this->entityManager->flush();

        return $this->json($admin);
    }

    #[Route('/privilege/{privilege}', methods: ['GET'])]
    #[OA\Tag(name: 'administrator')]
    public function filterByPrivilege(string $privilege): JsonResponse
    {
        $admins = $this->administratorRepository->findByPrivilege($privilege);
        return $this->json($admins);
    }

    #[Route('/statistics/privileges', methods: ['GET'])]
    #[OA\Tag(name: 'administrator')]
    public function statsByPrivilege(): JsonResponse
    {
        return new JsonResponse($this->administratorRepository->countByPrivilege());
    }
}
