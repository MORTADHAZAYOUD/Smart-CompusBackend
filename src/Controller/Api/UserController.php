<?php
// src/Controller/UserController.php
namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\Administrator;
use App\Entity\Teacher;
use App\Entity\Student;
use App\Entity\ParentUser;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private SerializerInterface $serializer
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Tag(name: 'user')]

    public function getUsers(Request $request): JsonResponse
    {
        $role = $request->query->get('role');
        $users = $role ? $this->userRepository->findByRole($role) : $this->userRepository->findAll();
        
        return $this->json($users, 200, [], ['groups' => ['user:read']]);
    }

    #[Route('/admin/dashboard', methods: ['GET'])]
    #[OA\Tag(name: 'admin')]
    #[OA\Response(
        response: 200,
        description: 'Returns all users grouped by type with statistics',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                'students' => new OA\Property(property: 'students', type: 'array'),
                'teachers' => new OA\Property(property: 'teachers', type: 'array'),
                'parents' => new OA\Property(property: 'parents', type: 'array'),
                'administrators' => new OA\Property(property: 'administrators', type: 'array'),
                'statistics' => new OA\Property(property: 'statistics', type: 'object')
            ]
        )
    )]
    public function getAdminDashboard(): JsonResponse
    {
        // Get all users grouped by type
        $students = $this->em->getRepository(Student::class)->findAll();
        $teachers = $this->em->getRepository(Teacher::class)->findAll();
        $parents = $this->em->getRepository(ParentUser::class)->findAll();
        $administrators = $this->em->getRepository(Administrator::class)->findAll();
        
        // Calculate statistics
        $statistics = [
            'total_users' => count($students) + count($teachers) + count($parents) + count($administrators),
            'total_students' => count($students),
            'total_teachers' => count($teachers),
            'total_parents' => count($parents),
            'total_administrators' => count($administrators),
            'students_by_class' => $this->getStudentsByClass($students),
            'recently_registered' => $this->getRecentlyRegisteredUsers(),
        ];

        return $this->json([
            'students' => $students,
            'teachers' => $teachers,
            'parents' => $parents,
            'administrators' => $administrators,
            'statistics' => $statistics
        ], 200, [], ['groups' => ['user:read', 'student:read', 'teacher:read', 'parent:read', 'admin:read']]);
    }

    private function getStudentsByClass(array $students): array
    {
        $classCounts = [];
        foreach ($students as $student) {
            $className = $student->getClasse() ? $student->getClasse()->getNom() : 'Non assigné';
            $classCounts[$className] = ($classCounts[$className] ?? 0) + 1;
        }
        return $classCounts;
    }

    private function getRecentlyRegisteredUsers(): array
    {
        $oneWeekAgo = new \DateTime('-1 week');
        return $this->userRepository->createQueryBuilder('u')
            ->where('u.id IS NOT NULL')
            ->orderBy('u.id', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Tag(name: 'user')]
    public function showUser(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
        
        return $this->json($user, 200, [], ['groups' => ['user:read']]);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Tag(name: 'user')]
    public function createUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Factory pattern pour créer le bon type d'utilisateur
        $user = match($data['role']) {
            'admin' => new Administrator(),
            'teacher' => new Teacher(),
            'student' => new Student(),
            'parent' => new ParentUser(),
            default => throw new \InvalidArgumentException('Rôle invalide')
        };

        // Mapping des propriétés communes
        $user->setEmail($data['email'])
             ->setFirstname($data['firstname'])
             ->setLastname($data['lastname'])
             ->setRoles([$data['role']]);

        // Propriétés spécifiques selon le type
        if ($user instanceof Teacher && isset($data['specialite'])) {
            $user->setSpecialite($data['specialite']);
        }
        
        if ($user instanceof ParentUser) {
            if (isset($data['profession'])) $user->setProfession($data['profession']);
            if (isset($data['telephone'])) $user->setTelephone($data['telephone']);
        }
        
        if ($user instanceof Student) {
            if (isset($data['numStudent'])) $user->setNumStudent($data['numStudent']);
            if (isset($data['dateNaissance'])) {
                $user->setDateNaissance(new \DateTime($data['dateNaissance']));
            }
        }

        $this->em->persist($user);
        $this->em->flush();

        return $this->json($user, 201, [], ['groups' => ['user:read']]);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Tag(name: 'user')]
    public function updateUser(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $data = json_decode($request->getContent(), true);
        
        // Mise à jour des propriétés
        if (isset($data['email'])) $user->setEmail($data['email']);
        if (isset($data['firstname'])) $user->setFirstname($data['firstname']);
        if (isset($data['lastname'])) $user->setLastname($data['lastname']);

        $this->em->flush();
        if ($user instanceof Administrator && isset($data['privileges'])) {
            $user->setPrivileges($data['privileges']);
        }

        return $this->json($user, 200, [], ['groups' => ['user:read']]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Tag(name: 'user')]
    public function deleteUser(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $this->em->remove($user);
        $this->em->flush();

        return $this->json(['message' => 'Utilisateur supprimé'], 200);
    }
}