<?php
namespace App\Event;

use App\Entity\Server;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Triggered when an action is performed on a server by a team member.
 */
readonly class ServerActionEvent extends Event
{
    public function __construct(
        private Server $server,
        private ?User $user,
        private string $action
    ) {
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getAction(): string
    {
        return $this->action;
    }
}
