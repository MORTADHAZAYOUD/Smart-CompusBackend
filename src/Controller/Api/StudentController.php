<?php

namespace App\Controller\Api;

use App\Entity\Student;
use App\Entity\Classe;
use App\Entity\ParentUser;
use App\Form\StudentType;
use App\Repository\StudentRepository;
use App\Repository\ClasseRepository;
use App\Repository\ParentUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/student')]
#[IsGranted('ROLE_ADMIN')]
class StudentController extends AbstractController
{
    #[Route('/', name: 'app_student_index', methods: ['GET'])]
    #[OA\Tag(name: 'student')]
    public function index(
        StudentRepository $studentRepository,
        ClasseRepository $classeRepository,
        Request $request
    ): Response {
        $classe = $request->query->get('classe');
        $search = $request->query->get('search');
        
        $queryBuilder = $studentRepository->createQueryBuilder('s')
            ->leftJoin('s.classe', 'c')
            ->leftJoin('s.parent', 'p');
        
        // Filtrage par classe
        if ($classe) {
            $queryBuilder->andWhere('c.id = :classe')
                        ->setParameter('classe', $classe);
        }
        
        // Recherche par nom, prénom ou numéro étudiant
        if ($search) {
            $queryBuilder->andWhere('s.nom LIKE :search OR s.prenom LIKE :search OR s.numStudent LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
        }
        
        $students = $queryBuilder->getQuery()->getResult();
        $classes = $classeRepository->findAll();
        
        return $this->render('student/index.html.twig', [
            'students' => $students,
            'classes' => $classes,
            'current_classe' => $classe,
            'current_search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_student_new', methods: ['GET', 'POST'])]
    #[OA\Tag(name: 'student')]        
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $student = new Student();
        $form = $this->createForm(StudentType::class, $student);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Générer automatiquement le numéro étudiant si non fourni
            if (!$student->getNumStudent()) {
                $student->setNumStudent($this->generateStudentNumber($entityManager));
            }
            
            // Définir le rôle étudiant
            $student->setRoles(['ROLE_STUDENT']);
            
            // Hash du mot de passe (si nécessaire)
            if ($student->getMotDePasse()) {
                $hashedPassword = password_hash($student->getMotDePasse(), PASSWORD_DEFAULT);
                $student->setMotDePasse($hashedPassword);
            }
            
            $entityManager->persist($student);
            $entityManager->flush();

            $this->addFlash('success', 'Étudiant créé avec succès.');
            return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('student/new.html.twig', [
            'student' => $student,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_student_show', methods: ['GET'])]
    #[OA\Tag(name: 'student')]
    #[IsGranted('ROLE_USER')]
    public function show(Student $student): Response
    {
        // Vérifier les droits d'accès selon le rôle
        $user = $this->getUser();
        
        if (!$this->isGranted('ROLE_ADMIN')) {
            // Un enseignant peut voir ses étudiants
            if ($this->isGranted('ROLE_TEACHER')) {
                // Vérifier si l'enseignant enseigne à la classe de cet étudiant
                // Cette logique dépend de votre modèle de données
            }
            // Un parent peut voir uniquement ses enfants
            elseif ($this->isGranted('ROLE_PARENT') && $student->getParent() !== $user) {
                throw $this->createAccessDeniedException();
            }
            // Un étudiant peut voir uniquement son propre profil
            elseif ($this->isGranted('ROLE_STUDENT') && $student !== $user) {
                throw $this->createAccessDeniedException();
            }
        }
        
        return $this->render('student/show.html.twig', [
            'student' => $student,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_student_edit', methods: ['GET', 'POST'])]
    #[OA\Tag(name: 'student')]   
    public function edit(Request $request, Student $student, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(StudentType::class, $student);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash du nouveau mot de passe si modifié
            $newPassword = $form->get('plainPassword')->getData();
            if ($newPassword) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $student->setMotDePasse($hashedPassword);
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'Étudiant modifié avec succès.');
            return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('student/edit.html.twig', [
            'student' => $student,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_student_delete', methods: ['POST'])]
    #[OA\Tag(name: 'student')]
    public function delete(Request $request, Student $student, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$student->getId(), $request->request->get('_token'))) {
            $entityManager->remove($student);
            $entityManager->flush();
            $this->addFlash('success', 'Étudiant supprimé avec succès.');
        }

        return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/classe/{id}', name: 'app_student_by_classe', methods: ['GET'])]
    #[OA\Tag(name: 'student')]
    #[IsGranted('ROLE_TEACHER')]
    public function studentsByClasse(Classe $classe, StudentRepository $studentRepository): Response
    {
        $students = $studentRepository->findBy(['classe' => $classe], ['nom' => 'ASC']);
        
        return $this->render('student/by_classe.html.twig', [
            'students' => $students,
            'classe' => $classe,
        ]);
    }

    #[Route('/{id}/profile', name: 'app_student_profile', methods: ['GET'])]
    #[OA\Tag(name: 'student')]
    #[IsGranted('ROLE_USER')]
    public function profile(Student $student): Response
    {
        // SmartProfile - Fiche élève détaillée
        $user = $this->getUser();
        
        // Contrôle d'accès selon les spécifications
        if (!$this->isGranted('ROLE_ADMIN')) {
            if ($this->isGranted('ROLE_PARENT') && $student->getParent() !== $user) {
                throw $this->createAccessDeniedException();
            }
            if ($this->isGranted('ROLE_STUDENT') && $student !== $user) {
                throw $this->createAccessDeniedException();
            }
        }
        
        return $this->render('student/profile.html.twig', [
            'student' => $student,
        ]);
    }

    #[Route('/search/ajax', name: 'app_student_search_ajax', methods: ['GET'])]
    #[OA\Tag(name: 'student')]
    #[IsGranted('ROLE_TEACHER')]
    public function searchAjax(Request $request, StudentRepository $studentRepository): Response
    {
        $query = $request->query->get('q', '');
        $classeId = $request->query->get('classe');
        
        $queryBuilder = $studentRepository->createQueryBuilder('s')
            ->where('s.nom LIKE :query OR s.prenom LIKE :query OR s.numStudent LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10);
            
        if ($classeId) {
            $queryBuilder->andWhere('s.classe = :classe')
                        ->setParameter('classe', $classeId);
        }
        
        $students = $queryBuilder->getQuery()->getResult();
        
        $results = [];
        foreach ($students as $student) {
            $results[] = [
                'id' => $student->getId(),
                'nom' => $student->getNom(),
                'prenom' => $student->getPrenom(),
                'numStudent' => $student->getNumStudent(),
                'classe' => $student->getClasse() ? $student->getClasse()->getNom() : null,
            ];
        }
        
        return $this->json($results);
    }

    private function generateStudentNumber(EntityManagerInterface $entityManager): string
    {
        $year = date('Y');
        $lastStudent = $entityManager->getRepository(Student::class)
            ->createQueryBuilder('s')
            ->where('s.numStudent LIKE :year')
            ->setParameter('year', $year . '%')
            ->orderBy('s.numStudent', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        
        if ($lastStudent) {
            $lastNumber = (int) substr($lastStudent->getNumStudent(), -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $year . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}