<?php
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
     * @return Response
     * @throws Exception
     */
    #[Route('/profile', name: 'index', options: ['expose' => true])]
    public function indexAction(): Response
    {
        /** @var Server $premiumServer */
        $eventRepo = $this->em->getRepository(ServerEvent::class);
        $servers   = $this->em->getRepository(Server::class)
            ->findByTeamMemberUser($this->getUser());

        $premiumServer = null;
        $premiumStatus = Server::STATUS_STR_STANDARD;
        foreach($servers as $server) {
            // Set the server with the greatest premium status, which is used
            // to display the "Bump all servers" button if applicable.
            if (!$premiumServer) {
                $premiumServer = $server;
            } else if ($server->getPremiumStatus() > $premiumServer->getPremiumStatus()) {
                $premiumServer = $server;
            }

            // The countdown on the profile page needs to know when the server
            // can be bumped again, which is given in seconds.
            $dateBumped = $server->getDateBumped();
            if (!$dateBumped) {
                $server->setNextBumpSeconds(0);
            } else {
                $window = $dateBumped->getTimestamp() + Server::BUMP_PERIOD_SECONDS;
                $server->setNextBumpSeconds($window - time());
            }

            // Needed to display the last time the server was bump and by who.
            $server->setLastBumpEvent(
                $eventRepo->findLastByEvent(ServerEvent::TYPE_BUMP)
            );
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
