<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\PurchasePeriod;
use App\Entity\Server;
use App\Repository\PurchasePeriodRepository;
use App\Repository\ServerRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:server:upgrades',
    description: 'Process premium subscription upgrades and expirations'
)]
class ServerUpgradesCommand extends Command
{
    private PurchasePeriodRepository $periodRepo;
    private ServerRepository $serverRepo;

    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();

        $this->periodRepo = $em->getRepository(PurchasePeriod::class);
        $this->serverRepo = $em->getRepository(Server::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->periodRepo->findExpired() as $purchasePeriod) {
            $server = $purchasePeriod->getPurchase()->getServer();
            if ($server->getPremiumStatus() !== Server::STATUS_STANDARD) {
                $output->writeln(
                    sprintf('Expiring %s.', $server->getDiscordID())
                );
                $this->expire($purchasePeriod);
            }
        }

        $this->em->flush();

        foreach ($this->periodRepo->findReady() as $purchasePeriod) {
            $server = $purchasePeriod->getPurchase()->getServer();
            if ($server->getPremiumStatus() === Server::STATUS_STANDARD) {
                $output->writeln(
                    sprintf('Upgrading %s.', $server->getDiscordID())
                );
                $this->upgrade($purchasePeriod);
            }
        }

        $this->em->flush();

        $output->writeln('Done!');

        return Command::SUCCESS;
    }

    private function expire(PurchasePeriod $purchasePeriod): void
    {
        $server = $purchasePeriod->getPurchase()->getServer();
        $server->setPremiumStatus(Server::STATUS_STANDARD);
        $purchasePeriod->setIsComplete(true);
    }

    private function upgrade(PurchasePeriod $purchasePeriod): void
    {
        $purchase = $purchasePeriod->getPurchase();
        $server = $purchasePeriod->getPurchase()->getServer();
        $server->setPremiumStatus($purchase->getPremiumStatus());

        $period = $purchase->getPeriod();
        $purchasePeriod
            ->setDateBegins(new DateTime())
            ->setDateExpires(new DateTime("{$period} days"));
    }
}
