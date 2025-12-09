<?php
namespace App\Controller;

use App\Entity\ServerEvent;
use App\Entity\Server;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(name: 'home_')]
class HomeController extends Controller
{
    const CACHE_LIFETIME = 1800; // 30 minutes

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/', name: 'index')]
    public function indexAction(Request $request): Response
    {
        $emeraldServer = null;
        if ($request->query->getInt('page', 1) === 1) {
            $emeraldServer = $this->em->getRepository(Server::class)
                ->createQueryBuilder('s')
                ->select('s', 'c', 't')
                ->leftJoin('s.categories', 'c')
                ->leftJoin('s.tags', 't')
                ->where('s.premiumStatus = :status')
                ->setParameter(':status', Server::STATUS_EMERALD)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        $query = $this->em->getRepository(Server::class)
            ->createQueryBuilder('s')
            ->select('s', 'c', 't')
            ->leftJoin('s.categories', 'c')
            ->leftJoin('s.tags', 't')
            ->where('s.isEnabled = 1')
            ->andWhere('s.isPublic = 1')
            ->andWhere('s.premiumStatus != :status')
            ->setParameter(':status', Server::STATUS_EMERALD)
            ->orderBy('s.premiumStatus', 'desc')
            ->addOrderBy('s.bumpPoints', 'desc')
            ->addOrderBy('s.dateBumped', 'desc')
            ->getQuery()
            ->useResultCache(true, self::CACHE_LIFETIME);

        return $this->render('home/index.html.twig', [
            'sort'          => 'most-bumped',
            'emeraldServer' => $emeraldServer,
            'servers'       => $this->paginate($query)
        ]);
    }

    /**
     * @return Response
     */
    #[Route('/recently-bumped', name: 'recently_bumped')]
    public function recentlyBumpedAction(): Response
    {
        $query = $this->em->getRepository(Server::class)
            ->createQueryBuilder('s')
            ->select('s', 'c', 't')
            ->leftJoin('s.categories', 'c')
            ->leftJoin('s.tags', 't')
            ->leftJoin(ServerEvent::class, 'e', Join::WITH, 'e.server = s')
            ->where('s.isEnabled = 1')
            ->andWhere('s.isPublic = 1')
            ->andWhere('e.eventType = :eventType')
            ->setParameter(':eventType', ServerEvent::TYPE_BUMP)
            ->orderBy('s.premiumStatus', 'desc')
            ->addOrderBy('e.dateCreated', 'desc')
            ->getQuery()
            ->useResultCache(true, self::CACHE_LIFETIME);

        return $this->render('home/index.html.twig', [
            'sort'    => 'recently-bumped',
            'title'   => 'Recently Bumped',
            'servers' => $this->paginate($query)
        ]);
    }

    /**
     * @return Response
     */
    #[Route('/recently-added', name: 'recently_added')]
    public function recentlyAddedAction(): Response
    {
        $query = $this->em->getRepository(Server::class)
            ->createQueryBuilder('s')
            ->select('s', 'c', 't')
            ->leftJoin('s.categories', 'c')
            ->leftJoin('s.tags', 't')
            ->where('s.isEnabled = 1')
            ->andWhere('s.isPublic = 1')
            ->orderBy('s.id', 'desc')
            ->getQuery()
            ->useResultCache(true, self::CACHE_LIFETIME);

        return $this->render('home/index.html.twig', [
            'sort'    => 'recently-added',
            'title'   => 'Recently Added',
            'servers' => $this->paginate($query)
        ]);
    }

    /**
     * @return Response
     * @throws Exception
     */
    #[Route('/trending', name: 'trending')]
    public function trendingAction(): Response
    {
        $query = $this->em->getRepository(Server::class)
            ->createQueryBuilder('s')
            ->select('s', 'c', 't')
            ->leftJoin('s.categories', 'c')
            ->leftJoin('s.tags', 't')
            ->leftJoin(ServerEvent::class, 'e', Join::WITH, 'e.server = s')
            ->where('s.isEnabled = 1')
            ->andWhere('s.isPublic = 1')
            ->andWhere('e.eventType = :eventType')
            ->setParameter(':eventType', ServerEvent::TYPE_JOIN)
            ->orderBy('e.id', 'desc')
            ->getQuery()
            ->useResultCache(true, self::CACHE_LIFETIME);

        return $this->render('home/index.html.twig', [
            'sort'    => 'trending',
            'title'   => 'Trending',
            'servers' => $this->paginate($query)
        ]);
    }

    /**
     * @return Response
     */
    #[Route('/most-online', name: 'most_online')]
    public function mostOnlineAction(): Response
    {
        $query = $this->em->getRepository(Server::class)
            ->createQueryBuilder('s')
            ->select('s', 'c', 't')
            ->leftJoin('s.categories', 'c')
            ->leftJoin('s.tags', 't')
            ->where('s.isEnabled = 1')
            ->andWhere('s.isPublic = 1')
            ->orderBy('s.membersOnline', 'desc')
            ->addOrderBy('s.bumpPoints', 'desc')
            ->getQuery()
            ->useResultCache(true, self::CACHE_LIFETIME);

        return $this->render('home/index.html.twig', [
            'sort'    => 'most-online',
            'title'   => 'Most Online',
            'servers' => $this->paginate($query)
        ]);
    }

    /**
     * @return Response
     * @throws DBALException
     * @throws NonUniqueResultException
     */
    #[Route('/random', name: 'random')]
    public function randomAction(): Response
    {
        // ORDER BY RAND() is bad, m'kay. This does the trick.
        $stmt = $this->em->getConnection()->prepare('
            SELECT MAX(`id`) FROM `server` WHERE `is_enabled` = 1 AND `is_active` = 1 LIMIT 1
        ');
        $stmt->execute();
        $randID = rand(1, $stmt->fetchColumn(0));

        $server = $this->em->getRepository(Server::class)
            ->createQueryBuilder('s')
            ->where('s.isEnabled = 1')
            ->andWhere('s.isPublic = 1')
            ->andWhere('s.id >= :id')
            ->setParameter(':id', $randID)
            ->orderBy('s.id', 'asc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$server) {
            return new RedirectResponse('/');
        }

        return new RedirectResponse(
            $this->generateUrl('server_index', ['slug' => $server->getSlug()])
        );
    }

    /**
     * @return Response
     */
    #[Route('/privacy', name: 'privacy')]
    public function privacyAction(): Response
    {
        return $this->render('home/privacy.html.twig', [
            'title' => 'Privacy Policy'
        ]);
    }

    /**
     * @return Response
     */
    #[Route('/terms', name: 'terms')]
    public function termsAction(): Response
    {
        return $this->render('home/terms.html.twig', [
            'Terms of Use'
        ]);
    }

    /**
     * @return Response
     */
    #[Route('/source', name: 'source')]
    public function sourceAction(): Response
    {
        return $this->render('home/source.html.twig', [
            'title' => 'Source Code'
        ]);
    }
}
