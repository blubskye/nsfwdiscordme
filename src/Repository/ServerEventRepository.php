<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\ServerEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServerEvent>
 */
class ServerEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServerEvent::class);
    }

    public function findByID(int|string $id): ?ServerEvent
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findLastByEvent(int $event): ?ServerEvent
    {
        return $this->createQueryBuilder('e')
            ->where('e.eventType = :event')
            ->setParameter('event', $event)
            ->setMaxResults(1)
            ->orderBy('e.id', 'desc')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Batch fetch last bump events for multiple servers to avoid N+1 queries.
     *
     * @param array<int> $serverIds
     * @return array<int, ServerEvent|null> Indexed by server ID
     */
    public function findLastBumpEventsForServers(array $serverIds): array
    {
        if (empty($serverIds)) {
            return [];
        }

        // Get all bump events for these servers, ordered by ID desc
        $events = $this->createQueryBuilder('e')
            ->where('e.server IN (:serverIds)')
            ->andWhere('e.eventType = :eventType')
            ->setParameter('serverIds', $serverIds)
            ->setParameter('eventType', ServerEvent::TYPE_BUMP)
            ->orderBy('e.id', 'desc')
            ->getQuery()
            ->getResult();

        // Index by server ID, keeping only the most recent (first) for each server
        $result = [];
        foreach ($events as $event) {
            $serverId = $event->getServer()->getId();
            if (!isset($result[$serverId])) {
                $result[$serverId] = $event;
            }
        }

        return $result;
    }
}
