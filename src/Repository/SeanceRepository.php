<?php

namespace App\Repository;

use App\Entity\Seance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Seance>
 */
class SeanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Seance::class);
    }

        /**
         * @return Seance[] Returns an array of Seance objects
         */
        public function findByFilmIdByDate($film_id, $date_debut): array
        {
            return $this->createQueryBuilder('s')
                ->andWhere('s.date = :val')
                ->setParameter('val', $date_debut)
                ->andWhere('s.film = :film')
                ->setParameter('film', $film_id)
                ->orderBy('s.salle', 'ASC')
                ->getQuery()
                ->getResult()
            ;
        }

    //    public function findOneBySomeField($value): ?Seance
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
