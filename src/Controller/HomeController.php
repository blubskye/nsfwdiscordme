<?php
namespace App\Controller;

use App\Entity\ServerEvent;
use App\Entity\Server;
use App\Http\Request;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(name="home_")
 */
class HomeController extends Controller
{
    const CACHE_LIFETIME = 1800; // 30 minutes

    /**
     * @Route("/", name="index")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $emeraldServer = null;
        if ($request->query->getInt('page', 1) === 1) {
            $emeraldServer = $this->em->getRepository(Server::class)
                ->findOneBy(['premiumStatus' => Server::STATUS_EMERALD]);
        }

        $query = $this->em->getRepository(Server::class)
            ->createQueryBuilder('s')
            ->where('s.isEnabled = 1')
            ->andWhere('s.isPublic = 1')
            ->andWhere('s.premiumStatus != :status')
            ->setParameter(':status', Server::STATUS_EMERALD)
            ->orderBy('s.premiumStatus', 'desc')
            ->addOrderBy('s.bumpPoints', 'desc')
            ->addOrderBy('s.dateBumped', 'desc')
            ->getQuery();

        return $this->render('home/index.html.twig', [
            'sort'          => 'most-bumped',
            'emeraldServer' => $emeraldServer,
            'servers'       => $this->paginate($query)
        ]);
    }

    /**
     * @Route("/recently-bumped", name="recently_bumped")
     *
     * @return Response
     */
    public function recentlyBumpedAction()
    {
        $query = $this->em->getRepository(Server::class)
            ->createQueryBuilder('s')
            ->leftJoin(ServerEvent::class, 'e', Join::WITH, 'e.server = s')
            ->where('s.isEnabled = 1')
            ->andWhere('s.isPublic = 1')
            ->andWhere('e.eventType = :eventType')
            ->setParameter(':eventType', ServerEvent::TYPE_BUMP)
            ->orderBy('s.premiumStatus', 'desc')
            ->addOrderBy('e.dateCreated', 'desc')
            ->getQuery();

        return $this->render('home/index.html.twig', [
            'sort'    => 'recently-bumped',
            'title'   => 'Recently Bumped',
            'servers' => $this->paginate($query)
        ]);
    }

    /**
     * @Route("/recently-added", name="recently_added")
     *
     * @return Response
     */
    public function recentlyAddedAction()
    {
        $query = $this->em->getRepository(Server::class)
            ->createQueryBuilder('s')
            ->where('s.isEnabled = 1')
            ->andWhere('s.isPublic = 1')
            ->orderBy('s.id', 'desc')
            ->getQuery();

        return $this->render('home/index.html.twig', [
            'sort'    => 'recently-added',
            'title'   => 'Recently Added',
            'servers' => $this->paginate($query)
        ]);
    }

    /**
     * @Route("/trending", name="trending")
     *
     * @return Response
     * @throws Exception
     */
    public function trendingAction()
    {
        $query = $this->em->getRepository(Server::class)
            ->createQueryBuilder('s')
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
     * @Route("/most-online", name="most_online")
     *
     * @return Response
     */
    public function mostOnlineAction()
    {
        $query = $this->em->getRepository(Server::class)
            ->createQueryBuilder('s')
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
     * @Route("/random", name="random")
     *
     * @return Response
     * @throws DBALException
     * @throws NonUniqueResultException
     */
    public function randomAction()
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
     * @Route("/privacy", name="privacy")
     *
     * @return Response
     */
    public function privacyAction()
    {
        return $this->render('home/privacy.html.twig', [
            'title' => 'Privacy Policy'
        ]);
    }

    /**
     * @Route("/terms", name="terms")
     *
     * @return Response
     */
    public function termsAction()
    {
        return $this->render('home/terms.html.twig', [
            'Terms of Use'
        ]);
    }

    /**
     * @Route("/source", name="source")
     *
     * @return Response
     */
    public function sourceAction()
    {
        return $this->render('home/source.html.twig', [
            'title' => 'Source Code'
        ]);
    }
}
