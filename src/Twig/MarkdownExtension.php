<?php
namespace App\Twig;

use App\Media\MarkdownParser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class MarkdownExtension
 */
class MarkdownExtension extends AbstractExtension
{
    protected MarkdownParser $parsedown;

    public function __construct()
    {
        $this->parsedown = new MarkdownParser();
        $this->parsedown->setSafeMode(true);
        $this->parsedown->setBreaksEnabled(true);
        $this->parsedown->setUrlsLinked(false);
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('markdown', $this->parsedown->text(...), ['is_safe' => ['html']])
        ];
    }
}
