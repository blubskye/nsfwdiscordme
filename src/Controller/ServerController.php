<?php
namespace App\Controller;

use App\Entity\BannedServer;
use App\Entity\BannedWord;
use App\Entity\ServerAction;
use App\Entity\ServerEvent;
use App\Entity\Media;
use App\Entity\Server;
use App\Entity\ServerFollow;
use App\Entity\ServerTeamMember;
use App\Entity\User;
use App\Event\AppEvents;
use App\Event\ServerActionEvent;
use App\Event\ViewEvent;
use App\Form\Model\ServerTeamMemberModel;
use App\Form\Type\ServerTeamMemberType;
use Symfony\Component\HttpFoundation\Request;
use App\Form\Type\ServerType;
use App\Media\Adapter\Exception\FileExistsException;
use App\Media\Adapter\Exception\FileNotFoundException;
use App\Media\Adapter\Exception\WriteException;
use App\Media\Paths;
use App\Media\WebHandlerInterface;
use App\Services\Exception\DiscordRateLimitException;
use Exception;
use Gumlet\ImageResize;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(name: 'server_')]
class ServerController extends Controller
{
    /**
     * @var WebHandlerInterface
     */
    protected $webHandler;

    /**
     * @param WebHandlerInterface $webHandler
     */
    public function setWebHandler(WebHandlerInterface $webHandler)
    {
        $this->webHandler = $webHandler;
    }

    /**
     * @param string  $slug
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/{slug}', name: 'index')]
    public function indexAction($slug, Request $request): Response
    {
        $server = $this->fetchServerOrThrow($slug);

        $this->eventDispatcher->dispatch(AppEvents::SERVER_VIEW, new ViewEvent($server, $request));

        return $this->render('server/index.html.twig', [
            'server' => $server,
            'title'  => $server->getName()
        ]);
    }

    /**
     * @param string $slug
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/server/stats/{slug}', name: 'stats')]
    public function statsAction($slug): Response
    {
        $server = $this->fetchServerOrThrow($slug);
        if (!$this->hasServerAccess($server, self::SERVER_ROLE_EDITOR)) {
            throw $this->createAccessDeniedException();
        }

        $actionLog = $this->em->getRepository(ServerAction::class)
            ->createQueryBuilder('a')
            ->where('a.server = :server')
            ->setParameter(':server', $server)
            ->orderBy('a.id', 'desc')
            ->setMaxResults(100)
            ->getQuery()
            ->execute();

        $joinCount = $this->em->getRepository(ServerEvent::class)
            ->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->where('j.server = :server')
            ->andWhere('j.eventType = :eventType')
            ->setParameter(':server', $server)
            ->setParameter(':eventType', ServerEvent::TYPE_JOIN)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();

        $viewCount = $this->em->getRepository(ServerEvent::class)
            ->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->where('j.server = :server')
            ->andWhere('j.eventType = :eventType')
            ->setParameter(':server', $server)
            ->setParameter(':eventType', ServerEvent::TYPE_VIEW)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('server/stats.html.twig', [
            'server'    => $server,
            'actionLog' => $actionLog,
            'joinCount' => $joinCount,
            'viewCount' => $viewCount,
            'title'     => sprintf('Stats for %s', $server->getName())
        ]);
    }

    /**
     * @param string  $slug
     *
     * @param Request $request
     *
     * @return Response
     * @throws GuzzleException
     */
    #[Route('/server/team/{slug}', name: 'team', methods: ['GET', 'POST'])]
    public function teamAction($slug, Request $request): Response
    {
        $server = $this->fetchServerOrThrow($slug);
        if (!$this->hasServerAccess($server, self::SERVER_ROLE_MANAGER)) {
            throw $this->createAccessDeniedException();
        }

        $model = new ServerTeamMemberModel();
        $form  = $this->createForm(ServerTeamMemberType::class, $model);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $discordID     = null;
            $username      = '';
            $discriminator = 0;
            $avatarHash    = '';
            $modelUsername = $model->getUsername();

            if (is_numeric($modelUsername)) {
                try {
                    $user = $this->discord->fetchUser($modelUsername);
                    if (!$user) {
                        throw new Exception();
                    }
                    $discordID     = $user['id'];
                    $username      = $user['username'];
                    $avatarHash    = $user['avatar'];
                    $discriminator = $user['discriminator'];
                } catch (Exception $e) {
                    $form
                        ->get('username')
                        ->addError(new FormError('User not found on Discord.'));
                }
            } else {
                try {
                    list($username, $discriminator) = $this->discord->extractUsernameAndDiscriminator($modelUsername);
                } catch (InvalidArgumentException $e) {
                    $form
                        ->get('username')
                        ->addError(new FormError('Invalid format. Must be username#discriminator.'));
                }
            }

            if ($username && $discriminator) {
                $user = $this->getUser();
                $teamMemberRepo = $this->em->getRepository(ServerTeamMember::class);
                if ($username === $user->getDiscordUsername() && $discriminator == $user->getDiscordDiscriminator()) {
                    $this->addFlash('danger', 'You cannot add yourself.');
                } else if ($teamMemberRepo->findByServerAndDiscordUsernameAndDiscriminator($server, $username, $discriminator)) {
                    $this->addFlash('danger', 'User is already a member of the team.');
                } else {
                    $teamMember = (new ServerTeamMember())
                        ->setServer($server)
                        ->setRole($model->getRole())
                        ->setDiscordAvatar($avatarHash)
                        ->setDiscordUsername($username)
                        ->setDiscordDiscriminator($discriminator);
                    if ($discordID) {
                        $teamMember->setDiscordID($discordID);
                    }
                    $teamUser = $this->em->getRepository(User::class)
                        ->findByDiscordUsernameAndDiscriminator($username, $discriminator);
                    if ($teamUser) {
                        $teamMember->setUser($teamUser);
                    }

                    $this->em->persist($teamMember);
                    $this->em->flush();
                    $this->addFlash('success', 'The user has been added to the server team');

                    $this->eventDispatcher->dispatch(
                        'app.server.action',
                        new ServerActionEvent($server, $user, 'Added team member.')
                    );

                    return new RedirectResponse(
                        $this->generateUrl('server_team', ['slug' => $slug])
                    );
                }
            }
        }

