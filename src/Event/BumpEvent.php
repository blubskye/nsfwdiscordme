<?php
namespace App\Event;

use App\Entity\Server;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

/**
 * Triggered when a server is bumped.
 */
readonly class BumpEvent extends Event
{
    public function __construct(
        private Server $server,
        private User $user,
        private Request $request
    ) {
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
