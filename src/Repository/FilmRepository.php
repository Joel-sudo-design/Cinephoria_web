<?php

namespace App\Repository;

use App\Entity\Film;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Film>
 */
class FilmRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Film::class);
    }

        /**
         * @return Film[] Returns an array of Film objects
         */

    public function findByDate($value): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere(':val BETWEEN f.date_debut AND f.date_fin')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByFilters($cinemaId, $genreId, $date)
    {
        // Créez le QueryBuilder pour la requête
        $queryBuilder = $this->createQueryBuilder('f')
            ->leftJoin('f.cinema', 'c')  // Jointure avec la table de cinéma (relation ManyToMany)
            ->leftJoin('f.genre', 'g');    // Jointure avec l'entité Genre (relation ManyToOne)

        // Filtrage par cinéma si l'ID est fourni
        if ($cinemaId) {
            $queryBuilder->andWhere('c.id = :cinemaId')
                ->setParameter('cinemaId', $cinemaId);
        }

        // Filtrage par genre si l'ID est fourni
        if ($genreId) {
            $queryBuilder->andWhere('g.id = :genreId')
                ->setParameter('genreId', $genreId);
        }

        // Filtrage par date si la date est fournie
        if ($date) {
            $queryBuilder->andWhere(':date BETWEEN f.date_debut AND f.date_fin')
                ->setParameter('date', $date);
        }

        // Exécuter la requête et retourner les résultats
        return $queryBuilder->getQuery()->getResult();
    }

    public function findFilmsWithLastWednesdayBetweenDates(\DateTime $lastWednesday): array
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->where(':lastWednesday = f.date_debut')
            ->orWhere(':lastWednesday = f.date_fin')
            ->orWhere(':lastWednesday BETWEEN f.date_debut AND f.date_fin')
            ->setParameter('lastWednesday', $lastWednesday->format('Y-m-d'))
            ->getQuery();

        return $queryBuilder->getResult();
    }

    //    public function findOneBySomeField($value): ?Film
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
