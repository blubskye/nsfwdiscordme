<?php
namespace App\Security;

use App\Entity\Server;
use App\Entity\ServerTeamMember;
use App\Entity\User;
use App\Enum\ServerTeamRole;
use App\Repository\ServerTeamMemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Provides a method indicating what access a user has to a server.
 */
class ServerAccess implements ServerAccessInterface
{
    public const ROLE_OWNER   = 'owner';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_EDITOR  = 'editor';
    public const ROLE_NONE    = 'none';

    public const ROLES = [
        self::ROLE_OWNER => [
            self::ROLE_OWNER,
            self::ROLE_MANAGER,
            self::ROLE_EDITOR
        ],
        self::ROLE_MANAGER => [
            self::ROLE_MANAGER,
            self::ROLE_EDITOR
        ],
        self::ROLE_EDITOR => [
            self::ROLE_EDITOR
        ]
    ];

    protected ServerTeamMemberRepository $repo;

    public function __construct(
        protected TokenStorageInterface $tokenStorage,
        EntityManagerInterface $em
    ) {
        $this->repo = $em->getRepository(ServerTeamMember::class);
    }

    public function getRoleNames(): array
    {
        return [
            self::ROLE_OWNER,
            self::ROLE_MANAGER,
            self::ROLE_EDITOR,
            self::ROLE_NONE
        ];
    }

    public function can(Server $server, string $role, ?User $user = null): bool
    {
        if (!in_array($role, $this->getRoleNames())) {
            throw new InvalidArgumentException(
                "Server role {$role} is invalid."
            );
        }

        if (!$user) {
            $token = $this->tokenStorage->getToken();
            if (!$token) {
                return false;
            }
            $user = $token->getUser();
        }
        if (!$user instanceof User) {
            return false;
        }

        $teamMember = $this->repo->findByServerAndUser($server, $user);
        if (!$teamMember) {
            return false;
        }

        return $this->containsRole($role, $teamMember->getRole());
    }

    public function containsRole(string $isRole, string $canRole): bool
    {
        return in_array($isRole, self::ROLES[$canRole]);
    }
}
