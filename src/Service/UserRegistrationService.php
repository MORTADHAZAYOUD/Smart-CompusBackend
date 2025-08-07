<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\ParentUser;
use App\Entity\Administrator;
use App\Entity\Classe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRegistrationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    /**
     * Create a user based on registration data
     */
    public function createUser(array $data): array
    {
        $type = $data['type'];
        $user = match ($type) {
            'Student' => new Student(),
            'Teacher' => new Teacher(),
            'Parent' => new ParentUser(),
            'Admin' => new Administrator(),
            default => null
        };

        if (!$user) {
            return [
                'success' => false,
                'error' => 'Impossible de créer l\'utilisateur',
                'code' => 'USER_CREATION_FAILED'
            ];
        }

        // Set basic properties
        $user->setEmail(trim(strtolower($data['email'])));
        $user->setFirstname(trim($data['firstname']));
        $user->setLastname(trim($data['lastname']));
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        $user->setRoles(['ROLE_' . strtoupper($type)]);

        return ['success' => true, 'user' => $user];
    }

    /**
     * Configure student-specific properties
     */
    public function configureStudent(Student $student, array $data): array
    {
        // Set birth date
        try {
            $birthDate = new \DateTime($data['dateNaissance']);
            $student->setDateNaissance($birthDate);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Format de date invalide',
                'code' => 'INVALID_DATE_FORMAT'
            ];
        }

        // Handle class
        $classResult = $this->handleStudentClass($student, $data['classe_id']);
        if (!$classResult['success']) {
            return $classResult;
        }

        $student->setNumStudent($data['numStudent'] ?? 'AUTO-' . uniqid());

        return ['success' => true];
    }

    /**
     * Configure teacher-specific properties
     */
    public function configureTeacher(Teacher $teacher, array $data): array
    {
        $teacher->setSpecialite(trim($data['specialite']));
        return ['success' => true];
    }

    /**
     * Configure parent-specific properties
     */
    public function configureParent(ParentUser $parent, array $data): array
    {
        $parent->setProfession(trim($data['profession']));
        $parent->setTelephone($data['telephone'] ?? '');

        // Store children names for future reference/linking
        if (!empty($data['childrenNames']) && is_array($data['childrenNames'])) {
            // For now, we just validate the names
            // The actual linking would happen in a separate process
            foreach ($data['childrenNames'] as $index => $childName) {
                $trimmedName = trim($childName);
                if (empty($trimmedName)) {
                    return [
                        'success' => false,
                        'error' => "Le nom de l'enfant " . ($index + 1) . " est obligatoire",
                        'code' => 'MISSING_CHILD_NAME',
                        'field' => 'childName' . $index
                    ];
                }
            }
        }

        return ['success' => true];
    }

    /**
     * Handle class assignment for students
     */
    private function handleStudentClass(Student $student, string $classIdentifier): array
    {
        try {
            if (is_numeric($classIdentifier)) {
                // It's a class ID
                $classe = $this->entityManager->getRepository(Classe::class)->find($classIdentifier);
                if (!$classe) {
                    return [
                        'success' => false,
                        'error' => 'Classe non trouvée',
                        'code' => 'CLASS_NOT_FOUND'
                    ];
                }
            } else {
                // It's a class name
                $className = trim($classIdentifier);
                $classe = $this->entityManager->getRepository(Classe::class)->findOneBy(['nom' => $className]);
                if (!$classe) {
                    // Create new class
                    $classe = new Classe();
                    $classe->setNom($className);
                    $this->entityManager->persist($classe);
                }
            }

            $student->setClasse($classe);
            return ['success' => true];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de la gestion de la classe',
                'code' => 'CLASS_MANAGEMENT_ERROR'
            ];
        }
    }

    /**
     * Find students by names to link with parent
     * This method can be called after parent registration to link existing children
     */
    public function linkParentToChildren(ParentUser $parent, array $childrenNames): array
    {
        $linkedChildren = [];
        $notFoundChildren = [];

        foreach ($childrenNames as $childName) {
            $trimmedName = trim($childName);
            $nameParts = explode(' ', $trimmedName);
            
            if (count($nameParts) >= 2) {
                $firstname = $nameParts[0];
                $lastname = implode(' ', array_slice($nameParts, 1));
                
                // Try to find student by first and last name
                $student = $this->entityManager->getRepository(Student::class)
                    ->createQueryBuilder('s')
                    ->where('s.firstname = :firstname AND s.lastname = :lastname')
                    ->setParameter('firstname', $firstname)
                    ->setParameter('lastname', $lastname)
                    ->getQuery()
                    ->getOneOrNullResult();
                
                if ($student) {
                    $student->setParent($parent);
                    $linkedChildren[] = $student;
                } else {
                    $notFoundChildren[] = $trimmedName;
                }
            } else {
                $notFoundChildren[] = $trimmedName;
            }
        }

        return [
            'linkedChildren' => $linkedChildren,
            'notFoundChildren' => $notFoundChildren
        ];
    }
}