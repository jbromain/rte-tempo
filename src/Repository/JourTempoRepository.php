<?php

namespace App\Repository;

use App\Entity\JourTempo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
