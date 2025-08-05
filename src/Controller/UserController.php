<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Classe;
use App\Repository\UserRepository;
use App\Repository\ClasseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users', name: 'api_users_')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function list(Request $request, UserRepository $userRepository): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $role = $request->query->get('role');
        $classe = $request->query->get('classe');
        $search = $request->query->get('search');

        $criteria = [];
        
        if ($search) {
            // Recherche simple par nom, prénom ou email
            $users = $userRepository->createQueryBuilder('u')
                ->where('u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%')
                ->setFirstResult(($page - 1) * $limit)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        } else {
            $users = $userRepository->findBy($criteria, ['nom' => 'ASC', 'prenom' => 'ASC'], $limit, ($page - 1) * $limit);
        }

        $usersData = [];
        foreach ($users as $user) {
            $usersData[] = $this->serializeUser($user);
        }

        return $this->json([
            'users' => $usersData,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($usersData)
            ]
        ]);
    }

    #[Route('/students', name: 'students', methods: ['GET'])]
    #[IsGranted('ROLE_TEACHER')]
    public function getStudents(Request $request, UserRepository $userRepository): JsonResponse
    {
        $classeId = $request->query->get('classe');

        $qb = $userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_STUDENT%');

        if ($classeId) {
            $qb->andWhere('u.classe = :classe')
               ->setParameter('classe', $classeId);
        }

        $students = $qb->orderBy('u.nom', 'ASC')
                      ->addOrderBy('u.prenom', 'ASC')
                      ->getQuery()
                      ->getResult();

        $studentsData = [];
        foreach ($students as $student) {
            $studentsData[] = $this->serializeUser($student, true);
        }

        return $this->json(['students' => $studentsData]);
    }

    #[Route('/teachers', name: 'teachers', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getTeachers(UserRepository $userRepository): JsonResponse
    {
        $teachers = $userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_TEACHER%')
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getResult();

        $teachersData = [];
        foreach ($teachers as $teacher) {
            $teachersData[] = $this->serializeUser($teacher);
        }

        return $this->json(['teachers' => $teachersData]);
    }

    #[Route('/parents', name: 'parents', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getParents(UserRepository $userRepository): JsonResponse
    {
        $parents = $userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_PARENT%')
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getResult();

        $parentsData = [];
        foreach ($parents as $parent) {
            $parentData = $this->serializeUser($parent);
            $parentData['enfants'] = [];
            foreach ($parent->getEnfants() as $enfant) {
                $parentData['enfants'][] = [
                    'id' => $enfant->getId(),
                    'nom' => $enfant->getNom(),
                    'prenom' => $enfant->getPrenom(),
                    'classe' => $enfant->getClasse() ? $enfant->getClasse()->getNom() : null
                ];
            }
            $parentsData[] = $parentData;
        }

        return $this->json(['parents' => $parentsData]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[IsGranted('ROLE_TEACHER')]
    public function show(int $id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier les permissions
        $currentUser = $this->getUser();
        if (!$this->canViewUser($currentUser, $user)) {
            return $this->json(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $userData = $this->serializeUser($user, true);

        // Ajouter des informations supplémentaires selon le rôle
        if (in_array('ROLE_STUDENT', $user->getRoles())) {
            $userData['presences'] = [];
            foreach ($user->getPresences() as $presence) {
                $userData['presences'][] = [
                    'id' => $presence->getId(),
                    'status' => $presence->getStatus(),
                    'dateMarquage' => $presence->getDateMarquage()?->format('Y-m-d H:i:s'),
                    'seance' => $presence->getSeance() ? [
                        'id' => $presence->getSeance()->getId(),
                        'titre' => $presence->getSeance()->getTitre(),
                        'dateDebut' => $presence->getSeance()->getDateDebut()?->format('Y-m-d H:i:s')
                    ] : null
                ];
            }

            $userData['notes'] = [];
            foreach ($user->getNotes() as $note) {
                $userData['notes'][] = [
                    'id' => $note->getId(),
                    'valeur' => $note->getValeur(),
                    'coefficient' => $note->getCoefficient(),
                    'commentaire' => $note->getCommentaire(),
                    'dateAttribution' => $note->getDateAttribution()?->format('Y-m-d H:i:s'),
                    'seance' => $note->getSeance() ? [
                        'id' => $note->getSeance()->getId(),
                        'titre' => $note->getSeance()->getTitre()
                    ] : null
                ];
            }
        }

        return $this->json(['user' => $userData]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, ClasseRepository $classeRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'], $data['nom'], $data['prenom'], $data['roles'])) {
            return $this->json(['error' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setNom($data['nom']);
        $user->setPrenom($data['prenom']);
        $user->setStatus($data['status'] ?? 'active');

        // Hacher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        $user->setMotDePasse($hashedPassword);

        $user->setRoles($data['roles']);

        // Assigner à une classe si fournie
        if (isset($data['classeId']) && $data['classeId']) {
            $classe = $classeRepository->find($data['classeId']);
            if ($classe) {
                $user->setClasse($classe);
            }
        }

        // Assigner un parent si fourni (pour les étudiants)
        if (isset($data['parentId']) && $data['parentId']) {
            $parent = $this->entityManager->getRepository(User::class)->find($data['parentId']);
            if ($parent && in_array('ROLE_PARENT', $parent->getRoles())) {
                $user->setParent($parent);
            }
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => $this->serializeUser($user)
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(int $id, Request $request, UserRepository $userRepository, ClasseRepository $classeRepository): JsonResponse
    {
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) $user->setNom($data['nom']);
        if (isset($data['prenom'])) $user->setPrenom($data['prenom']);
        if (isset($data['email'])) $user->setEmail($data['email']);
        if (isset($data['status'])) $user->setStatus($data['status']);
        if (isset($data['roles'])) $user->setRoles($data['roles']);

        if (isset($data['password']) && !empty($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
            $user->setMotDePasse($hashedPassword);
        }

        if (isset($data['classeId'])) {
            if ($data['classeId']) {
                $classe = $classeRepository->find($data['classeId']);
                $user->setClasse($classe);
            } else {
                $user->setClasse(null);
            }
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => $this->serializeUser($user)
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json(['message' => 'Utilisateur supprimé avec succès']);
    }

    private function serializeUser(User $user, bool $includeDetails = false): array
    {
        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'roles' => $user->getRoles(),
            'status' => $user->getStatus(),
            'dateCreation' => $user->getDateCreation()?->format('Y-m-d H:i:s'),
            'classe' => $user->getClasse() ? [
                'id' => $user->getClasse()->getId(),
                'nom' => $user->getClasse()->getNom(),
                'niveau' => $user->getClasse()->getNiveau()
            ] : null
        ];

        if ($includeDetails) {
            $data['parent'] = $user->getParent() ? [
                'id' => $user->getParent()->getId(),
                'nom' => $user->getParent()->getNom(),
                'prenom' => $user->getParent()->getPrenom(),
                'email' => $user->getParent()->getEmail()
            ] : null;

            $data['enfants'] = [];
            foreach ($user->getEnfants() as $enfant) {
                $data['enfants'][] = [
                    'id' => $enfant->getId(),
                    'nom' => $enfant->getNom(),
                    'prenom' => $enfant->getPrenom()
                ];
            }
        }

        return $data;
    }

    private function canViewUser(User $currentUser, User $targetUser): bool
    {
        // Admins peuvent voir tous les utilisateurs
        if (in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return true;
        }

        // Enseignants peuvent voir leurs étudiants
        if (in_array('ROLE_TEACHER', $currentUser->getRoles())) {
            if (in_array('ROLE_STUDENT', $targetUser->getRoles())) {
                // Vérifier si l'étudiant est dans une classe de l'enseignant
                foreach ($currentUser->getClassesEnseignees() as $classe) {
                    if ($targetUser->getClasse() && $targetUser->getClasse()->getId() === $classe->getId()) {
                        return true;
                    }
                }
            }
            return false;
        }

        // Parents peuvent voir leurs enfants
        if (in_array('ROLE_PARENT', $currentUser->getRoles())) {
            return $targetUser->getParent() && $targetUser->getParent()->getId() === $currentUser->getId();
        }

        // Utilisateurs peuvent voir leur propre profil
        return $currentUser->getId() === $targetUser->getId();
    }
}