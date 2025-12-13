<?php
namespace App\Event;

use App\Entity\Server;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

/**
 * Triggered when a visitor views a server page.
 */
readonly class ViewEvent extends Event
{
    public function __construct(
        private Server $server,
        private Request $request
    ) {
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
