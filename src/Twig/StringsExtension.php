<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class StringsExtension
 */
class StringsExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('ucwords', ucwords(...))
        ];
    }
}
