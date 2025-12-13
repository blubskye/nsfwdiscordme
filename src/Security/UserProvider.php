<?php
namespace App\Security;

use App\Entity\AccessToken;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Wohali\OAuth2\Client\Provider\Discord;

/**
 * Connects the Discord oauth with the Symfony security component.
 */
class UserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly Discord $discord,
        private readonly EntityManagerInterface $em
    ) {
    }

    public function getAuthorizationURL(): string
    {
        return $this->discord->getAuthorizationUrl();
    }

    /**
     * @throws Exception
     * @throws IdentityProviderException
     */
    public function createUser(string $authorizationCode): User
    {
        /** @var \League\OAuth2\Client\Token\AccessToken $token */
        $token = $this->discord->getAccessToken('authorization_code', [
            'code' => $authorizationCode
        ]);
        if (!$token) {
            throw new Exception(
                'Could not get token from Discord.'
            );
        }

        $owner = $this->discord->getResourceOwner($token);
        if (!$owner) {
            throw new Exception(
                'Could not get resource owner from Discord.'
            );
        }

        $owner = $owner->toArray();
        if (empty($owner['id'])) {
            throw new Exception(
                'Invalid resource owner from Discord.'
            );
        }

        $user = $this->em->getRepository(User::class)
            ->findByDiscordID($owner['id']);
        if (!$user) {
            $accessToken = new AccessToken();
            $user = new User();
            $user
                ->setIsEnabled(true)
                ->setDateLastLogin(new DateTime())
                ->setDiscordID($owner['id'])
                ->setDiscordUsername($owner['username'])
                ->setDiscordEmail($owner['email'])
                ->setDiscordAvatar($owner['avatar'])
                ->setDiscordDiscriminator($owner['discriminator'])
                ->setDiscordAvatar($owner['avatar']);
            $this->em->persist($user);
        } else {
            $user->setDateLastLogin(new DateTime());
            $accessToken = $user->getDiscordAccessToken();
            if (!$accessToken) {
                $accessToken = new AccessToken();
            }
        }

        $values = $token->getValues();
        $expires = (new DateTime())->setTimestamp($token->getExpires());
        $accessToken
            ->setUser($user)
            ->setToken($token->getToken())
            ->setRefreshToken($token->getRefreshToken())
            ->setDateExpires($expires)
            ->setScope($values['scope'])
            ->setType($values['token_type']);
        $user->setDiscordAccessToken($accessToken);
        $this->em->persist($accessToken);

        $this->em->flush();

        return $user;
    }

    /**
     * Loads the user for the given identifier (replaces loadUserByUsername in Symfony 8.0).
     *
     * @throws UserNotFoundException
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        throw new UserNotFoundException();
    }

    /**
     * Refreshes the user.
     *
     * @throws IdentityProviderException
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!($user instanceof User)) {
            throw new UserNotFoundException();
        }

        $accessToken = $user->getDiscordAccessToken();
        if (!$accessToken) {
            throw new UserNotFoundException();
        }
        if (!$accessToken->isExpired()) {
            return $this->findUser($user->getId());
        }

        $newAccessToken = $this->discord->getAccessToken('refresh_token', [
            'refresh_token' => $accessToken->getRefreshToken()
        ]);
        if ($newAccessToken) {
            $values = $newAccessToken->getValues();
            $dateExpires = (new DateTime())->setTimestamp($newAccessToken->getExpires());
            $accessToken
                ->setToken($newAccessToken->getToken())
                ->setRefreshToken($newAccessToken->getRefreshToken())
                ->setDateExpires($dateExpires)
                ->setScope($values['scope'])
                ->setType($values['token_type']);
            $this->em->flush();

            return $this->findUser($user->getId());
        }

        throw new UserNotFoundException();
    }

    /**
     * Whether this provider supports the given user class.
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }

    private function findUser(int $id): User
    {
        return $this->em->getRepository(User::class)->findByID($id);
    }
}
