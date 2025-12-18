<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Purchase;
use App\Entity\Server;
use App\Entity\ServerEvent;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(name: 'profile_')]
class ProfileController extends Controller
{
    /**
     * @throws Exception
     */
    #[Route('/profile', name: 'index', options: ['expose' => true])]
    public function indexAction(): Response
    {
        $eventRepo = $this->em->getRepository(ServerEvent::class);
        $serverRepo = $this->em->getRepository(Server::class);

        $servers = $serverRepo->findByTeamMemberUser($this->getUser());

        // Batch fetch last bump events for all servers to avoid N+1 queries
        $serverIds = array_map(fn($s) => $s->getId(), $servers);
        $lastBumpEvents = $serverIds ? $eventRepo->findLastBumpEventsForServers($serverIds) : [];

        $premiumServer = null;
        $premiumStatus = Server::STATUS_STR_STANDARD;
        $now = time();

        foreach ($servers as $server) {
            // Set the server with the greatest premium status
            if (!$premiumServer || $server->getPremiumStatus() > $premiumServer->getPremiumStatus()) {
                $premiumServer = $server;
            }

            // Calculate next bump time
            $dateBumped = $server->getDateBumped();
            if (!$dateBumped) {
                $server->setNextBumpSeconds(0);
            } else {
                $window = $dateBumped->getTimestamp() + Server::BUMP_PERIOD_SECONDS;
                $server->setNextBumpSeconds($window - $now);
            }

            // Use pre-fetched bump events instead of N+1 query
            $server->setLastBumpEvent($lastBumpEvents[$server->getId()] ?? null);
        }

        if ($premiumServer) {
            $premiumStatus = $premiumServer->getPremiumStatusString();
        }

        return $this->render('profile/index.html.twig', [
            'servers'       => $servers,
            'premiumStatus' => $premiumStatus,
            'title'         => 'Profile'
        ]);
    }

    /**
     * @return Response
     */
    #[Route('/profile/settings', name: 'settings')]
    public function settingsAction(): Response
    {
        return $this->render('profile/settings.html.twig', [
            'title' => 'Profile Settings'
        ]);
    }

    #[Route('/profile/invoices', name: 'invoices')]
    public function invoicesAction(): Response
    {
        $purchases = $this->em->getRepository(Purchase::class)->findByUser($this->getUser());

        return $this->render('profile/invoices.html.twig', [
            'purchases' => $purchases,
            'title'     => 'Invoices'
        ]);
    }
}
