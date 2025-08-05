<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator
    ) {}

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request, UserRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des données d'entrée
        if (!isset($data['email'], $data['password'], $data['nom'], $data['prenom'], $data['roles'])) {
            return $this->json(['error' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'utilisateur existe déjà
        if ($userRepository->findOneBy(['email' => $data['email']])) {
            return $this->json(['error' => 'Un utilisateur avec cet email existe déjà'], Response::HTTP_CONFLICT);
        }

        // Créer le nouvel utilisateur
        $user = new User();
        $user->setEmail($data['email']);
        $user->setNom($data['nom']);
        $user->setPrenom($data['prenom']);
        $user->setStatus('active');
        
        // Hacher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        $user->setMotDePasse($hashedPassword); // Pour la compatibilité avec le diagramme UML

        // Définir les rôles
        $allowedRoles = ['ROLE_ADMIN', 'ROLE_TEACHER', 'ROLE_STUDENT', 'ROLE_PARENT'];
        $roles = array_intersect($data['roles'], $allowedRoles);
        if (empty($roles)) {
            $roles = ['ROLE_USER'];
        }
        $user->setRoles($roles);

        // Valider l'entité
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        // Sauvegarder
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'roles' => $user->getRoles(),
                'status' => $user->getStatus()
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/login_check', name: 'login_check', methods: ['POST'])]
    public function loginCheck(): JsonResponse
    {
        // Cette méthode ne sera jamais atteinte car elle est gérée par le firewall JWT
        // Mais elle doit exister pour que la route soit disponible
        return $this->json(['message' => 'Login handled by JWT']);
    }

    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'user' => [
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
            ]
        ]);
    }

    #[Route('/profile', name: 'update_profile', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        // Mettre à jour les champs autorisés
        if (isset($data['nom'])) {
            $user->setNom($data['nom']);
        }
        if (isset($data['prenom'])) {
            $user->setPrenom($data['prenom']);
        }
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        // Changement de mot de passe (optionnel)
        if (isset($data['password']) && !empty($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
            $user->setMotDePasse($hashedPassword);
        }

        // Valider les changements
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'roles' => $user->getRoles(),
                'status' => $user->getStatus()
            ]
        ]);
    }

    #[Route('/roles', name: 'get_roles', methods: ['GET'])]
    public function getRoles(): JsonResponse
    {
        return $this->json([
            'roles' => [
                'ROLE_ADMIN' => 'Administrateur',
                'ROLE_TEACHER' => 'Enseignant',
                'ROLE_STUDENT' => 'Étudiant',
                'ROLE_PARENT' => 'Parent'
            ]
        ]);
    }
}