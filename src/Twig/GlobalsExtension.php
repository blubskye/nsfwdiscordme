<?php
namespace App\Twig;

use App\Repository\CategoryRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;

/**
 * Class GlobalsExtension
 */
class GlobalsExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        protected CategoryRepository $categoryRepository
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'categories' => $this->categoryRepository->findAll()
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('toBool', $this->toBool(...))
        ];
    }

    public function toBool(mixed $value): string
    {
        return $value ? 'true' : 'false';
    }
}
