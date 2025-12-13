<?php
namespace App\Event;

use App\Admin\LoggableEntityInterface;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Triggered when an admin logs in.
 */
readonly class AdminLoginEvent extends Event implements LoggableEntityInterface
{
    public function __construct(
        private User $user
    ) {
    }

    public function getLoggableMessage(): string
    {
        return sprintf('at %s', date('Y-m-d H:i:s'));
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
