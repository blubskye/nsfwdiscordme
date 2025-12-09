<?php
namespace App\Controller;

use Elastica\Query;
use Elastica\Query\QueryString;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(name: 'search_')]
class SearchController extends Controller
{
    const ORDER_FIELDS = [
        'bumpPoints'
    ];

    /**
     * @var PaginatedFinderInterface
     */
    protected $serverFinder;

    /**
     * @param PaginatedFinderInterface $serverFinder
     */
    public function setServerFinder(PaginatedFinderInterface $serverFinder)
    {
        $this->serverFinder = $serverFinder;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/search', name: 'index')]
    public function indexAction(Request $request): Response
    {
        $searchTerm = trim($request->query->get('q', ''));
        $orderField = trim($request->query->get('order', 'bumpPoints'));
        if (!$searchTerm || !in_array($orderField, self::ORDER_FIELDS)) {
            throw $this->createNotFoundException();
        }

        $query = new Query();
        $query->addSort([
            'premiumStatus' => [
                'order' => 'desc'
            ],
            $orderField => [
                'order' => 'desc'
            ]
        ]);
        $query->setQuery(new QueryString($searchTerm));
        $query = $this->serverFinder->createPaginatorAdapter($query);

        return $this->render('search/index.html.twig', [
            'servers'    => $this->paginate($query),
            'searchTerm' => $searchTerm,
            'title'      => $searchTerm
        ]);
    }
}
