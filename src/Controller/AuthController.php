<?php
namespace App\Controller;

use App\Entity\BannedUser;
use App\Entity\ServerTeamMember;
use App\Entity\User;
use App\Event\AdminLoginEvent;
use App\Event\AppEvents;
use App\Form\Model\AdminLoginModal;
use App\Form\Type\AdminLoginType;
use App\Security\UserProvider;
use Exception;
use PHPGangsta_GoogleAuthenticator;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

#[Route]
class AuthController extends Controller
{
    const OAUTH2_STATE_KEY = 'oauth2state';
    const OAUTH2_BACK_KEY  = 'oauth2back';

    /**
     * @param Request      $request
     * @param UserProvider $provider
     *
     * @return Response
     */
    #[Route('/login', name: 'login')]
    public function loginAction(Request $request, UserProvider $provider): Response
    {
        // The saved state key will be validated in discordOauth2RedirectAction().
        $url     = $provider->getAuthorizationURL();
        $session = $request->getSession();
        $session->set(self::OAUTH2_STATE_KEY, $url);
        if ($back = $request->query->get('back')) {
            $session->set(self::OAUTH2_BACK_KEY, $back);
        }

        return new RedirectResponse($url);
    }

    /**
     * @param Request               $request
     * @param TokenStorageInterface $tokenStorage
     *
     * @return RedirectResponse
     */
    #[Route('/logout', name: 'logout')]
    public function logoutAction(Request $request, TokenStorageInterface $tokenStorage): RedirectResponse
    {
        $tokenStorage->setToken(null);
        $session = $request->getSession();
        $session->invalidate();
        $session->remove(self::OAUTH2_STATE_KEY);

        return new RedirectResponse('/');
    }

    /**
     * @param Request               $request
     * @param UserProvider          $provider
     * @param TokenStorageInterface $tokenStorage
     *
     * @return RedirectResponse
     */
    #[Route('/discord/oauth2/redirect', name: 'discord_oauth2_redirect')]
    public function discordOauth2RedirectAction(
        Request $request,
        UserProvider $provider,
        TokenStorageInterface $tokenStorage
    ): RedirectResponse {
        // We saved this session value in loginAction(). Ensures
        // the user arrived at this route via the login path.
        $session = $request->getSession();
        if (!$session->get(self::OAUTH2_STATE_KEY) || $request->query->get('error')) {
            $session->remove(self::OAUTH2_STATE_KEY);
            return new RedirectResponse('/');
        }
        $session->remove(self::OAUTH2_STATE_KEY);

        try {
            $code = $request->query->get('code');
            if (!$code) {
                throw new Exception('No access code provided.');
            }

            $user = $provider->createUser($code);
            if (!$user) {
                throw new Exception('User not created.');
            }
        } catch (Exception $e) {
            $this->logger->warning('discordOauth2RedirectAction: ' . $e->getMessage());
            $this->addFlash('danger', 'Unable to authenticate your account.');

            return $this->logoutAction($request, $tokenStorage);
        }

        // Make sure this motha fucka is allowed on the site.
        $isBanned = $this->em->getRepository(BannedUser::class)->isBanned(
            $user->getDiscordUsername(),
            $user->getDiscordDiscriminator()
        );
        if (!$user->isEnabled() || $isBanned) {
            $this->addFlash('danger', 'Your account has been banned.');

            return $this->logoutAction($request, $tokenStorage);
        }

        // Team members may be created by username#discriminator without them having ever
        // visited this site. Associate the authenticated user with the team member now.
        $teamMember = $this->em->getRepository(ServerTeamMember::class)
            ->findByDiscordUsernameAndDiscriminator(
                $user->getDiscordUsername(),
                $user->getDiscordDiscriminator()
            );
        if ($teamMember) {
            if (!$teamMember->getDiscordID()) {
                $teamMember->setDiscordID($user->getDiscordID());
            }
            if (!$teamMember->getDiscordAvatar()) {
                $teamMember->setDiscordAvatar($user->getDiscordAvatar());
            }
            if (!$teamMember->getUser()) {
                $teamMember->setUser($user);
            }
            $this->em->flush();
        }

        // Authenticate with Symfony.
        $this->authenticate($request, $user, $user->getRoles());

        // We're done! Send the user back where they came from or else the home page.
        $session = $this->get('session');
        if ($back = $session->get(self::OAUTH2_BACK_KEY)) {
            return new RedirectResponse($back);
        }

        return new RedirectResponse(
            $this->generateUrl('home_index')
        );
    }

    /**
     * @param Request                        $request
     * @param PHPGangsta_GoogleAuthenticator $googleAuthenticator
     *
     * @return Response
     */
    #[Route('/admin/login', name: 'auth_admin_login')]
    public function adminLogin(Request $request, PHPGangsta_GoogleAuthenticator $googleAuthenticator): Response
    {
        $user  = $this->getUser();
        $modal = new AdminLoginModal();
        $form  = $this->createForm(AdminLoginType::class, $modal);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $valid = $googleAuthenticator->verifyCode(
                $user->getGoogleAuthenticatorSecret(),
                $modal->getCode()
            );
            if ($valid) {
                $this->authenticate($request, $user, [User::ROLE_SUPER_ADMIN]);
                $this->eventDispatcher->dispatch(AppEvents::ADMIN_LOGIN, new AdminLoginEvent($user));

                return new RedirectResponse(
                    $this->generateUrl('easyadmin')
                );
            }

            $form['code']->addError(new FormError('Invalid code.'));
        }

        return $this->render('auth/admin-login.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @param Request $request
     * @param User    $user
     * @param array   $roles
     */
    private function authenticate(Request $request, User $user, array $roles): void
    {
        $token = new UsernamePasswordToken($user, null, 'main', $roles);
        $this->get('security.token_storage')->setToken($token);
        $request->getSession()->set('_security_main', serialize($token));
        $this->eventDispatcher->dispatch(
            'security.interactive_login',
            new InteractiveLoginEvent($request, $token)
        );
    }
}
