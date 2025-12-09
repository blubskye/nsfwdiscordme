<?php
namespace App\Controller;

use App\Entity\Category;
use App\Entity\Server;
use App\Entity\Tag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(name: 'category_')]
class CategoryController extends Controller
{
    /**
     * @param string $slug
     *
     * @return Response
     */
    #[Route('/category/{slug}', name: 'index')]
    public function indexAction($slug): Response
    {
        $category = $this->em->getRepository(Category::class)->findBySlug($slug);
        if (!$category) {
            throw $this->createNotFoundException();
        }

        $query = $this->em->getRepository(Server::class)
            ->createQueryBuilder('s')
            ->leftJoin('s.categories', 'category')
            ->where('s.isEnabled = 1')
            ->andWhere('s.isPublic = 1')
            ->andWhere('category = :category')
            ->setParameter(':category', $category)
            ->orderBy('s.premiumStatus', 'desc')
            ->addOrderBy('s.bumpPoints', 'desc');

        return $this->render('category/index.html.twig', [
            'servers'  => $this->paginate($query),
            'category' => $category,
            'title'    => $category->getName()
        ]);
    }

    /**
     * @param string $tag
     *
     * @return Response
     */
    #[Route('/tag/{tag}', name: 'tag')]
    public function tagAction($tag): Response
    {
        $tag = $this->em->getRepository(Tag::class)->findByName($tag);
        if (!$tag) {
            throw $this->createNotFoundException();
        }

        $query = $this->em->getRepository(Server::class)
            ->createQueryBuilder('s')
            ->leftJoin('s.tags', 'tag')
            ->where('s.isEnabled = 1')
            ->andWhere('s.isPublic = 1')
            ->andWhere('tag = :tag')
            ->setParameter(':tag', $tag)
            ->orderBy('s.premiumStatus', 'desc')
            ->addOrderBy('s.bumpPoints', 'desc');

        return $this->render('category/index.html.twig', [
            'servers' => $this->paginate($query),
            'tag'     => $tag,
            'title'   => $tag->getName()
        ]);
    }

    #[Route('/tags', name: 'tags')]
    public function tagsAction(): Response
    {
        return $this->render('category/tags.html.twig', [
            'tags'  => $this->em->getRepository(Tag::class)->findAll(),
            'title' => 'Tags'
        ]);
    }
}