        $teamMembers = $this->em->getRepository(ServerTeamMember::class)
            ->findByServer($server);

        return $this->render('server/team.html.twig', [
            'server'      => $server,
            'form'        => $form->createView(),
            'teamMembers' => $teamMembers,
            'title'       => sprintf('Team %s', $server->getName())
        ]);
    }

    /**
     * @param string  $slug
     *
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/server/team/{slug}', name: 'team_delete', methods: ['DELETE'], options: ['expose' => true])]
    public function teamRemoveAction($slug, Request $request): Response
    {
        $server = $this->fetchServerOrThrow($slug);
        if (!$this->hasServerAccess($server, self::SERVER_ROLE_OWNER)) {
            throw $this->createAccessDeniedException();
        }

        $teamMember = $this->getDoctrine()->getRepository(ServerTeamMember::class)
            ->findByServerAndID($server, $request->request->get('teamMemberID'));
        if (!$teamMember) {
            throw $this->createNotFoundException();
        }

        $this->em->remove($teamMember);
        $this->em->flush();

        return new JsonResponse('ok');
    }

    /**
     * @param string  $slug
     * @param Request $request
     *
     * @return Response
     * @throws FileNotFoundException
     * @throws GuzzleException
     */
    #[Route('/server/settings/{slug}', name: 'settings')]
    public function settingsAction($slug, Request $request): Response
    {
        $server = $this->fetchServerOrThrow($slug);
        if (!$this->hasServerAccess($server, self::SERVER_ROLE_MANAGER)) {
            throw $this->createAccessDeniedException();
        }

        $user      = $this->getUser();
        $slug      = $server->getSlug();
        $discordID = $server->getDiscordID();
        $form      = $this->createForm(ServerType::class, $server, [
            'user'      => $user,
            'isEditing' => true
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // These values cannot be changed when editing.
            $server->setUser($user);
            $server->setSlug($slug);
            $server->setDiscordID($discordID);

            if ($this->processForm($form, true)) {
                $this->em->persist($server);
                $this->em->flush();
                $this->addFlash('success', 'The server has been updated.');

                $this->eventDispatcher->dispatch(
                    'app.server.action',
                    new ServerActionEvent($server, $user, 'Changed settings.')
                );

                return new RedirectResponse($this->generateUrl('profile_index'));
            } else {
                $this->addFlash('danger', 'Please fix the errors below.');
            }
        }

        return $this->render(
            'server/settings.html.twig',
            [
                'form'      => $form->createView(),
                'server'    => $server,
                'isEditing' => true,
                'title'     => sprintf('Settings for %s', $server->getName())
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws FileNotFoundException
     * @throws GuzzleException
     * @throws DiscordRateLimitException
     */
    #[Route('/server/add', name: 'add', options: ['expose' => true])]
    public function addAction(Request $request): Response
    {
        $user   = $this->getUser();
        $server = new Server();
        $form   = $this->createForm(ServerType::class, $server, [
            'user' => $user
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $server->setUser($user);

            if ($this->processForm($form, false)) {
                $widget = $this->discord->fetchWidget($server->getDiscordID());
                $server->setMembersOnline(count($widget['members']));

                $teamMember = (new ServerTeamMember())
                    ->setUser($user)
                    ->setServer($server)
                    ->setRole(ServerTeamMember::ROLE_OWNER)
                    ->setDiscordUsername($user->getDiscordUsername())
                    ->setDiscordDiscriminator($user->getDiscordDiscriminator());
                $this->em->persist($server);
                $this->em->persist($teamMember);
                $this->em->flush();
                $this->addFlash('success', 'The server has been added.');

                $this->eventDispatcher->dispatch(
                    'app.server.action',
                    new ServerActionEvent($server, $user, 'Created server.')
                );

                return new RedirectResponse($this->generateUrl('profile_index'));
            } else {
                $this->addFlash('danger', 'Please fix the errors below.');
            }
        }

        return $this->render(
            'server/add.html.twig',
            [
                'form'      => $form->createView(),
                'isEditing' => false,
                'title'     => 'Add Server'
            ]
        );
    }

    /**
     * @param FormInterface $form
     * @param bool          $isEditing
     *
     * @return bool
     * @throws FileNotFoundException
     * @throws Exception
     * @throws GuzzleException
     */
    private function processForm(FormInterface $form, $isEditing): bool
    {
        /** @var Server $server */
        $server   = $form->getData();
        $repo     = $this->getDoctrine()->getRepository(Server::class);
        $isValid  = true;

        if ($this->em->getRepository(BannedServer::class)->isBanned($server->getDiscordID())) {
            $form
                ->get('discordID')
                ->addError(new FormError('Server is banned.'));
            return false;
        }

        $foundCats = [];
        foreach($server->getCategories() as $category) {
            if ($category->getId() && in_array($category->getId(), $foundCats)) {
                $form
                    ->get('category2')
                    ->addError(new FormError('Categories must be unique.'));
                return false;
            }
            $foundCats[] = $category->getId();
        }

        $foundTags = [];
        foreach($server->getTags() as $tag) {
            if ($tag->getId() && in_array($tag->getId(), $foundTags)) {
                $form
                    ->get('tags')
                    ->addError(new FormError('Tags must be unique.'));
                return false;
            }
            $foundTags[] = $tag->getId();
        }

        $bannedWordRepo = $this->em->getRepository(BannedWord::class);
        foreach($server->getTags() as $tag) {
            if ($bannedWordRepo->containsBannedWords($tag->getName())) {
                $form
                    ->get('tags')
                    ->addError(new FormError('Contains banned words.'));
                return false;
            }
        }
        if ($bannedWordRepo->containsBannedWords($server->getSummary())) {
            $form
                ->get('summary')
                ->addError(new FormError('Contains banned words.'));
            return false;
        }
        if ($bannedWordRepo->containsBannedWords($server->getDescription())) {
            $form
                ->get('description')
                ->addError(new FormError('Contains banned words.'));
            return false;
        }

        if (!$isEditing) {
            if ($repo->findByDiscordID($server->getDiscordID())) {
                $isValid = false;
                $form
                    ->get('discordID')
                    ->addError(new FormError('Discord ID in use.'));
            }

            if ($repo->findBySlug($server->getSlug())) {
                $isValid = false;
                $form
                    ->get('slug')
                    ->addError(new FormError('Slug already in use.'));
            }

            try {
                $this->discord->fetchWidget($server->getDiscordID());
            } catch(Exception $e) {
                $isValid = false;
                $this->addFlash('danger', 'Widget not enabled.');
            }
        }

        if (in_array($server->getSlug(), $this->getForbiddenSlugs())) {
            $isValid = false;
            $form
                ->get('slug')
                ->addError(new FormError('Slug already in use.'));
        }

        if ($form['updatePassword']->getData()) {
            if ($server->getServerPassword() === '') {
                $server->setServerPassword('');
            } else {
                $server->setServerPassword(
                    password_hash($server->getServerPassword(), PASSWORD_BCRYPT)
                );
            }
        } else {
            $server->setServerPassword('');
        }

        if (!$server->getBotInviteChannelID()) {
            $widget = [];
            try {
                $widget = $this->discord->fetchWidget($server->getDiscordID());
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
            if (!$widget || !isset($widget['instant_invite'])) {
                $isValid = false;
                $form
                    ->get('inviteType')
                    ->addError(new FormError('Instant invite not enabled. A channel is required.'));
            }
        }

        $iconMedia   = null;
        $bannerMedia = null;
        try {
            $guild  = null;
            $guilds = $this->discord->fetchMeGuilds($this->getUser()->getDiscordAccessToken());
            foreach($guilds as $g) {
                if ($g['id'] == $server->getDiscordID()) {
                    $guild = $g;
                    break;
                }
            }
            if (!$guild) {
                $form
                    ->get('discordID')
                    ->addError(new FormError('Unable to get server information from Discord.'));
                return false;
            }

            $iconFile = $this->discord->writeGuildIcon($server->getDiscordID(), $guild['icon']);
            if ($iconFile) {
                $iconMedia = $this->moveIconFile($iconFile, $server);
                if ($iconMedia) {
                    $server->setIconMedia($iconMedia);
                } else {
                    $isValid = false;
                    $this->addFlash('danger', 'There was an error grabbing the server icon image.');
                }
            }

            if ($bannerFile  = $form['bannerFile']->getData()) {
                $bannerCropData = $form['bannerCropData']->getData();
                if ($bannerCropData) {
                    $bannerCropData = json_decode($bannerCropData, true);
                }
                $bannerMedia = $this->moveBannerFile($bannerFile, $bannerCropData, $server);
                if ($bannerMedia) {
                    $server->setBannerMedia($bannerMedia);
                } else {
                    $isValid = false;
                    $form
                        ->get('bannerFile')
                        ->addError(new FormError('There was an error uploading the file.'));
                }
            }
        } catch (Exception $e) {
            if ($iconMedia) {
                $this->deleteUploadedFile($iconMedia);
            }
            if ($bannerMedia) {
                $this->deleteUploadedFile($bannerMedia);
            }
            throw $e;
        }

        return $isValid;
    }

    /**
     * Returns all the site paths which might conflict with server slugs
     *
     * @return array
     */
    private function getForbiddenSlugs(): array
    {
        $paths = [
            'admin'
        ];

        foreach ($this->get('router')->getRouteCollection()->all() as $route) {
            $path = $route->getPath();
            $path = array_filter(explode('/', $path));
            $path = array_shift($path);
            if ($path && !in_array($path, $paths) && $path[0] !== '{') {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * @param UploadedFile $file
     * @param array        $cropData
     * @param Server       $server
     *
     * @return Media
     * @throws Exception
     * @throws FileExistsException
     * @throws FileNotFoundException
     * @throws WriteException
     */
    private function moveBannerFile(UploadedFile $file, $cropData, Server $server): ?Media
    {
        if ($file->getError() !== 0) {
            return null;
        }

        $mimeTypes = [
            'image/jpeg' => 'jpg',
            'image/jpg'  => 'jpg',
            'image/png'  => 'png'
        ];
        $mimeType  = $file->getMimeType();
        if (!in_array($mimeType, array_keys($mimeTypes))) {
            return null;
        }

        if ($cropData) {
            $resizer = new ImageResize($file->getPathname());
            $resizer->freecrop($cropData['width'], $cropData['height'], $cropData['x'], $cropData['y']);
            $resizer->save($file->getPathname());
        }

        $paths = new Paths();
        $path  = $paths->getPathByType(
            'banner',
            $server->getDiscordID(),
            $this->snowflakeGenerator->generate(),
            $mimeTypes[$mimeType]
        );

        return $this->webHandler->write('banner', $path, $file->getPathname());
    }

    /**
     * @param string $filename
     * @param Server $server
     *
     * @return Media
     * @throws FileExistsException
     * @throws FileNotFoundException
     * @throws WriteException
     */
    private function moveIconFile($filename, Server $server): Media
    {
        $paths = new Paths();
        $path  = $paths->getPathByType(
            'icon',
            $server->getDiscordID(),
            $this->snowflakeGenerator->generate(),
            'png'
        );

        return $this->webHandler->write('icon', $path, $filename);
    }

    /**
     * @param Media $media
     *
     * @return bool
     * @throws FileNotFoundException
     */
    private function deleteUploadedFile(Media $media): bool
    {
        return $this->webHandler->getAdapter()->remove($media->getPath());
    }
}
