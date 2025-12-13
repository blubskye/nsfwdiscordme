<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class FontAwesomeExtension
 */
class FontAwesomeExtension extends AbstractExtension
{
    /**
     * Icon names which are aliases to real icon names. An optional
     * css class can be specified by separating the real icon name
     * by the classes, i.e. "gem far" and "circle fa online".
     */
    public const ICON_ALIASES = [
        'app-bump'            => 'arrow-alt-circle-up',
        'app-recently-bumped' => 'burn',
        'app-upgrade'         => 'gem far',
        'app-stats'           => 'chart-bar',
        'app-settings'        => 'cog',
        'app-team'            => 'users',
        'app-trending'        => 'chart-line',
        'app-online'          => 'circle fa server-icon-online',
        'app-most-online'     => 'user',
        'app-random'          => 'random',
        'app-delete'          => 'trash-alt',
        'app-join'            => 'sign-in-alt',
        'app-ruby'            => 'gem',
        'app-topaz'           => 'gem',
        'app-emerald'         => 'gem',
        'app-invoices'        => 'file-invoice-dollar'
    ];

    public function getFunctions(): array
    {
        return [
            new TwigFunction('icon', $this->icon(...), ['is_safe' => ['html']])
        ];
    }

    /**
     * Returns the html for a Font Awesome icon
     */
    public function icon(string $id, string $classes = "fa", string $title = ""): string
    {
        if ($title) {
            $title = htmlspecialchars($title, ENT_HTML5 | ENT_QUOTES);
            $title = " title=\"{$title}\"";
        }

        if (isset(self::ICON_ALIASES[$id])) {
            $parts = explode(' ', self::ICON_ALIASES[$id], 2);
            $id = array_shift($parts);
            if ($parts) {
                $classes = $parts[0];
            }
        }

        return sprintf('<i class="icon %s fa-%s"%s></i>', $classes, $id, $title);
    }
}
