<?php
namespace App\Twig;

use App\Entity\Media;
use App\Media\Exception\AdapterNotFoundException;
use App\Media\WebHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class WebHandlerExtension
 */
class WebHandlerExtension extends AbstractExtension
{
    public function __construct(
        protected WebHandlerInterface $webHandler,
        protected EntityManagerInterface $em
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('webPath', $this->webPath(...))
        ];
    }

    /**
     * @throws AdapterNotFoundException
     */
    public function webPath(Media|string $media): string
    {
        if ($media instanceof Media) {
            return $this->webHandler->getWebURL($media);
        }

        $media = $this->em->getRepository(Media::class)->findByPath($media);
        if (!$media) {
            return '/images/default-banner.jpg';
        }
        return $this->webHandler->getWebURL($media);
    }
}
