<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\ParentUser;
use App\Entity\Administrator;
use App\Entity\Classe;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    #[OA\Tag(name: 'registration')]
    public function apiRegister(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse {
        
        // 1. Parse JSON data
        $data = json_decode($request->getContent(), true);
        
        // Check if JSON is valid
        if ($data === null) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Données JSON invalides',
                'code' => 'INVALID_JSON'
            ], 400);
        }

        // 2. Check required fields
        $requiredFields = ['email', 'password', 'type', 'firstname', 'lastname'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return new JsonResponse([
                    'success' => false,
                    'error' => "Le champ '$field' est obligatoire",
                    'code' => 'MISSING_FIELD',
                    'field' => $field
                ], 400);
            }
        }

        // 3. Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Format d\'email invalide',
                'code' => 'INVALID_EMAIL'
            ], 400);
        }

        // 4. Validate password (minimum 6 characters for simplicity)
        if (strlen($data['password']) < 6) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Le mot de passe doit contenir au moins 6 caractères',
                'code' => 'PASSWORD_TOO_SHORT'
            ], 400);
        }

        // 5. Validate user type
        $validTypes = ['Student', 'Teacher', 'Parent', 'Admin'];
        if (!in_array($data['type'], $validTypes)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Type d\'utilisateur invalide. Types autorisés: ' . implode(', ', $validTypes),
                'code' => 'INVALID_USER_TYPE',
                'validTypes' => $validTypes
            ], 400);
        }

        // 6. Check if user already exists
        try {
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Un utilisateur avec cet email existe déjà',
                    'code' => 'USER_EXISTS'
                ], 409);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de la vérification de l\'email',
                'code' => 'DATABASE_CHECK_ERROR'
            ], 500);
        }

        // 7. Create user based on type
        try {
            $type = $data['type'];
            $user = match ($type) {
                'Student' => new Student(),
                'Teacher' => new Teacher(),
                'Parent' => new ParentUser(),
                'Admin' => new Administrator(),
                default => null
            };

            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Impossible de créer l\'utilisateur',
                    'code' => 'USER_CREATION_FAILED'
                ], 500);
            }

            // 8. Set basic properties
            $user->setEmail(trim(strtolower($data['email'])));
            $user->setFirstname(trim($data['firstname']));
            $user->setLastname(trim($data['lastname']));
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
            $user->setRoles(['ROLE_' . strtoupper($type)]);

            // 9. Set type-specific properties with validation
            if ($user instanceof Student) {
                // Validate birth date
                if (!empty($data['dateNaissance'])) {
                    try {
                        $birthDate = new \DateTime($data['dateNaissance']);
                        $today = new \DateTime();
                        $age = $today->diff($birthDate)->y;
                        
                        if ($age < 3 || $age > 25) {
                            return new JsonResponse([
                                'success' => false,
                                'error' => 'L\'âge doit être entre 3 et 25 ans',
                                'code' => 'INVALID_AGE'
                            ], 400);
                        }
                        
                        $user->setDateNaissance($birthDate);
                    } catch (\Exception $e) {
                        return new JsonResponse([
                            'success' => false,
                            'error' => 'Format de date invalide',
                            'code' => 'INVALID_DATE_FORMAT'
                        ], 400);
                    }
                }

                // Validate and set class
                if (!empty($data['classe_id'])) {
                    try {
                        $classe = $em->getRepository(Classe::class)->find($data['classe_id']);
                        if (!$classe) {
                            return new JsonResponse([
                                'success' => false,
                                'error' => 'Classe non trouvée',
                                'code' => 'CLASS_NOT_FOUND'
                            ], 400);
                        }
                        $user->setClasse($classe);
                    } catch (\Exception $e) {
                        return new JsonResponse([
                            'success' => false,
                            'error' => 'Erreur lors de la recherche de la classe',
                            'code' => 'CLASS_LOOKUP_ERROR'
                        ], 500);
                    }
                }

                $user->setNumStudent($data['numStudent'] ?? 'AUTO-' . uniqid());
            }

            if ($user instanceof Teacher) {
                if (empty($data['specialite'])) {
                    return new JsonResponse([
                        'success' => false,
                        'error' => 'La spécialité est obligatoire pour un enseignant',
                        'code' => 'MISSING_SPECIALITY'
                    ], 400);
                }
                $user->setSpecialite(trim($data['specialite']));
            }

            if ($user instanceof ParentUser) {
                if (empty($data['profession'])) {
                    return new JsonResponse([
                        'success' => false,
                        'error' => 'La profession est obligatoire pour un parent',
                        'code' => 'MISSING_PROFESSION'
                    ], 400);
                }
                $user->setProfession(trim($data['profession']));
                $user->setTelephone($data['telephone'] ?? '');
            }

            if ($user instanceof Administrator) {
                $user->setPrivileges($data['privileges'] ?? ['READ']);
            }

            // 10. Save to database
            $em->beginTransaction();
            try {
                $em->persist($user);
                $em->flush();
                $em->commit();

                return new JsonResponse([
                    'success' => true,
                    'message' => 'Inscription réussie !',
                    'user' => [
                        'id' => $user->getId(),
                        'email' => $user->getEmail(),
                        'firstname' => $user->getFirstname(),
                        'lastname' => $user->getLastname(),
                        'type' => $type
                    ]
                ], 201);

            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                $em->rollback();
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Cet email est déjà utilisé',
                    'code' => 'EMAIL_ALREADY_USED'
                ], 409);

            } catch (\Exception $e) {
                $em->rollback();
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Erreur lors de l\'enregistrement en base de données',
                    'code' => 'DATABASE_SAVE_ERROR'
                ], 500);
            }

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur inattendue: ' . $e->getMessage(),
                'code' => 'UNEXPECTED_ERROR'
            ], 500);
        }
    }
}