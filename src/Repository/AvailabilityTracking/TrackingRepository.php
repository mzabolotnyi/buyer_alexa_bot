<?php

namespace App\Repository\AvailabilityTracking;

use App\Entity\AvailabilityTracking\Tracking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Tracking|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tracking|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tracking[]    findAll()
 * @method Tracking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TrackingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tracking::class);
    }

    /**
     * @return Tracking[]
     */
    public function findForTracking(): array
    {
        $qb = $this->createQueryBuilder('tracking')
            ->where('tracking.lastTrackedAt IS NULL OR tracking.lastTrackedAt < :deadline')
            ->setParameter('deadline', (new \DateTime())->modify('-5 minute'));

        return $qb->getQuery()->getResult();
    }
}
