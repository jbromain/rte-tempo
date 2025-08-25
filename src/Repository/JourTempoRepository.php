<?php

namespace App\Repository;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Entity\JourTempo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;
use PhpParser\Builder\Param;

/**
 * @extends ServiceEntityRepository<JourTempo>
 *
 * @method JourTempo|null find($id, $lockMode = null, $lockVersion = null)
 * @method JourTempo|null findOneBy(array $criteria, array $orderBy = null)
 * @method JourTempo[]    findAll()
 * @method JourTempo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JourTempoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JourTempo::class);
    }

    public function getNombreJoursRougesPlacesJusqua(string $libPeriode, string $dateJour): int {
        return $this->createQueryBuilder('j')
            ->select('count(j.id)')
            ->andWhere('j.periode = :periode')
            ->andWhere('j.dateJour <= :dateJour')
            ->andWhere('j.codeJour = :codeRouge')
            ->setParameter('periode', $libPeriode, ParameterType::STRING)
            ->setParameter('dateJour', $dateJour, ParameterType::STRING)
            ->setParameter('codeRouge', TARIF_ROUGE, ParameterType::INTEGER)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getNombreJoursBlancsPlacesJusqua(string $libPeriode, string $dateJour): int {
        return $this->createQueryBuilder('j')
            ->select('count(j.id)')
            ->andWhere('j.periode = :periode')
            ->andWhere('j.dateJour <= :dateJour')
            ->andWhere('j.codeJour = :codeBlanc')
            ->setParameter('periode', $libPeriode, ParameterType::STRING)
            ->setParameter('dateJour', $dateJour, ParameterType::STRING)
            ->setParameter('codeBlanc', TARIF_BLANC, ParameterType::INTEGER)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getNombreJoursBleusPlacesJusqua(string $libPeriode, string $dateJour): int {
        return $this->createQueryBuilder('j')
            ->select('count(j.id)')
            ->andWhere('j.periode = :periode')
            ->andWhere('j.dateJour <= :dateJour')
            ->andWhere('j.codeJour = :codeBleu')
            ->setParameter('periode', $libPeriode, ParameterType::STRING)
            ->setParameter('dateJour', $dateJour, ParameterType::STRING)
            ->setParameter('codeBleu', TARIF_BLEU, ParameterType::INTEGER)
            ->getQuery()
            ->getSingleScalarResult();
    }


//    /**
//     * @return JourTempo[] Returns an array of JourTempo objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('j.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?JourTempo
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
