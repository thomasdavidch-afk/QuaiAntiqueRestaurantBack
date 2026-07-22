<?php

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 *
 * @method Booking|null find($id, $lockMode = null, $lockVersion = null)
 * @method Booking|null findOneBy(array $criteria, array $orderBy = null)
 * @method Booking[]    findAll()
 * @method Booking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * Retourne toutes les réservations pour une date donnée (entre 00:00:00 et 23:59:59)
     * Utile pour vérifier la limite de 50 couverts par service.
     * 
     * @param string $dateString Date au format 'YYYY-MM-DD'
     * @return Booking[]
     */
    public function findByDate(string $dateString): array
    {
        // On crée des objets DateTime pour le début et la fin de la journée
        $start = new \DateTime($dateString . ' 00:00:00');
        $end = new \DateTime($dateString . ' 23:59:59');

        return $this->createQueryBuilder('b')
            ->andWhere('b.bookingAt >= :start')
            ->andWhere('b.bookingAt <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Booking[] Returns an array of Booking objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Booking
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}