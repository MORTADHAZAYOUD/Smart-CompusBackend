<?php

namespace App\Repository;

use App\Entity\Matiere;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Matiere>
 *
 * @method Matiere|null find($id, $lockMode = null, $lockVersion = null)
 * @method Matiere|null findOneBy(array $criteria, array $orderBy = null)
 * @method Matiere[]    findAll()
 * @method Matiere[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MatiereRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Matiere::class);
    }

    // Exemple de méthode personnalisée
    public function findByNom(string $nom): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.nom LIKE :nom')
            ->setParameter('nom', '%' . $nom . '%')
            ->getQuery()
            ->getResult();
    }

    // (Optionnel) Ajouter plus tard si tu relis Matière à Séance ou Note
    // public function findSeancesByMatiere(Matiere $matiere) {...}
    // public function findNotesByMatiere(Matiere $matiere) {...}
}
