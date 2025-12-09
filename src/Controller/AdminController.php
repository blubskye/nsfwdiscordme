<?php
namespace App\Controller;

use App\Entity\Server;
use App\Entity\ServerEvent;
use Symfony\Component\HttpFoundation\Request;
use DateInterval;
use DateTime;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;
use Elastica\Aggregation\DateHistogram;
use Elastica\Query;
use Exception;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use FOS\ElasticaBundle\Paginator\FantaPaginatorAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends EasyAdminController
{
    /**
     * @var PaginatedFinderInterface
     */
    protected $eventsFinder;

    /**
     * @var PaginatedFinderInterface
     */
    protected $serverFinder;

    /**
     * @param PaginatedFinderInterface $eventsFinder
     *
     * @return $this
     */
    public function setEventsFinder(PaginatedFinderInterface $eventsFinder)
    {
        $this->eventsFinder = $eventsFinder;

        return $this;
    }

    /**
     * @param PaginatedFinderInterface $serverFinder
     *
     * @return $this
     */
    public function setServerFinder(PaginatedFinderInterface $serverFinder)
    {
        $this->serverFinder = $serverFinder;

        return $this;
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/stats', name: 'admin_stats')]
    public function statsAction(Request $request): Response
    {
        if ($request->getMethod() === 'POST') {
            /** @var FantaPaginatorAdapter $adapter */
            $query   = $this->createServerEventQuery(ServerEvent::TYPE_JOIN);
            $results = $this->eventsFinder->findPaginated($query);
            $joins   = $this->generateStatsFromResults($results);

            $query   = $this->createServerEventQuery(ServerEvent::TYPE_VIEW);
            $results = $this->eventsFinder->findPaginated($query);
            $views   = $this->generateStatsFromResults($results);

            $query   = $this->createServerEventQuery(ServerEvent::TYPE_BUMP);
            $results = $this->eventsFinder->findPaginated($query);
            $bumps   = $this->generateStatsFromResults($results);

            $query = new Query();
            $query->setSize(0);
            $query->addAggregation(new DateHistogram('hits', 'dateCreated', 'day'));
            $results = $this->serverFinder->findPaginated($query);
            $added   = $this->generateStatsFromResults($results);

            return new JsonResponse([
                'message' => 'ok',
                'joins'   => $joins,
                'views'   => $views,
                'bumps'   => $bumps,
                'added'   => $added
            ]);
        }

        $eventRepo  = $this->getDoctrine()->getRepository(ServerEvent::class);
        $serverRepo = $this->getDoctrine()->getRepository(Server::class);

        $todayBumped = $eventRepo->createQueryBuilder('e')
            ->select('COUNT(e)')
            ->where('e.dateCreated >= :dayAgo')
            ->andWhere('e.eventType = :eventType')
            ->setParameter(':dayAgo', new DateTime('24 hours ago'))
            ->setParameter(':eventType', ServerEvent::TYPE_BUMP)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();

        $todayJoined = $eventRepo->createQueryBuilder('e')
            ->select('COUNT(e)')
            ->where('e.dateCreated >= :dayAgo')
            ->andWhere('e.eventType = :eventType')
            ->setParameter(':dayAgo', new DateTime('24 hours ago'))
            ->setParameter(':eventType', ServerEvent::TYPE_JOIN)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();

        $todayViewed = $eventRepo->createQueryBuilder('e')
            ->select('COUNT(e)')
            ->where('e.dateCreated >= :dayAgo')
            ->andWhere('e.eventType = :eventType')
            ->setParameter(':dayAgo', new DateTime('24 hours ago'))
            ->setParameter(':eventType', ServerEvent::TYPE_VIEW)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();

        $todayAdded = $serverRepo->createQueryBuilder('e')
            ->select('COUNT(e)')
            ->where('e.dateCreated >= :dayAgo')
            ->setParameter(':dayAgo', new DateTime('24 hours ago'))
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/stats.html.twig', [
            'todayBumped' => $todayBumped,
            'todayJoined' => $todayJoined,
            'todayViewed' => $todayViewed,
            'todayAdded'  => $todayAdded
        ]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/bumps', name: 'admin_bumps')]
    public function bumpsAction(Request $request): Response
    {
        if ($request->getMethod() === 'POST') {
            switch($request->request->get('action')) {
                case 'reset':
                    $this->resetBumpPoints();
                    $this->addFlash('success', 'Bump points have been reset.');

                    // Redirect to ensure the form isn't accidentally resubmitted.
                    return new RedirectResponse($this->generateUrl('admin_bumps'));
                    break;
            }
        }

        return $this->render('admin/bumps.html.twig');
    }

    /**
     * @param int $eventType
     *
     * @return Query
     */
    private function createServerEventQuery($eventType): Query
    {
        $query = new Query();
        $query->setSize(0);
        $bool = new Query\BoolQuery();
        $bool->addMust(new Query\Term([
            'eventType' => $eventType
        ]));
        $query->setQuery($bool);
        $query->addAggregation(new DateHistogram('hits', 'dateCreated', 'day'));

        return $query;
    }

    /**
     * @param mixed $results
     *
     * @return array
     * @throws Exception
     */
    private function generateStatsFromResults($results): array
    {
        /** @var FantaPaginatorAdapter $adapter */
        $adapter = $results->getAdapter();
        $buckets = $adapter->getAggregations()['hits']['buckets'];

        $rows = [];
        foreach($buckets as $bucket) {
            $day = (new DateTime($bucket['key_as_string']))->format('Y-m-d');
            $rows[$day] = $bucket['doc_count'];
        }

        $final = [];
        $now   = new DateTime('30 days ago');
        $int   = new DateInterval('P1D');
        for($i = 30; $i > 0; $i--) {
            $day = $now->add($int)->format('Y-m-d');
            if (isset($rows[$day])) {
                $final[] = [
                    'day'   => $day,
                    'count' => $rows[$day]
                ];
            } else {
                $final[] = [
                    'day'   => $day,
                    'count' => 0
                ];
            }
        }

        return $final;
    }

    /**
     *
     */
    private function resetBumpPoints(): void
    {
        $serverRepo = $this->getDoctrine()->getRepository(Server::class);
        foreach($serverRepo->findAll() as $server) {
            $server->setBumpPoints(0);
        }

        $this->getDoctrine()->getManager()->flush();
    }
}
