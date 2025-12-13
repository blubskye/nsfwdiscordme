<?php
namespace App\Controller;

use Elastica\Query;
use Elastica\Query\MultiMatch;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(name: 'search_')]
class SearchController extends Controller
{
    public const ORDER_FIELDS = [
        'bumpPoints'
    ];

    /**
     * Maximum allowed search term length to prevent DoS attacks.
     */
    private const MAX_SEARCH_LENGTH = 200;

    /**
     * Fields to search in - explicitly defined to prevent injection.
     */
    private const SEARCHABLE_FIELDS = [
        'name^3',        // Server name (boosted)
        'description^2', // Description (boosted)
        'tags',          // Tags
    ];

    protected ?PaginatedFinderInterface $serverFinder = null;

    public function setServerFinder(PaginatedFinderInterface $serverFinder): void
    {
        $this->serverFinder = $serverFinder;
    }

    #[Route('/search', name: 'index')]
    public function indexAction(Request $request): Response
    {
        $searchTerm = $this->sanitizeSearchTerm($request->query->get('q', ''));
        $orderField = trim($request->query->get('order', 'bumpPoints'));

        if (!$searchTerm || !in_array($orderField, self::ORDER_FIELDS, true)) {
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

        // Security: Use MultiMatch instead of QueryString to prevent injection.
        // QueryString allows Lucene query syntax which can be exploited.
        // MultiMatch safely searches across specified fields without parsing special syntax.
        $multiMatch = new MultiMatch();
        $multiMatch->setQuery($searchTerm);
        $multiMatch->setFields(self::SEARCHABLE_FIELDS);
        $multiMatch->setType(MultiMatch::TYPE_BEST_FIELDS);
        $multiMatch->setFuzziness('AUTO');

        $query->setQuery($multiMatch);
        $query = $this->serverFinder->createPaginatorAdapter($query);

        return $this->render('search/index.html.twig', [
            'servers'    => $this->paginate($query),
            'searchTerm' => $searchTerm,
            'title'      => $searchTerm
        ]);
    }

    /**
     * Sanitizes search input to prevent ElasticSearch injection attacks.
     * Removes/escapes special characters that have meaning in query syntax.
     */
    private function sanitizeSearchTerm(string $term): string
    {
        // Trim whitespace
        $term = trim($term);

        // Enforce maximum length to prevent DoS
        if (mb_strlen($term) > self::MAX_SEARCH_LENGTH) {
            $term = mb_substr($term, 0, self::MAX_SEARCH_LENGTH);
        }

        // Remove null bytes and other control characters
        $term = preg_replace('/[\x00-\x1F\x7F]/u', '', $term);

        return $term;
    }
}
