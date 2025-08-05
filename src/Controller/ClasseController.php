<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Repository\ClasseRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/classes', name: 'api_classes_')]
class ClasseController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted('ROLE_TEACHER')]
    public function list(ClasseRepository $classeRepository): JsonResponse
    {
        $classes = $classeRepository->findAllWithStudents();

        $classesData = [];
        foreach ($classes as $classe) {
            $classesData[] = $this->serializeClasse($classe);
        }

        return $this->json(['classes' => $classesData]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[IsGranted('ROLE_TEACHER')]
    public function show(int $id, ClasseRepository $classeRepository): JsonResponse
    {
        $classe = $classeRepository->find($id);

        if (!$classe) {
            return $this->json(['error' => 'Classe non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $classeData = $this->serializeClasse($classe, true);

        return $this->json(['classe' => $classeData]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, UserRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['nom'], $data['niveau'])) {
            return $this->json(['error' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
        }

        $classe = new Classe();
        $classe->setNom($data['nom']);
        $classe->setNiveau($data['niveau']);
        $classe->setDescription($data['description'] ?? '');
        $classe->setEffectif(0);

        // Assigner un enseignant si fourni
        if (isset($data['enseignantId']) && $data['enseignantId']) {
            $enseignant = $userRepository->find($data['enseignantId']);
            if ($enseignant && in_array('ROLE_TEACHER', $enseignant->getRoles())) {
                $classe->setEnseignant($enseignant);
            }
        }

        $this->entityManager->persist($classe);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Classe créée avec succès',
            'classe' => $this->serializeClasse($classe)
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(int $id, Request $request, ClasseRepository $classeRepository, UserRepository $userRepository): JsonResponse
    {
        $classe = $classeRepository->find($id);

        if (!$classe) {
            return $this->json(['error' => 'Classe non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) $classe->setNom($data['nom']);
        if (isset($data['niveau'])) $classe->setNiveau($data['niveau']);
        if (isset($data['description'])) $classe->setDescription($data['description']);

        if (isset($data['enseignantId'])) {
            if ($data['enseignantId']) {
                $enseignant = $userRepository->find($data['enseignantId']);
                if ($enseignant && in_array('ROLE_TEACHER', $enseignant->getRoles())) {
                    $classe->setEnseignant($enseignant);
                }
            } else {
                $classe->setEnseignant(null);
            }
        }

        // Mettre à jour l'effectif
        $classe->setEffectif($classe->getEtudiants()->count());

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Classe mise à jour avec succès',
            'classe' => $this->serializeClasse($classe)
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, ClasseRepository $classeRepository): JsonResponse
    {
        $classe = $classeRepository->find($id);

        if (!$classe) {
            return $this->json(['error' => 'Classe non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier qu'il n'y a pas d'étudiants dans la classe
        if ($classe->getEtudiants()->count() > 0) {
            return $this->json([
                'error' => 'Impossible de supprimer une classe qui contient des étudiants'
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->remove($classe);
        $this->entityManager->flush();

        return $this->json(['message' => 'Classe supprimée avec succès']);
    }

    #[Route('/{id}/students', name: 'students', methods: ['GET'])]
    #[IsGranted('ROLE_TEACHER')]
    public function getStudents(int $id, ClasseRepository $classeRepository): JsonResponse
    {
        $classe = $classeRepository->find($id);

        if (!$classe) {
            return $this->json(['error' => 'Classe non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $studentsData = [];
        foreach ($classe->getEtudiants() as $student) {
            $studentsData[] = [
                'id' => $student->getId(),
                'nom' => $student->getNom(),
                'prenom' => $student->getPrenom(),
                'email' => $student->getEmail(),
                'status' => $student->getStatus(),
                'dateCreation' => $student->getDateCreation()?->format('Y-m-d H:i:s')
            ];
        }

        return $this->json([
            'classe' => [
                'id' => $classe->getId(),
                'nom' => $classe->getNom(),
                'niveau' => $classe->getNiveau()
            ],
            'students' => $studentsData
        ]);
    }

    #[Route('/{id}/students/{studentId}', name: 'add_student', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function addStudent(int $id, int $studentId, ClasseRepository $classeRepository, UserRepository $userRepository): JsonResponse
    {
        $classe = $classeRepository->find($id);
        $student = $userRepository->find($studentId);

        if (!$classe) {
            return $this->json(['error' => 'Classe non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$student) {
            return $this->json(['error' => 'Étudiant non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if (!in_array('ROLE_STUDENT', $student->getRoles())) {
            return $this->json(['error' => 'L\'utilisateur n\'est pas un étudiant'], Response::HTTP_BAD_REQUEST);
        }

        if ($student->getClasse()) {
            return $this->json(['error' => 'L\'étudiant est déjà dans une classe'], Response::HTTP_BAD_REQUEST);
        }

        $student->setClasse($classe);
        $classe->setEffectif($classe->getEtudiants()->count() + 1);

        $this->entityManager->flush();

        return $this->json(['message' => 'Étudiant ajouté à la classe avec succès']);
    }

    #[Route('/{id}/students/{studentId}', name: 'remove_student', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function removeStudent(int $id, int $studentId, ClasseRepository $classeRepository, UserRepository $userRepository): JsonResponse
    {
        $classe = $classeRepository->find($id);
        $student = $userRepository->find($studentId);

        if (!$classe) {
            return $this->json(['error' => 'Classe non trouvée'], Response::HTTP_NOT_FOUND);
        }

        if (!$student) {
            return $this->json(['error' => 'Étudiant non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if ($student->getClasse() !== $classe) {
            return $this->json(['error' => 'L\'étudiant n\'est pas dans cette classe'], Response::HTTP_BAD_REQUEST);
        }

        $student->setClasse(null);
        $classe->setEffectif($classe->getEtudiants()->count() - 1);

        $this->entityManager->flush();

        return $this->json(['message' => 'Étudiant retiré de la classe avec succès']);
    }

    #[Route('/{id}/statistics', name: 'statistics', methods: ['GET'])]
    #[IsGranted('ROLE_TEACHER')]
    public function getStatistics(int $id, ClasseRepository $classeRepository): JsonResponse
    {
        $classe = $classeRepository->find($id);

        if (!$classe) {
            return $this->json(['error' => 'Classe non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $totalStudents = $classe->getEtudiants()->count();
        $activeStudents = 0;
        $inactiveStudents = 0;

        foreach ($classe->getEtudiants() as $student) {
            if ($student->getStatus() === 'active') {
                $activeStudents++;
            } else {
                $inactiveStudents++;
            }
        }

        $totalSeances = $classe->getSeances()->count();

        return $this->json([
            'classe' => [
                'id' => $classe->getId(),
                'nom' => $classe->getNom(),
                'niveau' => $classe->getNiveau()
            ],
            'statistics' => [
                'totalStudents' => $totalStudents,
                'activeStudents' => $activeStudents,
                'inactiveStudents' => $inactiveStudents,
                'totalSeances' => $totalSeances
            ]
        ]);
    }

    private function serializeClasse(Classe $classe, bool $includeDetails = false): array
    {
        $data = [
            'id' => $classe->getId(),
            'nom' => $classe->getNom(),
            'niveau' => $classe->getNiveau(),
            'description' => $classe->getDescription(),
            'effectif' => $classe->getEffectif(),
            'enseignant' => $classe->getEnseignant() ? [
                'id' => $classe->getEnseignant()->getId(),
                'nom' => $classe->getEnseignant()->getNom(),
                'prenom' => $classe->getEnseignant()->getPrenom(),
                'email' => $classe->getEnseignant()->getEmail()
            ] : null
        ];

        if ($includeDetails) {
            $data['etudiants'] = [];
            foreach ($classe->getEtudiants() as $etudiant) {
                $data['etudiants'][] = [
                    'id' => $etudiant->getId(),
                    'nom' => $etudiant->getNom(),
                    'prenom' => $etudiant->getPrenom(),
                    'email' => $etudiant->getEmail(),
                    'status' => $etudiant->getStatus()
                ];
            }

            $data['seances'] = [];
            foreach ($classe->getSeances() as $seance) {
                $data['seances'][] = [
                    'id' => $seance->getId(),
                    'titre' => $seance->getTitre(),
                    'dateDebut' => $seance->getDateDebut()?->format('Y-m-d H:i:s'),
                    'dateFin' => $seance->getDateFin()?->format('Y-m-d H:i:s'),
                    'mode' => $seance->getMode()
                ];
            }
        }

        return $data;
    }
}